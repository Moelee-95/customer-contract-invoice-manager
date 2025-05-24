<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only PUT requests are supported.'
    ]);
    exit();
}

try {
    $db_connect_path = '../../db_connect.php';
    if (!file_exists($db_connect_path)) {
        throw new Exception('Database connection file not found');
    }
    
    require_once $db_connect_path;
    
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('Invoice ID is required');
    }
    
    $invoiceId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($invoiceId === false) {
        throw new Exception('Invalid invoice ID provided');
    }
    
    $input = file_get_contents('php://input');
    if ($input === false) {
        throw new Exception('Failed to read input data');
    }
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data provided');
    }
    
    if (empty($data)) {
        throw new Exception('No data provided');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Failed to connect to database');
    }
    
    $invoiceUpdateManager = new InvoiceUpdateManager($db);
    $result = $invoiceUpdateManager->updateInvoice($invoiceId, $data);
    
    if (!$result['success']) {
        http_response_code(400);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'data' => null
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error: ' . $e->getMessage(),
        'data' => null
    ]);
}

class InvoiceUpdateManager {
    private $connection;
    
    public function __construct($db) {
        $this->connection = $db;
    }
    
    public function updateInvoice($invoiceId, $data) {
        try {
            $requiredFields = ['customerId', 'invoiceDate', 'createdBy', 'items'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            if (!$this->invoiceExists($invoiceId)) {
                throw new Exception("Invoice with ID $invoiceId not found");
            }
            
            if (!is_array($data['items']) || count($data['items']) === 0) {
                throw new Exception("At least one invoice item is required");
            }
            
            $customerName = $this->getCustomerName($data['customerId']);
            if (!$customerName) {
                throw new Exception("Customer with ID " . $data['customerId'] . " not found");
            }
            
            if (!$this->connection->autocommit(false)) {
                throw new Exception("Failed to start transaction");
            }
            
            try {
                $this->updateInvoiceMain($invoiceId, $data, $customerName);
                $this->deleteInvoiceItems($invoiceId);
                $this->insertInvoiceItems($invoiceId, $data['items']);
                
                if (!$this->connection->commit()) {
                    throw new Exception("Failed to commit transaction");
                }
                
                $this->connection->autocommit(true);
                
                return [
                    'success' => true,
                    'message' => 'Invoice updated successfully',
                    'data' => [
                        'invoiceId' => $invoiceId,
                        'customerId' => $data['customerId'],
                        'customerName' => $customerName,
                        'invoiceDate' => $data['invoiceDate'],
                        'status' => isset($data['done']) && $data['done'] ? 'Done' : 'Undone',
                        'itemsCount' => count($data['items'])
                    ]
                ];
                
            } catch (Exception $e) {
                $this->connection->rollback();
                $this->connection->autocommit(true);
                throw $e;
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    private function invoiceExists($invoiceId) {
        $stmt = $this->connection->prepare("SELECT id FROM invoices WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Prepare invoice check failed: ' . $this->connection->error);
        }
        
        $stmt->bind_param('i', $invoiceId);
        if (!$stmt->execute()) {
            throw new Exception('Execute invoice check failed: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    private function updateInvoiceMain($invoiceId, $data, $customerName) {
        $doneStatus = isset($data['done']) && $data['done'] ? 'Done' : 'Undone';
        
        $stmt = $this->connection->prepare("
            UPDATE invoices 
            SET customer_id = ?, 
                customer_name = ?, 
                inv_date = ?, 
                done_undone = ?,
                created_by_user = ?
            WHERE id = ?
        ");
        
        if (!$stmt) {
            throw new Exception('Prepare update invoice failed: ' . $this->connection->error);
        }
        
        $customerId = intval($data['customerId']);
        $invoiceDate = $data['invoiceDate'];
        $createdBy = intval($data['createdBy']);
        
        $stmt->bind_param(
            'isssii',
            $customerId,
            $customerName,
            $invoiceDate,
            $doneStatus,
            $createdBy,
            $invoiceId
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Execute update invoice failed: ' . $stmt->error);
        }
        
        if ($stmt->affected_rows === 0 && !$this->invoiceExists($invoiceId)) {
            throw new Exception("Invoice with ID $invoiceId no longer exists");
        }
        
        $stmt->close();
    }
    
    private function deleteInvoiceItems($invoiceId) {
        $stmt = $this->connection->prepare("DELETE FROM invoice_item WHERE invoice_id = ?");
        
        if (!$stmt) {
            throw new Exception('Prepare delete items failed: ' . $this->connection->error);
        }
        
        $stmt->bind_param('i', $invoiceId);
        
        if (!$stmt->execute()) {
            throw new Exception('Execute delete items failed: ' . $stmt->error);
        }
        
        $stmt->close();
    }
    
    private function insertInvoiceItems($invoiceId, $items) {
        $stmt = $this->connection->prepare("
            INSERT INTO invoice_item (invoice_id, item_name, price, quantity, subtotal, note)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            throw new Exception('Prepare insert items failed: ' . $this->connection->error);
        }
        
        foreach ($items as $index => $item) {
            if (empty($item['name']) || !isset($item['price']) || !isset($item['quantity'])) {
                throw new Exception("Item #" . ($index + 1) . " must have name, price, and quantity");
            }
            
            $itemName = $item['name'];
            $price = floatval($item['price']);
            $quantity = intval($item['quantity']);
            $subtotal = floatval($item['subtotal'] ?? ($item['price'] * $item['quantity']));
            $note = $item['note'] ?? '';
            
            $stmt->bind_param(
                'isddds',
                $invoiceId,
                $itemName,
                $price,
                $quantity,
                $subtotal,
                $note
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Execute insert item failed: ' . $stmt->error);
            }
        }
        
        $stmt->close();
    }
    
    private function getCustomerName($customerId) {
        $stmt = $this->connection->prepare("SELECT name FROM customers WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Prepare customer query failed: ' . $this->connection->error);
        }
        
        $stmt->bind_param('i', $customerId);
        if (!$stmt->execute()) {
            throw new Exception('Execute customer query failed: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        $stmt->close();
        
        return $customer ? $customer['name'] : false;
    }
}
?>
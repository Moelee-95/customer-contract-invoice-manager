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
            
            // Get old invoice data before updating (for audit logging)
            $oldInvoiceData = $this->getInvoiceData($invoiceId);

            // Temporary debugging - remove this after testing
            error_log('Old invoice data: ' . print_r($oldInvoiceData, true));

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
                
                // Prepare old values for logging
                // Prepare old values for logging
                $oldValues = null;
                if ($oldInvoiceData) {
                    $oldValues = [
                        'customer_id' => $oldInvoiceData['customer_id'],
                        'customer_name' => $oldInvoiceData['customer_name'],
                        'invoice_date' => $oldInvoiceData['inv_date'],
                        'status' => $oldInvoiceData['done_undone'],
                        'items' => $oldInvoiceData['items'] ?? []
                    ];
                }

                // Log the successful invoice update
                $this->logUserAction(
                    $data['createdBy'],
                    'UPDATE',
                    'invoices',
                    $invoiceId,
                    'Updated invoice for customer: ' . $customerName,
                    [
                        'customer_id' => $data['customerId'],
                        'customer_name' => $customerName,
                        'invoice_date' => $data['invoiceDate'],
                        'status' => isset($data['done']) && $data['done'] ? 'Done' : 'Undone',
                        'items_count' => count($data['items'])
                    ],
                    $oldValues
                );

                // Log the successful invoice update
                $this->logUserAction(
                    $data['createdBy'],
                    'UPDATE',
                    'invoices',
                    $invoiceId,
                    'Updated invoice for customer: ' . $customerName,
                    [
                        'customer_id' => $data['customerId'],
                        'customer_name' => $customerName,
                        'invoice_date' => $data['invoiceDate'],
                        'status' => isset($data['done']) && $data['done'] ? 'Done' : 'Undone',
                        'items_count' => count($data['items'])
                    ],
                    $oldValues  // This will now contain the old values
                );

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
            // Log the error
            if (isset($data['createdBy'])) {
                $this->logUserAction(
                    $data['createdBy'],
                    'UPDATE',
                    'invoices',
                    $invoiceId,
                    'Error updating invoice ID: ' . $invoiceId,
                    null,
                    null,
                    'FAILED',
                    $e->getMessage()
                );
            }
            
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

    public function logUserAction($userId, $actionType, $tableName, $recordId, $description, $newValues = null, $oldValues = null, $status = 'SUCCESS', $errorMessage = null) {
        try {
            $query = "INSERT INTO users_actions (
                        user_id, 
                        action_type, 
                        table_name, 
                        record_id, 
                        action_description, 
                        new_values, 
                        old_values, 
                        ip_address, 
                        user_agent, 
                        status, 
                        error_message
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->connection->prepare($query);
            
            if (!$stmt) {
                error_log('Audit log prepare failed: ' . $this->connection->error);
                return false;
            }
            
            // Get user's IP address
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            
            // Get user agent
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            // Convert arrays to JSON
            $newValuesJson = $newValues ? json_encode($newValues) : null;
            $oldValuesJson = $oldValues ? json_encode($oldValues) : null;
            
            $stmt->bind_param('isissssssss',
                $userId,
                $actionType,
                $tableName,
                $recordId,
                $description,
                $newValuesJson,
                $oldValuesJson,
                $ipAddress,
                $userAgent,
                $status,
                $errorMessage
            );
            
            $result = $stmt->execute();
            $stmt->close();
            
            if (!$result) {
                error_log('Audit log execution failed: ' . $this->connection->error);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('Audit logging error: ' . $e->getMessage());
            return false;
        }
    }

    private function getInvoiceData($invoiceId) {
        try {
            // Get invoice main data
            $stmt = $this->connection->prepare("SELECT * FROM invoices WHERE id = ?");
            if (!$stmt) {
                error_log('Failed to prepare invoice query: ' . $this->connection->error);
                return null;
            }
            
            $stmt->bind_param('i', $invoiceId);
            if (!$stmt->execute()) {
                error_log('Failed to execute invoice query: ' . $stmt->error);
                $stmt->close();
                return null;
            }
            
            $result = $stmt->get_result();
            $invoiceData = $result->fetch_assoc();
            $stmt->close();
            
            if (!$invoiceData) {
                return null;
            }
            
            // Get invoice items
            $stmt = $this->connection->prepare("SELECT * FROM invoice_item WHERE invoice_id = ?");
            if (!$stmt) {
                error_log('Failed to prepare items query: ' . $this->connection->error);
                return $invoiceData; // Return at least the main invoice data
            }
            
            $stmt->bind_param('i', $invoiceId);
            if (!$stmt->execute()) {
                error_log('Failed to execute items query: ' . $stmt->error);
                $stmt->close();
                return $invoiceData;
            }
            
            $itemsResult = $stmt->get_result();
            $items = [];
            while ($item = $itemsResult->fetch_assoc()) {
                $items[] = $item;
            }
            $stmt->close();
            
            $invoiceData['items'] = $items;
            return $invoiceData;
            
        } catch (Exception $e) {
            error_log('Error in getInvoiceData: ' . $e->getMessage());
            return null;
        }
    }
}
?>
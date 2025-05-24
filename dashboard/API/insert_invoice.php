<?php

require_once '../../db_connect.php';

class InvoiceManager {
    private $connection;
    
    public function __construct($db) {
        $this->connection = $db;
    }
    
    public function insertInvoice($data) {
        try {
            // Validate required fields
            $requiredFields = ['customerId', 'invoiceDate', 'createdBy', 'items'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Validate items array
            if (!is_array($data['items']) || count($data['items']) === 0) {
                throw new Exception("At least one invoice item is required");
            }
            
            // Get customer name
            $customerName = $this->getCustomerName($data['customerId']);
            if (!$customerName) {
                throw new Exception("Customer not found");
            }
            
            // Prepare items JSON for the stored procedure
            $itemsForProcedure = [];
            foreach ($data['items'] as $item) {
                // Validate each item
                if (empty($item['name']) || !isset($item['price']) || !isset($item['quantity'])) {
                    throw new Exception("Each item must have name, price, and quantity");
                }
                
                $itemsForProcedure[] = [
                    'item_name' => $item['name'],
                    'price' => floatval($item['price']),
                    'quantity' => intval($item['quantity']),
                    'subtotal' => floatval($item['subtotal'] ?? ($item['price'] * $item['quantity'])),
                    'note' => $item['note'] ?? ''
                ];
            }
            
            // Convert to JSON
            $itemsJson = json_encode($itemsForProcedure);
            
            // Prepare the stored procedure call
            $stmt = $this->connection->prepare("CALL insert_invoice_with_items(?, ?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
            }
            
            // Convert done status to string
            $doneStatus = isset($data['done']) && $data['done'] ? 'Done' : 'Undone';
            
            // Bind parameters
            $stmt->bind_param(
                'iissss',
                $data['createdBy'],
                $data['customerId'],
                $customerName,
                $data['invoiceDate'],
                $doneStatus,
                $itemsJson
            );
            
            // Execute the procedure
            if (!$stmt->execute()) {
                throw new Exception('Execute failed: ' . $stmt->error);
            }
            
            $stmt->close();
            
            return [
                'success' => true,
                'message' => 'Invoice created successfully',
                'data' => [
                    'customerId' => $data['customerId'],
                    'customerName' => $customerName,
                    'invoiceDate' => $data['invoiceDate'],
                    'status' => $doneStatus,
                    'itemsCount' => count($data['items'])
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    private function getCustomerName($customerId) {
        try {
            $stmt = $this->connection->prepare("SELECT name FROM customers WHERE id = ?");
            if (!$stmt) {
                return false;
            }
            
            $stmt->bind_param('i', $customerId);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $customer = $result->fetch_assoc();
            
            $stmt->close();
            
            return $customer ? $customer['name'] : false;
            
        } catch (Exception $e) {
            return false;
        }
    }
}

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only POST requests are supported.'
    ]);
    exit();
}

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data provided');
    }
    
    if (empty($data)) {
        throw new Exception('No data provided');
    }
    
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Failed to connect to database');
    }
    
    // Initialize invoice manager
    $invoiceManager = new InvoiceManager($db);
    
    // Insert the invoice
    $result = $invoiceManager->insertInvoice($data);
    
    // Set appropriate HTTP status code
    if (!$result['success']) {
        http_response_code(400);
    }
    
    // Return JSON response
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'data' => null
    ]);
}

?>
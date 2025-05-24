<?php

require_once '../../db_connect.php';

class InvoiceDeleteManager {
    private $connection;
    
    public function __construct($db) {
        $this->connection = $db;
    }
    
    public function deleteInvoice($invoiceId) {
        try {
            // Validate invoice ID
            if (!$invoiceId || !is_numeric($invoiceId)) {
                throw new Exception("Invalid invoice ID provided");
            }
            
            // Check if invoice exists
            $checkStmt = $this->connection->prepare("SELECT id, customer_name FROM invoices WHERE id = ?");
            if (!$checkStmt) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
            }
            
            $checkStmt->bind_param('i', $invoiceId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows === 0) {
                $checkStmt->close();
                throw new Exception("Invoice not found");
            }
            
            $invoice = $result->fetch_assoc();
            $customerName = $invoice['customer_name'];
            $checkStmt->close();
            
            // Start transaction
            $this->connection->autocommit(false);
            
            try {
                // Delete invoice items first (foreign key constraint)
                $deleteItemsStmt = $this->connection->prepare("DELETE FROM invoice_item WHERE invoice_id = ?");
                if (!$deleteItemsStmt) {
                    throw new Exception('Prepare failed for items: ' . $this->connection->error);
                }
                
                $deleteItemsStmt->bind_param('i', $invoiceId);
                if (!$deleteItemsStmt->execute()) {
                    throw new Exception('Failed to delete invoice items: ' . $deleteItemsStmt->error);
                }
                $deletedItemsCount = $deleteItemsStmt->affected_rows;
                $deleteItemsStmt->close();
                
                // Delete the invoice
                $deleteInvoiceStmt = $this->connection->prepare("DELETE FROM invoices WHERE id = ?");
                if (!$deleteInvoiceStmt) {
                    throw new Exception('Prepare failed for invoice: ' . $this->connection->error);
                }
                
                $deleteInvoiceStmt->bind_param('i', $invoiceId);
                if (!$deleteInvoiceStmt->execute()) {
                    throw new Exception('Failed to delete invoice: ' . $deleteInvoiceStmt->error);
                }
                
                if ($deleteInvoiceStmt->affected_rows === 0) {
                    throw new Exception('Invoice was not deleted');
                }
                
                $deleteInvoiceStmt->close();
                
                // Commit transaction
                $this->connection->commit();
                $this->connection->autocommit(true);
                
                return [
                    'success' => true,
                    'message' => 'Invoice deleted successfully',
                    'data' => [
                        'invoiceId' => $invoiceId,
                        'customerName' => $customerName,
                        'deletedItemsCount' => $deletedItemsCount
                    ]
                ];
                
            } catch (Exception $e) {
                // Rollback transaction
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
}

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Allow both DELETE and POST methods for flexibility
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only DELETE and POST requests are supported.'
    ]);
    exit();
}

try {
    $invoiceId = null;
    
    // Get invoice ID from different sources
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // For DELETE requests, get ID from URL parameter or request body
        if (isset($_GET['id'])) {
            $invoiceId = $_GET['id'];
        } else {
            // Try to get from JSON body
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            if ($data && isset($data['id'])) {
                $invoiceId = $data['id'];
            }
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // For POST requests, get from JSON body or form data
        if (isset($_POST['id'])) {
            $invoiceId = $_POST['id'];
        } else {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            if ($data && isset($data['id'])) {
                $invoiceId = $data['id'];
            }
        }
    }
    
    if (!$invoiceId) {
        throw new Exception('Invoice ID is required');
    }
    
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Failed to connect to database');
    }
    
    // Initialize invoice delete manager
    $invoiceDeleteManager = new InvoiceDeleteManager($db);
    
    // Delete the invoice
    $result = $invoiceDeleteManager->deleteInvoice($invoiceId);
    
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
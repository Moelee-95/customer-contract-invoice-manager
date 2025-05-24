<?php
// Start the session
session_start();

// Check if user is NOT logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header("Location: ../login.php");
    exit();
}

// Handle logout request
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    // Destroy all session data
    session_unset();
    session_destroy();
    
    // Redirect to login page
    header("Location: ../login.php");
    exit();
}
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
            
            // Get invoice data before deletion for logging
            $invoiceData = $this->getInvoiceById($invoiceId);
            $userId = $_SESSION['user_id'];
            
            if (!$invoiceData) {
                // Log the failed deletion attempt
                $this->logUserAction(
                    $userId,
                    'DELETE',
                    'invoices',
                    $invoiceId,
                    'Failed to delete invoice: Invoice not found',
                    null,
                    null,
                    'FAILED',
                    'Invoice not found'
                );
                
                throw new Exception("Invoice not found");
            }
            
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
                
                // Log the successful invoice deletion
                $this->logUserAction(
                    $userId,
                    'DELETE',
                    'invoices',
                    $invoiceId,
                    'Deleted invoice: ' . ($invoiceData['customer_name'] ?? 'Unknown customer'),
                    null,
                    $invoiceData
                );
                
                return [
                    'success' => true,
                    'message' => 'Invoice deleted successfully',
                    'data' => [
                        'invoiceId' => $invoiceId,
                        'customerName' => $invoiceData['customer_name'],
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
            // Log the error
            $this->logUserAction(
                $userId ?? 1,
                'DELETE',
                'invoices',
                $invoiceId,
                'Error deleting invoice: ' . ($invoiceData['customer_name'] ?? 'Unknown'),
                null,
                $invoiceData ?? null,
                'FAILED',
                $e->getMessage()
            );
            
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'data' => null
            ];
        }
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
            
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
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

    public function getInvoiceById($invoiceId) {
        try {
            $query = "SELECT * FROM invoices WHERE id = ?";
            $stmt = $this->connection->prepare($query);
            
            if (!$stmt) {
                return null;
            }
            
            $stmt->bind_param('i', $invoiceId);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $invoice = $result->fetch_assoc();
            $stmt->close();
            
            return $invoice;
            
        } catch (Exception $e) {
            return null;
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
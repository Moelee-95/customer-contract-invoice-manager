<?php



require_once 'session.php';
require_once '../../db_connect.php';

class CustomerDeleter {
    private $connection;
    
    public function __construct($db) {
        $this->connection = $db;
    }
    
    public function deleteCustomer($id) {
        try {
            // Start transaction for data integrity
            $this->connection->autocommit(false);
            
            $customerData = $this->getCustomerById($id);
            $userId = $_SESSION['user_id']; // get it from session user id later

            // First, delete related invoices and their items
            $deleteInvoiceItemsQuery = "DELETE invoice_item FROM invoice_item 
                                       INNER JOIN invoices ON invoice_item.invoice_id = invoices.id 
                                       WHERE invoices.customer_id = ?";
            $stmt1 = $this->connection->prepare($deleteInvoiceItemsQuery);
            
            if (!$stmt1) {
                throw new Exception('Prepare failed for invoice items deletion: ' . $this->connection->error);
            }
            
            $stmt1->bind_param('i', $id);
            $stmt1->execute();
            $stmt1->close();
            
            // Delete invoices
            $deleteInvoicesQuery = "DELETE FROM invoices WHERE customer_id = ?";
            $stmt2 = $this->connection->prepare($deleteInvoicesQuery);
            
            if (!$stmt2) {
                throw new Exception('Prepare failed for invoices deletion: ' . $this->connection->error);
            }
            
            $stmt2->bind_param('i', $id);
            $stmt2->execute();
            $stmt2->close();
            
            // Delete contracts
            $deleteContractsQuery = "DELETE FROM contracts WHERE customer_id = ?";
            $stmt3 = $this->connection->prepare($deleteContractsQuery);
            
            if (!$stmt3) {
                throw new Exception('Prepare failed for contracts deletion: ' . $this->connection->error);
            }
            
            $stmt3->bind_param('i', $id);
            $stmt3->execute();
            $stmt3->close();
            
            // Finally, delete the customer
            $deleteCustomerQuery = "DELETE FROM customers WHERE id = ?";
            $stmt4 = $this->connection->prepare($deleteCustomerQuery);
            
            if (!$stmt4) {
                throw new Exception('Prepare failed for customer deletion: ' . $this->connection->error);
            }
            
            $stmt4->bind_param('i', $id);
            $stmt4->execute();
            
            // Check if customer was actually deleted
            if ($stmt4->affected_rows === 0) {
                $stmt4->close();
                $this->connection->rollback();
                
                // Log the failed deletion attempt
                $this->logUserAction(
                    $userId,
                    'DELETE',
                    'customers',
                    $id,
                    'Failed to delete customer: Customer not found',
                    null,
                    null,
                    'FAILED',
                    'Customer not found or already deleted'
                );
                
                return [
                    'success' => false,
                    'message' => 'Customer not found or already deleted'
                ];
            }
            
            $stmt4->close();
            
            // Commit transaction
            $this->connection->commit();
            $this->connection->autocommit(true);
            
            // Log the successful customer deletion
            if ($customerData) {
                $this->logUserAction(
                    $userId,
                    'DELETE',
                    'customers',
                    $id,
                    'Deleted customer: ' . ($customerData['name'] ?? 'Unknown'),
                    null,
                    $customerData
                );
            }
            return [
                'success' => true,
                'message' => 'Customer and all related data deleted successfully'
            ];
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->connection->rollback();
            $this->connection->autocommit(true);

            // Log the error
            $this->logUserAction(
                $userId ?? 1,
                'DELETE',
                'customers',
                $id,
                'Error deleting customer: ' . ($customerData['name'] ?? 'Unknown'),
                null,
                $customerData ?? null,
                'FAILED',
                $e->getMessage()
            );
            
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    public function customerExists($id) {
        try {
            $query = "SELECT id FROM customers WHERE id = ?";
            $stmt = $this->connection->prepare($query);
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
            }
            
            $stmt->bind_param('i', $id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $exists = $result->num_rows > 0;
            
            $stmt->close();
            
            return $exists;
            
        } catch (Exception $e) {
            return false;
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

    public function getCustomerById($customerId) {
        try {
            $query = "SELECT * FROM customers WHERE id = ?";
            $stmt = $this->connection->prepare($query);
            
            if (!$stmt) {
                return null;
            }
            
            $stmt->bind_param('i', $customerId);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $customer = $result->fetch_assoc();
            $stmt->close();
            
            return $customer;
            
        } catch (Exception $e) {
            return null;
        }
    }
}

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only DELETE requests are supported.'
    ]);
    exit();
}

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Failed to connect to database');
    }
    
    // Validate customer ID parameter
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Customer ID is required'
        ]);
        exit();
    }
    
    $customerId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    
    if ($customerId === false || $customerId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid customer ID provided'
        ]);
        exit();
    }
    
    // Initialize customer deleter
    $customerDeleter = new CustomerDeleter($db);
    
    // Check if customer exists before attempting deletion
    if (!$customerDeleter->customerExists($customerId)) {
        echo json_encode([
            'success' => false,
            'message' => 'Customer not found'
        ]);
        exit();
    }
    
    // Delete customer and related data
    $result = $customerDeleter->deleteCustomer($customerId);
    
    // Set appropriate HTTP status code
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    // Return JSON response
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

?>
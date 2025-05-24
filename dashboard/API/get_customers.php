<?php
require_once 'session.php';
require_once '../../db_connect.php';

class CustomerManager {
    private $connection;
    
    public function __construct($db) {
        $this->connection = $db;
    }
    
    public function getAllCustomers() {
        try {
            $query = "SELECT 
                        id,
                        name,
                        address,
                        phone_number,
                        free_trial as freeTrial,
                        start_date as startDate,
                        note,
                        created_by_user as createdBy,
                        created_at as createdAt
                      FROM customers 
                      ORDER BY created_at DESC";
            
            $result = $this->connection->query($query);
            
            if (!$result) {
                throw new Exception('Query failed: ' . $this->connection->error);
            }
            
            $customers = [];
            while ($row = $result->fetch_assoc()) {
                // Convert database results to match Vue.js expected format
                $customer = [
                    'id' => (int)$row['id'],
                    'name' => $row['name'] ?? '',
                    'address' => $row['address'] ?? '',
                    'phone' => $row['phone_number'] ?? '', // FIXED: Use phone_number from DB
                    'freeTrial' => $row['freeTrial'] ?? '',
                    'startDate' => $row['startDate'] ?? '',
                    'note' => $row['note'] ?? '',
                    'createdBy' => $row['createdBy'] ?? '',
                    'createdAt' => $row['createdAt'] ?? ''
                ];
                $customers[] = $customer;
            }
            
            $result->free();
            
            return [
                'success' => true,
                'data' => $customers,
                'message' => 'Customers retrieved successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'data' => [],
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    public function getCustomerById($id) {
        try {
            $query = "SELECT 
                        id,
                        name,
                        address,
                        phone_number,
                        free_trial as freeTrial,
                        start_date as startDate,
                        note,
                        created_by_user as createdBy,
                        created_at as createdAt
                      FROM customers 
                      WHERE id = ?";
            
            $stmt = $this->connection->prepare($query);
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
            }
            
            $stmt->bind_param('i', $id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $customer = $result->fetch_assoc();
            
            if ($customer) {
                // Convert database results to match Vue.js expected format
                $customerData = [
                    'id' => (int)$customer['id'],
                    'name' => $customer['name'] ?? '',
                    'address' => $customer['address'] ?? '',
                    'phone' => $customer['phone_number'] ?? '', // FIXED: Use phone_number from DB
                    'freeTrial' => $customer['freeTrial'] ?? '',
                    'startDate' => $customer['startDate'] ?? '',
                    'note' => $customer['note'] ?? '',
                    'createdBy' => $customer['createdBy'] ?? '',
                    'createdAt' => $customer['createdAt'] ?? ''
                ];
                
                $stmt->close();
                
                return [
                    'success' => true,
                    'data' => $customerData,
                    'message' => 'Customer retrieved successfully'
                ];
            } else {
                $stmt->close();
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'Customer not found'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'message' => 'Database error: ' . $e->getMessage()
            ];
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

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only GET requests are supported.'
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
    
    // Initialize customer manager
    $customerManager = new CustomerManager($db);
    
    // Check if specific customer ID is requested
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $customerId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        
        if ($customerId === false) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid customer ID provided'
            ]);
            exit();
        }
        
        $result = $customerManager->getCustomerById($customerId);
    } else {
        // Get all customers
        $result = $customerManager->getAllCustomers();
    }
    
    // Return JSON response
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'data' => [],
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

?>
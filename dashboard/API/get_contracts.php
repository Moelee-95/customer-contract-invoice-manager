<?php

require_once '../../db_connect.php';

class ContractManager {
    private $connection;
    
    public function __construct($db) {
        $this->connection = $db;
    }
    
    public function getAllContracts() {
        try {
            $query = "SELECT 
                        id,
                        customer_id as customerId,
                        start_date as startDate,
                        expire_date as expireDate,
                        payment,
                        note,
                        created_by_user as createdBy,
                        created_at as createdAt
                      FROM contracts 
                      ORDER BY created_at DESC";
            
            $result = $this->connection->query($query);
            
            if (!$result) {
                throw new Exception('Query failed: ' . $this->connection->error);
            }
            
            $contracts = [];
            while ($row = $result->fetch_assoc()) {
                // Convert database results to match Vue.js expected format
                $contract = [
                    'id' => (int)$row['id'],
                    'customerId' => (int)$row['customerId'],
                    'startDate' => $row['startDate'] ?? '',
                    'expireDate' => $row['expireDate'] ?? '',
                    'payment' => (float)$row['payment'],
                    'note' => $row['note'] ?? '',
                    'createdBy' => (int)$row['createdBy'],
                    'createdAt' => $row['createdAt'] ?? ''
                ];
                $contracts[] = $contract;
            }
            
            $result->free();
            
            return [
                'success' => true,
                'data' => $contracts,
                'message' => 'Contracts retrieved successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'data' => [],
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    public function getContractById($id) {
        try {
            $query = "SELECT 
                        id,
                        customer_id as customerId,
                        start_date as startDate,
                        expire_date as expireDate,
                        payment,
                        note,
                        created_by_user as createdBy,
                        created_at as createdAt
                      FROM contracts 
                      WHERE id = ?";
            
            $stmt = $this->connection->prepare($query);
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
            }
            
            $stmt->bind_param('i', $id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $contract = $result->fetch_assoc();
            
            if ($contract) {
                // Convert database results to match Vue.js expected format
                $contractData = [
                    'id' => (int)$contract['id'],
                    'customerId' => (int)$contract['customerId'],
                    'startDate' => $contract['startDate'] ?? '',
                    'expireDate' => $contract['expireDate'] ?? '',
                    'payment' => (float)$contract['payment'],
                    'note' => $contract['note'] ?? '',
                    'createdBy' => (int)$contract['createdBy'],
                    'createdAt' => $contract['createdAt'] ?? ''
                ];
                
                $stmt->close();
                
                return [
                    'success' => true,
                    'data' => $contractData,
                    'message' => 'Contract retrieved successfully'
                ];
            } else {
                $stmt->close();
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'Contract not found'
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
    
    public function getContractsByCustomerId($customerId) {
        try {
            $query = "SELECT 
                        id,
                        customer_id as customerId,
                        start_date as startDate,
                        expire_date as expireDate,
                        payment,
                        note,
                        created_by_user as createdBy,
                        created_at as createdAt
                      FROM contracts 
                      WHERE customer_id = ?
                      ORDER BY created_at DESC";
            
            $stmt = $this->connection->prepare($query);
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
            }
            
            $stmt->bind_param('i', $customerId);
            $stmt->execute();
            
            $result = $stmt->get_result();
            
            $contracts = [];
            while ($row = $result->fetch_assoc()) {
                $contract = [
                    'id' => (int)$row['id'],
                    'customerId' => (int)$row['customerId'],
                    'startDate' => $row['startDate'] ?? '',
                    'expireDate' => $row['expireDate'] ?? '',
                    'payment' => (float)$row['payment'],
                    'note' => $row['note'] ?? '',
                    'createdBy' => (int)$row['createdBy'],
                    'createdAt' => $row['createdAt'] ?? ''
                ];
                $contracts[] = $contract;
            }
            
            $stmt->close();
            
            return [
                'success' => true,
                'data' => $contracts,
                'message' => 'Customer contracts retrieved successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'data' => [],
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
    
    // Initialize contract manager
    $contractManager = new ContractManager($db);
    
    // Check if specific contract ID is requested
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $contractId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        
        if ($contractId === false) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid contract ID provided'
            ]);
            exit();
        }
        
        $result = $contractManager->getContractById($contractId);
    } 
    // Check if contracts for specific customer are requested
    elseif (isset($_GET['customer_id']) && !empty($_GET['customer_id'])) {
        $customerId = filter_var($_GET['customer_id'], FILTER_VALIDATE_INT);
        
        if ($customerId === false) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid customer ID provided'
            ]);
            exit();
        }
        
        $result = $contractManager->getContractsByCustomerId($customerId);
    } else {
        // Get all contracts
        $result = $contractManager->getAllContracts();
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
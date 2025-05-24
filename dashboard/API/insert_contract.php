<?php

require_once '../../db_connect.php';

class ContractInserter {
    private $connection;
    
    public function __construct($db) {
        $this->connection = $db;
    }
    
    public function insertContract($data) {
        try {
            // Validate required fields
            $requiredFields = ['customerId', 'startDate', 'payment'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Validate data types and values
            $customerId = filter_var($data['customerId'], FILTER_VALIDATE_INT);
            if ($customerId === false || $customerId <= 0) {
                throw new Exception('Invalid customer ID');
            }
            
            $payment = filter_var($data['payment'], FILTER_VALIDATE_FLOAT);
            if ($payment === false || $payment < 0) {
                throw new Exception('Invalid payment amount');
            }
            
            // Validate dates
            $startDate = $data['startDate'];
            if (!$this->isValidDate($startDate)) {
                throw new Exception('Invalid start date format');
            }
            
            $expireDate = isset($data['expireDate']) && !empty($data['expireDate']) ? $data['expireDate'] : null;
            if ($expireDate && !$this->isValidDate($expireDate)) {
                throw new Exception('Invalid expire date format');
            }
            
            // Validate that expire date is after start date if provided
            if ($expireDate && strtotime($expireDate) <= strtotime($startDate)) {
                throw new Exception('Expire date must be after start date');
            }
            
            // Validate customer exists
            if (!$this->customerExists($customerId)) {
                throw new Exception('Customer does not exist');
            }
            
            // Prepare data
            $note = isset($data['note']) ? trim($data['note']) : '';
            $createdBy = isset($data['createdBy']) ? filter_var($data['createdBy'], FILTER_VALIDATE_INT) : 1;
            if ($createdBy === false || $createdBy <= 0) {
                $createdBy = 1; // Default to user ID 1
            }
            
            // Insert contract
            $query = "INSERT INTO contracts (
                        customer_id, 
                        start_date, 
                        expire_date, 
                        payment, 
                        note, 
                        created_by_user, 
                        created_at
                      ) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->connection->prepare($query);
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
            }
            
            $stmt->bind_param('issdsi', $customerId, $startDate, $expireDate, $payment, $note, $createdBy);
            
            if (!$stmt->execute()) {
                throw new Exception('Execute failed: ' . $stmt->error);
            }
            
            $insertId = $this->connection->insert_id;
            $stmt->close();
            
            // Return the newly created contract
            $newContract = $this->getContractById($insertId);
            // Log the successful contract creation
            $this->logUserAction(
                $createdBy,
                'CREATE',
                'contracts',
                $insertId,
                'Created new contract for customer ID: ' . $customerId,
                [
                    'customer_id' => $customerId,
                    'start_date' => $startDate,
                    'expire_date' => $expireDate,
                    'payment' => $payment,
                    'note' => $note,
                    'created_by' => $createdBy
                ]
            );
            return [
                'success' => true,
                'data' => $newContract,
                'message' => 'Contract created successfully',
                'insertId' => $insertId
            ];
            
        } catch (Exception $e) {
            // 
            if (isset($createdBy)) {
                $this->logUserAction(
                    $createdBy,
                    'CREATE',
                    'contracts',
                    null,
                    'Error creating contract for customer ID: ' . ($customerId ?? 'unknown'),
                    null,
                    null,
                    'FAILED',
                    $e->getMessage()
                );
            }
            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to create contract: ' . $e->getMessage()
            ];
        }
    }
    
    private function customerExists($customerId) {
        try {
            $query = "SELECT COUNT(*) as count FROM customers WHERE id = ?";
            $stmt = $this->connection->prepare($query);
            
            if (!$stmt) {
                return false;
            }
            
            $stmt->bind_param('i', $customerId);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            return $row['count'] > 0;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function getContractById($id) {
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
                return null;
            }
            
            $stmt->bind_param('i', $id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $contract = $result->fetch_assoc();
            $stmt->close();
            
            if ($contract) {
                return [
                    'id' => (int)$contract['id'],
                    'customerId' => (int)$contract['customerId'],
                    'startDate' => $contract['startDate'] ?? '',
                    'expireDate' => $contract['expireDate'] ?? '',
                    'payment' => (float)$contract['payment'],
                    'note' => $contract['note'] ?? '',
                    'createdBy' => (int)$contract['createdBy'],
                    'createdAt' => $contract['createdAt'] ?? ''
                ];
            }
            
            return null;
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    private function isValidDate($date) {
        if (empty($date)) {
            return false;
        }
        
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    private function logUserAction($userId, $actionType, $tableName, $recordId, $description, $newValues = null, $oldValues = null, $status = 'SUCCESS', $errorMessage = null) {
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
}

// Set content type and CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
    
    // Initialize contract inserter
    $contractInserter = new ContractInserter($db);
    
    // Insert contract
    $result = $contractInserter->insertContract($data);
    
    // Set appropriate HTTP status code
    if ($result['success']) {
        http_response_code(201); // Created
    } else {
        http_response_code(400); // Bad Request
    }
    
    // Return JSON response
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'data' => null,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

?>
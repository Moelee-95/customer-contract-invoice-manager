<?php

require_once '../../db_connect.php';

class ContractUpdater {
    private $connection;
    
    public function __construct($db) {
        $this->connection = $db;
    }
    
    public function updateContract($id, $contractData) {
        try {
            // Validate required fields
            $requiredFields = ['customerId', 'startDate', 'expireDate', 'payment'];
            foreach ($requiredFields as $field) {
                if (!isset($contractData[$field]) || empty($contractData[$field])) {
                    return [
                        'success' => false,
                        'message' => "Missing required field: $field"
                    ];
                }
            }
            
            // Validate data types
            if (!is_numeric($contractData['customerId'])) {
                return [
                    'success' => false,
                    'message' => 'Customer ID must be a number'
                ];
            }
            
            if (!is_numeric($contractData['payment'])) {
                return [
                    'success' => false,
                    'message' => 'Payment must be a number'
                ];
            }
            
            if (!is_numeric($contractData['createdBy'])) {
                return [
                    'success' => false,
                    'message' => 'Created By must be a number'
                ];
            }
            
            // Validate dates
            if (!$this->isValidDate($contractData['startDate'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid start date format'
                ];
            }
            
            if (!$this->isValidDate($contractData['expireDate'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid expire date format'
                ];
            }
            
            // Check if contract exists
            $checkQuery = "SELECT id FROM contracts WHERE id = ?";
            $checkStmt = $this->connection->prepare($checkQuery);
            
            if (!$checkStmt) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
            }
            
            $checkStmt->bind_param('i', $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows === 0) {
                $checkStmt->close();
                return [
                    'success' => false,
                    'message' => 'Contract not found'
                ];
            }
            
            $checkStmt->close();
            
            // Check if customer exists
            $customerCheckQuery = "SELECT id FROM customers WHERE id = ?";
            $customerCheckStmt = $this->connection->prepare($customerCheckQuery);
            
            if (!$customerCheckStmt) {
                throw new Exception('Customer check prepare failed: ' . $this->connection->error);
            }
            
            $customerId = (int)$contractData['customerId'];
            $customerCheckStmt->bind_param('i', $customerId);
            $customerCheckStmt->execute();
            $customerResult = $customerCheckStmt->get_result();
            
            if ($customerResult->num_rows === 0) {
                $customerCheckStmt->close();
                return [
                    'success' => false,
                    'message' => 'Selected customer does not exist'
                ];
            }
            
            $customerCheckStmt->close();
            
            // Prepare update query
            $updateQuery = "UPDATE contracts SET 
                           customer_id = ?, 
                           start_date = ?, 
                           expire_date = ?, 
                           payment = ?, 
                           note = ?, 
                           created_by_user = ?,
                           updated_at = CURRENT_TIMESTAMP
                           WHERE id = ?";
            
            $stmt = $this->connection->prepare($updateQuery);
            
            if (!$stmt) {
                throw new Exception('Update prepare failed: ' . $this->connection->error);
            }
            
            // Bind parameters
            $customerId = (int)$contractData['customerId'];
            $startDate = $contractData['startDate'];
            $expireDate = $contractData['expireDate'];
            $payment = (float)$contractData['payment'];
            $note = $contractData['note'] ?? '';
            $createdBy = (int)$contractData['createdBy'];
            
            $stmt->bind_param('issdsii', 
                $customerId, 
                $startDate, 
                $expireDate, 
                $payment, 
                $note, 
                $createdBy, 
                $id
            );
            
            // Execute update
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $stmt->close();
                    
                    return [
                        'success' => true,
                        'message' => 'Contract updated successfully',
                        'contractId' => $id
                    ];
                } else {
                    $stmt->close();
                    return [
                        'success' => false,
                        'message' => 'No changes were made to the contract'
                    ];
                }
            } else {
                $error = $stmt->error;
                $stmt->close();
                throw new Exception('Execute failed: ' . $error);
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    private function isValidDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow PUT requests
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only PUT requests are supported.'
    ]);
    exit();
}

try {
    // Get contract ID from URL parameter
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Contract ID is required'
        ]);
        exit();
    }
    
    $contractId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($contractId === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid contract ID provided'
        ]);
        exit();
    }
    
    // Get JSON input
    $input = file_get_contents('php://input');
    $contractData = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON data provided'
        ]);
        exit();
    }
    
    if (empty($contractData)) {
        echo json_encode([
            'success' => false,
            'message' => 'No data provided'
        ]);
        exit();
    }
    
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Failed to connect to database');
    }
    
    // Initialize contract updater and update contract
    $contractUpdater = new ContractUpdater($db);
    $result = $contractUpdater->updateContract($contractId, $contractData);
    
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
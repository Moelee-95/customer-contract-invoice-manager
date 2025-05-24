<?php

require_once '../../db_connect.php';

class ContractDeleter {
    private $connection;
    
    public function __construct($db) {
        $this->connection = $db;
    }
    
    public function deleteContract($id) {
        try {
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
            
            // Start transaction for data integrity
            $this->connection->begin_transaction();
            
            try {
                // Delete the contract
                $deleteQuery = "DELETE FROM contracts WHERE id = ?";
                $deleteStmt = $this->connection->prepare($deleteQuery);
                
                if (!$deleteStmt) {
                    throw new Exception('Delete prepare failed: ' . $this->connection->error);
                }
                
                $deleteStmt->bind_param('i', $id);
                
                if ($deleteStmt->execute()) {
                    if ($deleteStmt->affected_rows > 0) {
                        // Commit the transaction
                        $this->connection->commit();
                        $deleteStmt->close();
                        
                        return [
                            'success' => true,
                            'message' => 'Contract deleted successfully',
                            'contractId' => $id
                        ];
                    } else {
                        // Rollback transaction
                        $this->connection->rollback();
                        $deleteStmt->close();
                        
                        return [
                            'success' => false,
                            'message' => 'Contract could not be deleted'
                        ];
                    }
                } else {
                    // Rollback transaction
                    $this->connection->rollback();
                    $error = $deleteStmt->error;
                    $deleteStmt->close();
                    throw new Exception('Delete execute failed: ' . $error);
                }
                
            } catch (Exception $e) {
                // Rollback transaction on any error
                $this->connection->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    public function deleteContractsByCustomerId($customerId) {
        try {
            // Check if customer has contracts
            $checkQuery = "SELECT COUNT(*) as contract_count FROM contracts WHERE customer_id = ?";
            $checkStmt = $this->connection->prepare($checkQuery);
            
            if (!$checkStmt) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
            }
            
            $checkStmt->bind_param('i', $customerId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $row = $result->fetch_assoc();
            $contractCount = $row['contract_count'];
            
            $checkStmt->close();
            
            if ($contractCount == 0) {
                return [
                    'success' => true,
                    'message' => 'No contracts found for this customer',
                    'deletedCount' => 0
                ];
            }
            
            // Start transaction
            $this->connection->begin_transaction();
            
            try {
                // Delete all contracts for the customer
                $deleteQuery = "DELETE FROM contracts WHERE customer_id = ?";
                $deleteStmt = $this->connection->prepare($deleteQuery);
                
                if (!$deleteStmt) {
                    throw new Exception('Delete prepare failed: ' . $this->connection->error);
                }
                
                $deleteStmt->bind_param('i', $customerId);
                
                if ($deleteStmt->execute()) {
                    $deletedCount = $deleteStmt->affected_rows;
                    
                    // Commit the transaction
                    $this->connection->commit();
                    $deleteStmt->close();
                    
                    return [
                        'success' => true,
                        'message' => "Successfully deleted $deletedCount contract(s) for customer",
                        'deletedCount' => $deletedCount
                    ];
                } else {
                    // Rollback transaction
                    $this->connection->rollback();
                    $error = $deleteStmt->error;
                    $deleteStmt->close();
                    throw new Exception('Delete execute failed: ' . $error);
                }
                
            } catch (Exception $e) {
                // Rollback transaction on any error
                $this->connection->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
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
    
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Failed to connect to database');
    }
    
    // Initialize contract deleter and delete contract
    $contractDeleter = new ContractDeleter($db);
    $result = $contractDeleter->deleteContract($contractId);
    
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
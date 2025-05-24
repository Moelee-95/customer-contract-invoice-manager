<?php

require_once 'session.php';
require_once '../../db_connect.php';

class CustomerUpdater {
    private $connection;
    
    public function __construct($db) {
        $this->connection = $db;
    }
    
    public function updateCustomer($customerId, $customerData) {
        try {
            // Validate customer ID
            if (empty($customerId) || !is_numeric($customerId)) {
                return [
                    'success' => false,
                    'message' => 'Invalid customer ID'
                ];
            }
            
            // Check if customer exists
            if (!$this->customerExists($customerId)) {
                return [
                    'success' => false,
                    'message' => 'Customer not found'
                ];
            }
            
            // Validate required fields
            if (empty($customerData['name'])) {
                return [
                    'success' => false,
                    'message' => 'Customer name is required'
                ];
            }
            
            // Validate phone number format if provided
            if (!empty($customerData['phone']) && !preg_match('/^[\d\-\+\(\)\s]+$/', $customerData['phone'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid phone number format'
                ];
            }
            
            // Handle start date - convert empty string to NULL for database
            $startDate = null;
            if (!empty($customerData['startDate'])) {
                // Validate date format
                $date = DateTime::createFromFormat('Y-m-d', $customerData['startDate']);
                if (!$date || $date->format('Y-m-d') !== $customerData['startDate']) {
                    return [
                        'success' => false,
                        'message' => 'Invalid start date format. Use YYYY-MM-DD'
                    ];
                }
                $startDate = $customerData['startDate'];
            }
            
            // Validate createdBy (must be a valid user ID)
            if (empty($customerData['createdBy']) || !is_numeric($customerData['createdBy'])) {
                $customerData['createdBy'] = 1; // Default to user ID 1
            }
            
            // Check for duplicate name/phone (excluding current customer)
            if ($this->duplicateExists($customerId, $customerData['name'], $customerData['phone'] ?? null)) {
                return [
                    'success' => false,
                    'message' => 'A customer with this name or phone number already exists'
                ];
            }

            // Get old customer data before update for audit logging
            $oldCustomerData = $this->getCustomerById($customerId);
            
            // Prepare the update query
            $query = "UPDATE customers SET 
                        name = ?, 
                        address = ?, 
                        phone_number = ?, 
                        free_trial = ?, 
                        start_date = ?, 
                        note = ?, 
                        created_by_user = ?,
                        updated_at = NOW()
                      WHERE id = ?";
            
            $stmt = $this->connection->prepare($query);
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
            }
            
            // Handle empty strings - convert to null for optional fields
            $address = !empty($customerData['address']) ? $customerData['address'] : null;
            $phone = !empty($customerData['phone']) ? $customerData['phone'] : null;
            $freeTrial = !empty($customerData['freeTrial']) ? $customerData['freeTrial'] : null;
            $note = !empty($customerData['note']) ? $customerData['note'] : null;
            
            // Bind parameters
            $stmt->bind_param('sssssisi',
                $customerData['name'],
                $address,
                $phone,
                $freeTrial,
                $startDate,
                $note,
                $customerData['createdBy'],
                $customerId
            );
            
            // Execute the statement
            if ($stmt->execute()) {
                $stmt->close();
                
                // Get the updated customer data
                $updatedCustomer = $this->getCustomerById($customerId);

                // Log the successful customer update
                $this->logUserAction(
                    $customerData['createdBy'],
                    'UPDATE',
                    'customers',
                    $customerId,
                    'Updated customer: ' . $customerData['name'],
                    [
                        'name' => $customerData['name'],
                        'address' => $address,
                        'phone' => $phone,
                        'free_trial' => $freeTrial,
                        'start_date' => $startDate,
                        'note' => $note,
                        'updated_by' => $customerData['createdBy']
                    ],
                    $oldCustomerData
                );
                
                // Return success with the updated customer data
                return [
                    'success' => true,
                    'data' => $updatedCustomer,
                    'message' => 'Customer updated successfully'
                ];
            } else {
                $error = $stmt->error;
                $stmt->close();
                // Log user Action
                $this->logUserAction(
                $customerData['createdBy'],
                'UPDATE',
                'customers',
                $customerId,
                'Failed to update customer: ' . $customerData['name'],
                null,
                $oldCustomerData,
                'FAILED',
                $error
            );
                throw new Exception('Execute failed: ' . $error);
            }
            
        } catch (Exception $e) {
            // Log the error
            if (isset($customerData['createdBy']) && isset($customerId)) {
                $this->logUserAction(
                    $customerData['createdBy'],
                    'UPDATE',
                    'customers',
                    $customerId,
                    'Error updating customer: ' . ($customerData['name'] ?? 'unknown'),
                    null,
                    $oldCustomerData ?? null,
                    'FAILED',
                    $e->getMessage()
                );
            }
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    public function customerExists($customerId) {
        try {
            $query = "SELECT COUNT(*) as count FROM customers WHERE id = ?";
            $stmt = $this->connection->prepare($query);
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
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
    
    public function duplicateExists($customerId, $name, $phone = null) {
        try {
            $query = "SELECT COUNT(*) as count FROM customers WHERE (name = ?";
            $params = [$name];
            $types = 's';
            
            // Also check phone if provided
            if (!empty($phone)) {
                $query .= " OR phone_number = ?";
                $params[] = $phone;
                $types .= 's';
            }
            
            $query .= ") AND id != ?";
            $params[] = $customerId;
            $types .= 'i';
            
            $stmt = $this->connection->prepare($query);
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
            }
            
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            return $row['count'] > 0;
            
        } catch (Exception $e) {
            // On error, assume duplicate might exist to be safe
            return true;
        }
    }
    
    public function getCustomerById($customerId) {
        try {
            $query = "SELECT 
                        id,
                        name,
                        address,
                        phone_number as phone,
                        free_trial as freeTrial,
                        start_date as startDate,
                        note,
                        created_by_user as createdBy,
                        created_at,
                        updated_at
                      FROM customers 
                      WHERE id = ?";
            
            $stmt = $this->connection->prepare($query);
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
            }
            
            $stmt->bind_param('i', $customerId);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $customer = $result->fetch_assoc();
            $stmt->close();
            
            // Convert null values to empty strings for frontend
            if ($customer) {
                $customer['address'] = $customer['address'] ?? '';
                $customer['phone'] = $customer['phone'] ?? '';
                $customer['freeTrial'] = $customer['freeTrial'] ?? '';
                $customer['startDate'] = $customer['startDate'] ?? '';
                $customer['note'] = $customer['note'] ?? '';
            }
            
            return $customer;
            
        } catch (Exception $e) {
            return null;
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
}

// Set content type to JSON and CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

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
    // Get customer ID from URL parameter
    $customerId = $_GET['id'] ?? null;
    
    if (empty($customerId)) {
        echo json_encode([
            'success' => false,
            'message' => 'Customer ID is required'
        ]);
        exit();
    }
    
    // Get the JSON input
    $input = file_get_contents('php://input');
    
    if (empty($input)) {
        echo json_encode([
            'success' => false,
            'message' => 'No data provided'
        ]);
        exit();
    }
    
    // Log the raw input for debugging
    error_log('Raw input for customer update: ' . $input);
    error_log('Customer ID: ' . $customerId);
    
    // Decode JSON data
    $customerData = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON data: ' . json_last_error_msg()
        ]);
        exit();
    }
    
    // Log the decoded data for debugging
    error_log('Decoded customer data: ' . print_r($customerData, true));
    
    // Validate required data
    if (!is_array($customerData)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid data format'
        ]);
        exit();
    }
    
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Failed to connect to database');
    }
    
    // Initialize customer updater
    $customerUpdater = new CustomerUpdater($db);
    
    // Update the customer
    $result = $customerUpdater->updateCustomer($customerId, $customerData);
    
    // Set appropriate HTTP status code
    if ($result['success']) {
        http_response_code(200); // OK
    } else {
        http_response_code(400); // Bad Request
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
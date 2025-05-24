<?php

require_once '../../db_connect.php';

class CustomerManager {
    private $connection;
    
    public function __construct($db) {
        $this->connection = $db;
    }
    
    public function insertCustomer($customerData) {
        try {
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
            
            // Prepare the insert query - use NULL for empty dates
            $query = "INSERT INTO customers (
                        name, 
                        address, 
                        phone_number, 
                        free_trial, 
                        start_date, 
                        note, 
                        created_by_user,
                        created_at
                      ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->connection->prepare($query);
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
            }
            
            // Handle empty strings - convert to null for optional fields
            $address = !empty($customerData['address']) ? $customerData['address'] : null;
            $phone = !empty($customerData['phone']) ? $customerData['phone'] : null;
            $freeTrial = !empty($customerData['freeTrial']) ? $customerData['freeTrial'] : null;
            $note = !empty($customerData['note']) ? $customerData['note'] : null;
            
            // Bind parameters - use 's' for date even if null (MySQL will handle it)
            $stmt->bind_param('sssssis',
                $customerData['name'],
                $address,
                $phone,
                $freeTrial,
                $startDate,
                $note,
                $customerData['createdBy']
            );
            
            // Execute the statement
            if ($stmt->execute()) {
                $newCustomerId = $this->connection->insert_id;
                $stmt->close();

                // Log the successful customer creation
                $this->logUserAction(
                    $customerData['createdBy'],
                    'CREATE',
                    'customers',
                    $newCustomerId,
                    'Created new customer: ' . $customerData['name'],
                    [
                        'name' => $customerData['name'],
                        'address' => $address,
                        'phone' => $phone,
                        'free_trial' => $freeTrial,
                        'start_date' => $startDate,
                        'note' => $note,
                        'created_by' => $customerData['createdBy']
                    ]
                );
                
                // Return success with the new customer ID
                return [
                    'success' => true,
                    'data' => [
                        'id' => $newCustomerId,
                        'name' => $customerData['name'],
                        'address' => $address ?? '',
                        'phone' => $phone ?? '',
                        'freeTrial' => $freeTrial ?? '',
                        'startDate' => $startDate ?? '',
                        'note' => $note ?? '',
                        'createdBy' => $customerData['createdBy']
                    ],
                    'message' => 'Customer created successfully'
                ];
            } else {
                $error = $stmt->error;
                $stmt->close();
                throw new Exception('Execute failed: ' . $error);
            }
            
        } catch (Exception $e) {
            // NEW CODE: Log the error
            if (isset($customerData['createdBy'])) {
                $this->logUserAction(
                    $customerData['createdBy'],
                    'CREATE',
                    'customers',
                    null,
                    'Error creating customer: ' . ($customerData['name'] ?? 'unknown'),
                    null,
                    null,
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
    
    public function customerExists($name, $phone = null) {
        try {
            $query = "SELECT COUNT(*) as count FROM customers WHERE name = ?";
            $params = [$name];
            $types = 's';
            
            // Also check phone if provided
            if (!empty($phone)) {
                $query .= " OR phone_number = ?";
                $params[] = $phone;
                $types .= 's';
            }
            
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
            // On error, assume customer might exist to be safe
            return true;
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

// Set content type to JSON and CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

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
    error_log('Raw input: ' . $input);
    
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
    error_log('Decoded data: ' . print_r($customerData, true));
    
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
    
    // Initialize customer manager
    $customerManager = new CustomerManager($db);
    
    // Insert the customer
    $result = $customerManager->insertCustomer($customerData);
    
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
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

?>
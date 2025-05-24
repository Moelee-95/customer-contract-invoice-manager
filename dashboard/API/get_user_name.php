<?php
require_once 'session.php';
require_once '../../db_connect.php';

class UserManager {
    private $connection;
    
    public function __construct($db) {
        $this->connection = $db;
    }
    
    public function getUserNameById($id) {
        try {
            $query = "SELECT username FROM users WHERE id = ?";
            
            $stmt = $this->connection->prepare($query);
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
            }
            
            $stmt->bind_param('i', $id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if ($user) {
                $stmt->close();
                
                return [
                    'success' => true,
                    'data' => $user['username'],
                    'message' => 'User name retrieved successfully'
                ];
            } else {
                $stmt->close();
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'User not found'
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
    
    public function getAllUserNames() {
        try {
            $query = "SELECT id, username FROM users ORDER BY username";
            
            $result = $this->connection->query($query);
            
            if (!$result) {
                throw new Exception('Query failed: ' . $this->connection->error);
            }
            
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[(int)$row['id']] = $row['username'];
            }
            
            $result->free();
            
            return [
                'success' => true,
                'data' => $users,
                'message' => 'User names retrieved successfully'
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
    
    // Initialize user manager
    $userManager = new UserManager($db);
    
    // Check if specific user ID is requested
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $userId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        
        if ($userId === false) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid user ID provided'
            ]);
            exit();
        }
        
        $result = $userManager->getUserNameById($userId);
    } else {
        // Get all user names as ID => name mapping
        $result = $userManager->getAllUserNames();
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
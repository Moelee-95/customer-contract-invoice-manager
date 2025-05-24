<?php
require_once 'session.php';
require_once '../../db_connect.php';

class LogsManager {
    private $connection;
    
    public function __construct($db) {
        $this->connection = $db;
    }
    
    public function getAllLogs($filters = []) {
        try {
            // Build the base query
            $query = "SELECT * FROM users_actions WHERE 1=1";
            $types = "";
            $params = [];
            
            // Filter by action type
            if (isset($filters['action_type']) && !empty($filters['action_type'])) {
                $query .= " AND action_type = ?";
                $types .= "s";
                $params[] = $filters['action_type'];
            }
            
            // Filter by table name
            if (isset($filters['table_name']) && !empty($filters['table_name'])) {
                $query .= " AND table_name = ?";
                $types .= "s";
                $params[] = $filters['table_name'];
            }
            
            // Order by most recent first with limit
            $query .= " ORDER BY created_at DESC LIMIT 100";
            
            $stmt = $this->connection->prepare($query);
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
            }
            
            // Bind parameters if any
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if (!$result) {
                throw new Exception('Query failed: ' . $this->connection->error);
            }
            
            $logs = [];
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
            
            $stmt->close();
            
            return [
                'success' => true,
                'data' => $logs,
                'message' => 'Logs retrieved successfully'
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
    
    // Initialize logs manager
    $logsManager = new LogsManager($db);
    
    // Prepare filters from GET parameters
    $filters = [];
    if (isset($_GET['action_type']) && !empty($_GET['action_type'])) {
        $filters['action_type'] = $_GET['action_type'];
    }
    if (isset($_GET['table_name']) && !empty($_GET['table_name'])) {
        $filters['table_name'] = $_GET['table_name'];
    }
    
    // Get logs with filters
    $result = $logsManager->getAllLogs($filters);
    
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
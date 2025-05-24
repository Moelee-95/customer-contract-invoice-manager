<?php
// Include database connection
require_once 'db_connect.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Only POST requests are accepted.']);
    exit;
}

// Log the request for debugging
error_log("Login attempt from IP: " . $_SERVER['REMOTE_ADDR']);

// Get raw input
$raw_input = file_get_contents('php://input');
error_log("Raw input: " . $raw_input);

// Try to decode JSON
$input = json_decode($raw_input, true);

// Check if JSON decoding failed
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit;
}

// Check if data was received
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'No data received']);
    exit;
}

// Get username and password
$username = isset($input['username']) ? trim($input['username']) : '';
$password = isset($input['password']) ? trim($input['password']) : '';

// Log received data (don't log password in production)
error_log("Received username: " . $username);

// Validate input
if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Username and password are required']);
    exit;
}

// DB connection and checking
$db = new Database();
$conn = $db->getConnection();

// Prepare statement to avoid SQL injection - FIXED: Use ? placeholders
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND password = ?");

if (!$stmt) {
    error_log("SQL prepare error: " . $conn->error);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
    exit;
}

// Bind parameters and execute
$stmt->bind_param("ss", $username, $password);
$stmt->execute();

// Fetch result
$result = $stmt->get_result();

// Check if user found
if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc(); 
    session_start();
    $_SESSION['user_id'] = $row['id'];
    $_SESSION['username'] = $username;
    $_SESSION['logged_in'] = true;
    error_log("Login successful for user: " . $username);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login successful! Redirecting to dashboard...',
        'redirect' => 'dashboard'
    ]);
    
} else {
    // Authentication failed
    error_log("Login failed for user: " . $username);
    
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid username or password. Please try again.'
    ]);
}

// Close statement
$stmt->close();
$conn->close();
?>
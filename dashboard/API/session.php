<?php
// Start the session
session_start();

// Check if user is NOT logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header("Location: ../../login.php");
    exit();
}

// Handle logout request
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    // Destroy all session data
    session_unset();
    session_destroy();
    
    // Redirect to login page
    header("Location: ../login.php");
    exit();
}

?>
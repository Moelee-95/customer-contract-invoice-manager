<?php
// Start the session
session_start();

// Check if user is NOT logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header("Location: ../login.php");
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

// Get user information from session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .header {
            background-color: #333;
            color: white;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .welcome-message {
            font-size: 1.2em;
        }
        .logout-btn {
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }
        .dashboard-content {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .user-info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="welcome-message">
            Welcome to Dashboard, <?php echo htmlspecialchars($username); ?>!
        </div>
        <a href="?logout=true" class="logout-btn" onclick="return confirm('Are you sure you want to logout?')">
            Logout
        </a>
    </div>

    <div class="dashboard-content">
        <h1>Dashboard</h1>
        
        <div class="user-info">
            <h3>User Information</h3>
            <p><strong>User ID:</strong> <?php echo htmlspecialchars($user_id); ?></p>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
            <p><strong>Login Status:</strong> Active</p>
        </div>

        <div class="main-content">
            <h3>Dashboard Content</h3>
            <p>This is your dashboard. You can add your dashboard content here.</p>
            
            <!-- Add your dashboard content here -->
            <div class="dashboard-widgets">
                <div style="background: #007bff; color: white; padding: 20px; margin: 10px 0; border-radius: 5px;">
                    <h4>Widget 1</h4>
                    <p>Your dashboard widget content goes here.</p>
                </div>
                
                <div style="background: #28a745; color: white; padding: 20px; margin: 10px 0; border-radius: 5px;">
                    <h4>Widget 2</h4>
                    <p>Another dashboard widget content goes here.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Optional: Add JavaScript functionality
        console.log('Dashboard loaded for user: <?php echo htmlspecialchars($username); ?>');
        
        // You can add more JavaScript functionality here
        function confirmLogout() {
            return confirm('Are you sure you want to logout?');
        }
    </script>
</body>
</html>
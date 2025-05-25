
<?php
// Start the session
session_start();

// Check if user is already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_id'])) {
    // User is already logged in, redirect to dashboard
    header("Location: dashboard/index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management System - Login</title>
    <!-- Login Css Style Code -->
    <link rel="stylesheet" href="login_style.css">

</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">CMS</div>
            <h1 class="app-title">Customer Management</h1>
            <p class="app-subtitle">Sign in to your account</p>
        </div>

        <div class="error-message" id="errorMessage">
            Invalid username or password. Please try again.
        </div>

        <form id="loginForm" method="post">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-input" 
                    placeholder="Enter your username"
                    required
                    autocomplete="username"
                >
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="password-container">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="toggle-password" id="togglePassword">
                        👁️
                    </button>
                </div>
            </div>

            <div class="remember-me">
                <input type="checkbox" id="rememberMe" name="rememberMe">
                <label for="rememberMe">Remember me</label>
            </div>

            <button type="submit" class="login-button" id="loginButton">
                Sign In
                <div class="loading" id="loadingSpinner">
                    <div class="spinner"></div>
                </div>
            </button>
        </form>

    </div>

    <script src="login_script.js"></script>
</body>
</html>
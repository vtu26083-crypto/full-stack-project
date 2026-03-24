<?php
/**
 * TrackWise User Login Page
 * Handles user authentication with secure password verification
 */

// Include database connection
require_once 'includes/db.php';

// Start secure session
startSecureSession();

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Initialize variables
$email = $password = "";
$email_err = $password_err = $login_err = "";

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if email is empty
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if (empty($email_err) && empty($password_err)) {
        
        // Prepare a select statement
        $sql = "SELECT id, name, email, password FROM users WHERE email = ?";
        $result = executeQuery($sql, [$email], "s");
        
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $row['password'])) {
                // Password is correct, start new session
                session_regenerate_id(true);
                
                // Store data in session variables
                $_SESSION["loggedin"] = true;
                $_SESSION["user_id"] = $row['id'];
                $_SESSION["user_name"] = $row['name'];
                $_SESSION["user_email"] = $row['email'];
                
                // Redirect user to dashboard
                redirect('dashboard.php');
            } else {
                // Password is not valid
                $login_err = "Invalid email or password.";
            }
        } else {
            // Email doesn't exist
            $login_err = "Invalid email or password.";
        }
    }
}

// Display registration success message if exists
$success_message = "";
if (isset($_SESSION['registration_success'])) {
    $success_message = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TrackWise</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>TrackWise</h1>
                    <p>Welcome back! Please login to your account.</p>
                </div>
                
                <?php 
                if (!empty($success_message)) {
                    showSuccess($success_message);
                }
                
                if (!empty($login_err)) {
                    showError($login_err);
                }
                ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="auth-form" novalidate>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" 
                               value="<?php echo sanitizeOutput($email); ?>" 
                               placeholder="Enter your email" required>
                        <span class="error-message"><?php echo sanitizeOutput($email_err); ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control" 
                               placeholder="Enter your password" required>
                        <span class="error-message"><?php echo sanitizeOutput($password_err); ?></span>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">Login</button>
                    </div>
                    
                    <div class="auth-footer">
                        <p>Don't have an account? <a href="register.php">Register here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="assets/js/script.js"></script>
</body>
</html>

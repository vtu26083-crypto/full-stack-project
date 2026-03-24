<?php
/**
 * TrackWise User Registration Page
 * Handles new user registration with validation and security
 */

// Include database connection
require_once 'includes/db.php';

// Start secure session
startSecureSession();

// Initialize variables
$name = $email = $password = $confirm_password = "";
$name_err = $email_err = $password_err = $confirm_password_err = "";

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate name
    if (empty(trim($_POST["name"]))) {
        $name_err = "Please enter your name.";
    } elseif (strlen(trim($_POST["name"])) < 2) {
        $name_err = "Name must be at least 2 characters.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } elseif (!validateEmail(trim($_POST["email"]))) {
        $email_err = "Please enter a valid email address.";
    } else {
        $email = trim($_POST["email"]);
        
        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $result = executeQuery($sql, [$email], "s");
        
        if ($result->num_rows > 0) {
            $email_err = "This email is already registered.";
        }
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must be at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Passwords did not match.";
        }
    }
    
    // Check input errors before inserting in database
    if (empty($name_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {
        
        // Hash password using bcrypt
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user into database
        $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
        $result = executeQuery($sql, [$name, $email, $hashed_password], "sss");
        
        if ($result) {
            // Get the user ID of the newly created user
            $user_id = getLastInsertId();
            
            // Create default categories for the new user
            $default_categories = [
                'Food & Dining',
                'Groceries',
                'Restaurants',
                'Coffee & Snacks',
                'Transportation',
                'Gas & Fuel',
                'Public Transit',
                'Shopping',
                'Clothing',
                'Electronics',
                'Home & Garden',
                'Entertainment',
                'Movies & Theater',
                'Games & Hobbies',
                'Bills & Utilities',
                'Rent/Mortgage',
                'Electricity',
                'Water',
                'Internet',
                'Phone',
                'Healthcare',
                'Doctor Visits',
                'Pharmacy',
                'Insurance',
                'Education',
                'Books & Supplies',
                'Courses',
                'Personal Care',
                'Fitness',
                'Travel',
                'Business Expenses',
                'Gifts & Donations',
                'Savings & Investments',
                'Other'
            ];
            
            foreach ($default_categories as $category) {
                $sql = "INSERT INTO categories (user_id, category_name) VALUES (?, ?)";
                executeQuery($sql, [$user_id, $category], "is");
            }
            
            // Redirect to login page with success message
            $_SESSION['registration_success'] = "Registration successful! Please login.";
            redirect("login.php");
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TrackWise</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>TrackWise</h1>
                    <p>Create your account</p>
                </div>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="auth-form" novalidate>
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" name="name" id="name" class="form-control" 
                               value="<?php echo sanitizeOutput($name); ?>" 
                               placeholder="Enter your full name">
                        <span class="error-message"><?php echo sanitizeOutput($name_err); ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" 
                               value="<?php echo sanitizeOutput($email); ?>" 
                               placeholder="Enter your email">
                        <span class="error-message"><?php echo sanitizeOutput($email_err); ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control" 
                               placeholder="Enter your password (min. 6 characters)">
                        <span class="error-message"><?php echo sanitizeOutput($password_err); ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                               placeholder="Confirm your password">
                        <span class="error-message"><?php echo sanitizeOutput($confirm_password_err); ?></span>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">Register</button>
                    </div>
                    
                    <div class="auth-footer">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="assets/js/script.js"></script>
</body>
</html>

<?php
/**
 * TrackWise Database Connection
 * Establishes secure connection to MySQL database using MySQLi
 */

// Database configuration for XAMPP
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'trackwise_db');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for full Unicode support
$conn->set_charset("utf8mb4");

/**
 * Function to execute prepared statements safely
 * @param string $sql - SQL query with placeholders
 * @param array $params - Parameters for prepared statement
 * @param string $types - Type string for parameters (i=integer, s=string, d=double, b=blob)
 * @return mysqli_result|bool - Query result
 */
function executeQuery($sql, $params = [], $types = "") {
    global $conn;
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    
    // Bind parameters if provided
    if (!empty($params)) {
        $bind_params = array();
        $bind_params[] = $types;
        
        for ($i = 0; $i < count($params); $i++) {
            $bind_params[] = &$params[$i];
        }
        
        call_user_func_array(array($stmt, 'bind_param'), $bind_params);
    }
    
    // Execute the statement
    $result = $stmt->execute();
    
    if ($result === false) {
        die("Execute failed: " . $stmt->error);
    }
    
    // Return result set for SELECT queries
    $result_set = $stmt->get_result();
    
    $stmt->close();
    
    return $result_set;
}

/**
 * Function to get the last inserted ID
 * @return int - Last inserted ID
 */
function getLastInsertId() {
    global $conn;
    return $conn->insert_id;
}

/**
 * Function to escape user input for additional security
 * @param string $input - User input
 * @return string - Escaped input
 */
function escapeInput($input) {
    global $conn;
    return $conn->real_escape_string($input);
}

/**
 * Function to start secure session
 */
function startSecureSession() {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    
    // Start session
    session_start();
    
    // Regenerate session ID to prevent session fixation
    if (!isset($_SESSION['initialized'])) {
        session_regenerate_id();
        $_SESSION['initialized'] = true;
    }
}

/**
 * Function to validate email format
 * @param string $email - Email to validate
 * @return bool - True if valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Function to sanitize output
 * @param string $output - Output to sanitize
 * @return string - Sanitized output
 */
function sanitizeOutput($output) {
    return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
}

/**
 * Function to check if user is logged in
 * @return bool - True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Function to get current user ID
 * @return int|null - Current user ID or null if not logged in
 */
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * Function to redirect to a page
 * @param string $url - URL to redirect to
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Function to display error message
 * @param string $message - Error message
 */
function showError($message) {
    echo "<div class='alert alert-danger'>$message</div>";
}

/**
 * Function to display success message
 * @param string $message - Success message
 */
function showSuccess($message) {
    echo "<div class='alert alert-success'>$message</div>";
}

?>

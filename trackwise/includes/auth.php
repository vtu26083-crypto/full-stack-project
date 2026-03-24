<?php
/**
 * TrackWise Authentication Check
 * Protects pages by requiring user authentication
 */

// Include database connection
require_once 'db.php';

// Start secure session if not already started
if (session_status() == PHP_SESSION_NONE) {
    startSecureSession();
}

// Check if user is logged in
if (!isLoggedIn()) {
    // Store the requested URL for redirect after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    // Redirect to login page
    redirect('../login.php');
}

/**
 * Function to check if current user owns the resource
 * @param int $resource_user_id - User ID of the resource owner
 * @return bool - True if current user owns the resource
 */
function isResourceOwner($resource_user_id) {
    $current_user_id = getCurrentUserId();
    return $current_user_id === $resource_user_id;
}

/**
 * Function to verify user access to expense
 * @param int $expense_id - Expense ID to check
 * @return bool - True if user has access
 */
function canAccessExpense($expense_id) {
    $current_user_id = getCurrentUserId();
    
    $sql = "SELECT user_id FROM expenses WHERE id = ?";
    $result = executeQuery($sql, [$expense_id], "i");
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        return $row['user_id'] == $current_user_id;
    }
    
    return false;
}

/**
 * Function to verify user access to category
 * @param int $category_id - Category ID to check
 * @return bool - True if user has access
 */
function canAccessCategory($category_id) {
    $current_user_id = getCurrentUserId();
    
    $sql = "SELECT user_id FROM categories WHERE id = ?";
    $result = executeQuery($sql, [$category_id], "i");
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        return $row['user_id'] == $current_user_id;
    }
    
    return false;
}

/**
 * Function to verify user access to budget
 * @param int $budget_id - Budget ID to check
 * @return bool - True if user has access
 */
function canAccessBudget($budget_id) {
    $current_user_id = getCurrentUserId();
    
    $sql = "SELECT user_id FROM budgets WHERE id = ?";
    $result = executeQuery($sql, [$budget_id], "i");
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        return $row['user_id'] == $current_user_id;
    }
    
    return false;
}

/**
 * Function to get user information
 * @return array|null - User information or null if not found
 */
function getCurrentUser() {
    $current_user_id = getCurrentUserId();
    
    if ($current_user_id) {
        $sql = "SELECT id, name, email, created_at FROM users WHERE id = ?";
        $result = executeQuery($sql, [$current_user_id], "i");
        
        if ($result->num_rows == 1) {
            return $result->fetch_assoc();
        }
    }
    
    return null;
}

/**
 * Function to update last activity timestamp
 */
function updateLastActivity() {
    $current_user_id = getCurrentUserId();
    
    if ($current_user_id) {
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Function to check session timeout
 * @param int $timeout_seconds - Session timeout in seconds (default: 30 minutes)
 */
function checkSessionTimeout($timeout_seconds = 1800) {
    // Check if last activity is set
    if (isset($_SESSION['last_activity'])) {
        // Calculate inactive time
        $inactive_time = time() - $_SESSION['last_activity'];
        
        // Check if session has expired
        if ($inactive_time > $timeout_seconds) {
            // Destroy session and redirect to login
            session_destroy();
            redirect('../login.php?timeout=1');
        }
    }
    
    // Update last activity
    updateLastActivity();
}

/**
 * Function to validate CSRF token
 * @param string $token - Token to validate
 * @return bool - True if token is valid
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Function to generate CSRF token
 * @return string - CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Function to require admin access (for future admin features)
 */
function requireAdmin() {
    // This is a placeholder for future admin functionality
    // Currently, all authenticated users have access to their own data
    if (!isLoggedIn()) {
        redirect('../login.php');
    }
}

// Check session timeout on every page load
checkSessionTimeout();

?>

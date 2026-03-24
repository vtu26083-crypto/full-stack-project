<?php
/**
 * TrackWise Logout Page
 * Handles user logout and session destruction
 */

// Include database connection
require_once 'includes/db.php';

// Start session
startSecureSession();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
redirect('login.php');
?>

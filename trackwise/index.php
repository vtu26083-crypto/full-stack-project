<?php
/**
 * TrackWise Landing Page
 * Entry point that redirects to login or dashboard based on authentication status
 */

// Include database connection
require_once 'includes/db.php';

// Start secure session
startSecureSession();

// Check if user is logged in
if (isLoggedIn()) {
    // User is logged in, redirect to dashboard
    redirect('dashboard.php');
} else {
    // User is not logged in, redirect to login page
    redirect('login.php');
}
?>

<?php
/**
 * TrackWise Header Component
 * Contains navigation, user menu, and page header
 */

// Check if user is logged in for header display
$user_logged_in = isLoggedIn();
$user_name = $user_logged_in ? $_SESSION['user_name'] : '';
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? sanitizeOutput($page_title) : 'TrackWise'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php if ($user_logged_in): ?>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>TrackWise</h1>
                <p>Expense Analytics</p>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                            <span class="nav-icon">📊</span>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="add_expense.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'add_expense.php' ? 'active' : ''; ?>">
                            <span class="nav-icon">➕</span>
                            Add Expense
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="view_expenses.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view_expenses.php' ? 'active' : ''; ?>">
                            <span class="nav-icon">📋</span>
                            View Expenses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="categories.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                            <span class="nav-icon">🏷️</span>
                            Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="budget.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'budget.php' ? 'active' : ''; ?>">
                            <span class="nav-icon">💰</span>
                            Budget
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo sanitizeOutput($user_name); ?></div>
                        <a href="logout.php" class="logout-link">Logout</a>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <header class="content-header">
                <div class="header-title">
                    <h1><?php echo isset($page_heading) ? sanitizeOutput($page_heading) : 'Dashboard'; ?></h1>
                    <p><?php echo isset($page_description) ? sanitizeOutput($page_description) : 'Manage your expenses efficiently'; ?></p>
                </div>
                
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="window.location.href='add_expense.php'">
                        <span>➕</span> Add Expense
                    </button>
                </div>
            </header>
            
            <div class="content-body">
    <?php else: ?>
        <!-- Simple header for non-authenticated pages -->
        <header class="simple-header">
            <div class="header-container">
                <h1>TrackWise</h1>
                <p>Relational Expense Analytics Platform</p>
            </div>
        </header>
        <div class="container">
    <?php endif; ?>

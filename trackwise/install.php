<?php
/**
 * One-Click TrackWise Installation
 * Just run this file and it will setup everything
 */

echo "<style>
body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5}
.container{max-width:600px;margin:50px auto;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1)}
.success{color:#27ae60;font-weight:bold}
.error{color:#e74c3c;font-weight:bold}
.info{color:#3498db}
.btn{background:#3498db;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;margin:10px 0}
.btn:hover{background:#2980b9}
</style>";

echo "<div class='container'>";
echo "<h1>🚀 TrackWise Auto Installer</h1>";

try {
    // Step 1: Connect to MySQL
    $conn = new mysqli('localhost', 'root', '');
    if ($conn->connect_error) {
        throw new Exception("MySQL connection failed. Make sure XAMPP MySQL is running.");
    }
    echo "<p class='success'>✅ Connected to MySQL</p>";

    // Step 2: Create database
    $conn->query("CREATE DATABASE IF NOT EXISTS trackwise_db");
    $conn->select_db("trackwise_db");
    echo "<p class='success'>✅ Database ready</p>";

    // Step 3: Create tables directly
    $tables = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            category_name VARCHAR(100) NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS expenses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            category_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            description TEXT,
            expense_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS budgets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            month INT NOT NULL,
            year INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_month_year (user_id, month, year)
        )"
    ];

    foreach ($tables as $sql) {
        if ($conn->query($sql)) {
            echo "<p class='success'>✅ Tables created</p>";
        }
    }

    // Step 4: Test connection
    $test = new mysqli('localhost', 'root', '', 'trackwise_db');
    if (!$test->connect_error) {
        echo "<p class='success'>✅ Database connection test passed</p>";
    }

    echo "<h2 class='success'>🎉 Installation Complete!</h2>";
    echo "<p class='info'>Your TrackWise application is ready to use.</p>";
    echo "<a href='register.php' class='btn'>👤 Register New User</a>";
    echo "<a href='index.php' class='btn'>🏠 Go to Application</a>";

} catch (Exception $e) {
    echo "<p class='error'>❌ " . $e->getMessage() . "</p>";
    echo "<h3>Fix Steps:</h3>";
    echo "<ol>";
    echo "<li>Start XAMPP Control Panel</li>";
    echo "<li>Start Apache service (click Start)</li>";
    echo "<li>Start MySQL service (click Start)</li>";
    echo "<li>Refresh this page</li>";
    echo "</ol>";
}

echo "</div>";
?>

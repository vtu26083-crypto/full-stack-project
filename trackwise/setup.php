<?php
/**
 * TrackWise Database Setup Script
 * This script will automatically create the database and tables
 */

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>TrackWise Database Setup</h1>";

// Database connection details
$host = 'localhost';
$user = 'root';
$pass = '';
$db_name = 'trackwise_db';

try {
    // Connect to MySQL without selecting database
    $conn = new mysqli($host, $user, $pass);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p style='color: green;'>✓ Connected to MySQL successfully</p>";
    
    // Create database if not exists
    $sql = "CREATE DATABASE IF NOT EXISTS $db_name";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ Database '$db_name' created or already exists</p>";
    } else {
        throw new Exception("Error creating database: " . $conn->error);
    }
    
    // Select the database
    $conn->select_db($db_name);
    echo "<p style='color: green;'>✓ Selected database '$db_name'</p>";
    
    // Read and execute the SQL file
    $sql_file = __DIR__ . '/database.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("Database schema file not found: $sql_file");
    }
    
    $sql = file_get_contents($sql_file);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            // Skip CREATE DATABASE and USE statements since we're already connected
            if (preg_match('/^(CREATE DATABASE|USE)/i', $statement)) {
                continue;
            }
            
            if ($conn->query($statement)) {
                echo "<p style='color: green;'>✓ Executed: " . substr($statement, 0, 50) . "...</p>";
            } else {
                echo "<p style='color: orange;'>⚠ Warning: " . $conn->error . " (Statement: " . substr($statement, 0, 50) . "...)</p>";
            }
        }
    }
    
    // Verify tables were created
    $result = $conn->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    echo "<h2>Database Setup Complete!</h2>";
    echo "<h3>Tables created:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li style='color: green;'>✓ $table</li>";
    }
    echo "</ul>";
    
    // Test database connection
    $test_conn = new mysqli($host, $user, $pass, $db_name);
    if ($test_conn->connect_error) {
        throw new Exception("Test connection failed: " . $test_conn->connect_error);
    }
    
    echo "<p style='color: green; font-weight: bold;'>✓ Database setup completed successfully!</p>";
    echo "<p>You can now <a href='register.php'>register a new user</a> or <a href='index.php'>go to the application</a></p>";
    
    $conn->close();
    $test_conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<h3>Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Make sure XAMPP is running (Apache and MySQL services)</li>";
    echo "<li>Check that MySQL is running on port 3306</li>";
    echo "<li>Verify MySQL username is 'root' with no password</li>";
    echo "<li>Try accessing phpMyAdmin: http://localhost/phpmyadmin</li>";
    echo "</ul>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #333; }
h2 { color: #666; }
h3 { color: #999; }
p { margin: 10px 0; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
a { color: #0066cc; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>

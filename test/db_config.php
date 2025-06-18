<?php
// Database configuration
$servername = "localhost"; // Or your database host
$username = "root";        // Your database username
$password = "";            // Your database password
$dbname = "lfsb"; // The database name you created

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Stop execution and display an error.
    // In a production environment, you might want to log this error instead of showing it to the user.
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Set charset to handle special characters
$conn->set_charset("utf8mb4");

// We don't close the connection here because other scripts will include this file
// and need the connection to be open. It will be closed in those scripts.
?>
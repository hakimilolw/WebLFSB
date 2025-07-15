<?php
$servername = "localhost"; // Or your database host
$username = "legasifu_test";        // Your database username
$password = "Hakimi2906@";            // Your database password
$dbname = "legasifu_wp785";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>
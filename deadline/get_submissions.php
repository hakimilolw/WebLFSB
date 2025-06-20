<?php
ini_set('display_errors', 1); 
error_reporting(E_ALL);       

header('Content-Type: application/json');
include 'db_config.php';

// Updated SQL to include the new 'client' and 'priority' fields
$sql = "SELECT id, fileName, deadlineDate, deadlineTime, inCharge, description, client, priority, isDone FROM submissions ORDER BY deadlineDate ASC, deadlineTime ASC";
$result = $conn->query($sql);

$submissions = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Convert isDone to a boolean for JavaScript
        $row['isDone'] = (bool)$row['isDone'];
        $submissions[] = $row;
    }
}

echo json_encode($submissions);

$conn->close();
?>
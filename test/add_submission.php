<?php
header('Content-Type: application/json');
include 'db_config.php';

// Prepare and bind
$stmt = $conn->prepare("INSERT INTO submissions (fileName, deadlineDate, deadlineTime, inCharge, description) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $fileName, $deadlineDate, $deadlineTime, $inCharge, $description);

// Set parameters from the POST request
$fileName = $_POST['fileName'];
$deadlineDate = $_POST['deadlineDate'];
$deadlineTime = $_POST['deadlineTime'];
$inCharge = $_POST['inCharge'];
$description = $_POST['description'];

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'New submission added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
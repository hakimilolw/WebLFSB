<?php
header('Content-Type: application/json');
include 'db_config.php';

// We get the input from the request body since we'll be sending JSON
$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'];
$isDone = $data['isDone'] ? 1 : 0; // Convert boolean to integer for SQL

// Prepare and bind
$stmt = $conn->prepare("UPDATE submissions SET isDone = ? WHERE id = ?");
$stmt->bind_param("ii", $isDone, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Submission status updated']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating record: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
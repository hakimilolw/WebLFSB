<?php
header('Content-Type: application/json');
include 'db_config.php';

// Prepare and bind - Added the 'priority' column
$stmt = $conn->prepare("INSERT INTO submissions (fileName, deadlineDate, deadlineTime, inCharge, description, client, priority) VALUES (?, ?, ?, ?, ?, ?, ?)");
// Updated bind_param to include one more string 's' for priority
$stmt->bind_param("sssssss", $fileName, $deadlineDate, $deadlineTime, $inCharge, $description, $client, $priority);

// Set parameters from the POST request
$fileName = $_POST['fileName'];
$deadlineDate = $_POST['deadlineDate'];
$deadlineTime = $_POST['deadlineTime'];
$inCharge = $_POST['inCharge'];
$description = $_POST['description'];
$client = $_POST['client'];


$priority = 'Medium'; 
// Check if the fileName starts with "TC" (case-insensitive)
if (strtoupper(substr(trim($fileName), 0, 2)) === 'TC') {
    $priority = 'High'; // If it does, override the priority to 'High'
}
// --- END NEW LOGIC ---

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'New submission added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
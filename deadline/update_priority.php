<?php
header('Content-Type: application/json');
include 'db_config.php';

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? null;
$priority = $data['priority'] ?? null;

// Basic validation
if (!$id || !$priority) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Missing ID or priority.']);
    exit;
}

// Validate priority value to ensure it's one of the allowed ENUM values
$allowed_priorities = ['High', 'Medium', 'Low'];
if (!in_array($priority, $allowed_priorities)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid priority value.']);
    exit;
}


// Prepare and bind
$stmt = $conn->prepare("UPDATE submissions SET priority = ? WHERE id = ?");
$stmt->bind_param("si", $priority, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Submission priority updated']);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Error updating record: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
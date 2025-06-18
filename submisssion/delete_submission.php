<?php
// Set the content type to application/json for API-like responses
header('Content-Type: application/json');

// Database connection details
// IMPORTANT: Replace with your actual database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lfsb"; // <-- Make sure to set your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // If connection fails, return a server error
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Get the posted data
// Since the fetch request sends JSON, we need to read the raw input stream
$data = json_decode(file_get_contents('php://input'), true);

// Validate the input
if (!isset($data['id']) || !is_numeric($data['id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid or missing submission ID.']);
    exit();
}

$submissionId = (int)$data['id'];

// Prepare and bind the SQL statement to prevent SQL injection
$stmt = $conn->prepare("DELETE FROM submissions WHERE id = ?");
$stmt->bind_param("i", $submissionId);

// Execute the statement and check for success
if ($stmt->execute()) {
    // Check if any row was actually deleted
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Submission deleted successfully.']);
    } else {
        // No rows affected could mean the ID was not found
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'message' => 'Submission not found.']);
    }
} else {
    // If execution fails, return a server error
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error deleting submission: ' . $stmt->error]);
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>
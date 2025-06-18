<?php
// Add these lines for debugging if you face a blank page or 500 error
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

include 'config.php'; // Ensure you have your DB credentials in this file

header("Content-Type: application/json");

// Function to generate a UUID (v4) for shareable links
function guuidv4() {
    if (function_exists('random_bytes')) {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? $_GET['path'] : '';

switch ($method) {
    case 'GET':
        if ($path == 'items') {
            // This query remains the same, it gets the LATEST progress for the main table view
            $sql = "SELECT i.*, p.progress FROM items i
                    LEFT JOIN (
                        SELECT p1.item_primary_id, p1.progress FROM progress p1
                        INNER JOIN (
                            SELECT item_primary_id, MAX(CONCAT(date, ' ', time)) as max_datetime
                            FROM progress GROUP BY item_primary_id
                        ) p2 ON p1.item_primary_id = p2.item_primary_id AND CONCAT(p1.date, ' ', p1.time) = p2.max_datetime
                    ) p ON i.id = p.item_primary_id ORDER BY i.id DESC";
            $result = $conn->query($sql);
            $items = array();
            while($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
            echo json_encode($items);
        }
        // *** NEW ENDPOINT TO GET ALL PROGRESS FOR ONE ITEM ***
        elseif (strpos($path, 'progress/') === 0) {
            $id = substr($path, strlen('progress/'));
            $stmt = $conn->prepare("SELECT progress, description, date, time FROM progress WHERE item_primary_id = ? ORDER BY date DESC, time DESC");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $progress_history = [];
            while ($row = $result->fetch_assoc()) {
                $progress_history[] = $row;
            }
            $stmt->close();
            echo json_encode($progress_history);
        }
        break;

    // POST, PUT, and DELETE cases remain unchanged from the previous version
    case 'POST':
        if ($path == 'addItem') {
            $data = json_decode(file_get_contents('php://input'), true);
            $shareable_id = guuidv4();
            $stmt = $conn->prepare("INSERT INTO items (shareable_id, item_name, item_id, receipient, eta) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $shareable_id, $data['itemName'], $data['itemId'], $data['receipient'], $data['eta']);
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(array("message" => "Item added successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("error" => "Error adding item: " . $stmt->error));
            }
            $stmt->close();
        }
        break;

    case 'PUT':
        if (strpos($path, 'editItem/') === 0) {
            $id = substr($path, strlen('editItem/'));
            $data = json_decode(file_get_contents('php://input'), true);
            $conn->begin_transaction();
            try {
                $stmt_item = $conn->prepare("UPDATE items SET item_name = ?, item_id = ?, receipient = ?, eta = ? WHERE id = ?");
                $stmt_item->bind_param("ssssi", $data['itemName'], $data['itemId'], $data['receipient'], $data['eta'], $id);
                $stmt_item->execute();
                $stmt_item->close();

                $stmt_progress = $conn->prepare("INSERT INTO progress (item_primary_id, progress, description, date, time) VALUES (?, ?, ?, ?, ?)");
                $stmt_progress->bind_param("issss", $id, $data['progress'], $data['description'], $data['date'], $data['time']);
                $stmt_progress->execute();
                $stmt_progress->close();
                
                $conn->commit();
                echo json_encode(array("message" => "Item updated successfully"));
            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                http_response_code(500);
                echo json_encode(array("error" => "Database error: " . $exception->getMessage()));
            }
        }
        break;

    case 'DELETE':
        if (strpos($path, 'deleteItem/') === 0) {
            $id = substr($path, strlen('deleteItem/'));
            $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo json_encode(array("message" => "Item deleted successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("error" => "Error deleting item: " . $stmt->error));
            }
            $stmt->close();
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("error" => "Method not allowed"));
        break;
}

$conn->close();
?>
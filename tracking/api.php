<?php
// Add these lines for debugging if you face a blank page or 500 error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php'; // Ensure you have your DB credentials in this file

header("Content-Type: application/json");

// --- NEW: Check for DB connection immediately ---
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

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

// --- NEW: Wrap the entire logic in a try-catch block for robust error handling ---
try {
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
            elseif (strpos($path, 'progress/') === 0) {
                $id = substr($path, strlen('progress/'));
                $stmt = $conn->prepare("SELECT progress, description, date, time, image_path FROM progress WHERE item_primary_id = ? ORDER BY date DESC, time DESC");
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
            // --- UPDATED: ENDPOINT FOR ADMIN PAGE WITH ERROR HANDLING ---
            elseif ($path == 'items_with_full_progress') {
                $items_sql = "SELECT * FROM items ORDER BY id DESC";
                $items_result = $conn->query($items_sql);

                if ($items_result === false) {
                    throw new Exception("Failed to fetch items: " . $conn->error);
                }

                $items = [];
                while ($item_row = $items_result->fetch_assoc()) {
                    // CORRECTED: Select `id` and alias it as `progress_id`
                    $progress_sql = $conn->prepare("SELECT id as progress_id, progress, description, date, time, image_path FROM progress WHERE item_primary_id = ? ORDER BY date DESC, time DESC");
                    
                    if ($progress_sql === false) {
                        throw new Exception("Failed to prepare progress statement: " . $conn->error);
                    }

                    $progress_sql->bind_param("i", $item_row['id']);
                    
                    if (!$progress_sql->execute()) {
                        throw new Exception("Failed to execute progress query: " . $progress_sql->error);
                    }

                    $progress_result = $progress_sql->get_result();
                    $item_row['progress_history'] = [];
                    while ($progress_row = $progress_result->fetch_assoc()) {
                        $item_row['progress_history'][] = $progress_row;
                    }
                    $progress_sql->close();
                    $items[] = $item_row;
                }
                echo json_encode($items);
            }
            elseif (strpos($path, 'item/') === 0) {
                $id = substr($path, strlen('item/'));
                $stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                echo json_encode($result->fetch_assoc());
                $stmt->close();
            }
            elseif (strpos($path, 'progress_entry/') === 0) {
                $id = substr($path, strlen('progress_entry/'));
                // CORRECTED: Select from `id` and alias it as `progress_id`
                $stmt = $conn->prepare("SELECT id as progress_id, progress, description, date, time FROM progress WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $progress_data = $result->fetch_assoc();
                echo json_encode($progress_data);
                $stmt->close();
            }
            // --- END: NEW ENDPOINTS FOR ADMIN PAGE ---
            break;

        case 'POST':
            if ($path == 'addItem') {
                $data = json_decode(file_get_contents('php://input'), true);
                $shareable_id = guuidv4(); 
                $stmt = $conn->prepare("INSERT INTO items (shareable_id, item_name, item_id, client, eta) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $shareable_id, $data['itemName'], $data['itemId'], $data['client'], $data['eta']);
                if ($stmt->execute()) {
                    http_response_code(201);
                    echo json_encode(array("message" => "Item added successfully"));
                } else {
                    throw new Exception("Error adding item: " . $stmt->error);
                }
                $stmt->close();
            }
            elseif (strpos($path, 'updateItem/') === 0) {
                $id = substr($path, strlen('updateItem/'));
                $data = $_POST;
                $image_path = null;
                if (isset($_FILES['progressImage']) && $_FILES['progressImage']['error'] == 0) {
                    $upload_dir = 'uploads/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    $file_name = uniqid() . '-' . basename($_FILES['progressImage']['name']);
                    $target_file = $upload_dir . $file_name;
                    if (move_uploaded_file($_FILES['progressImage']['tmp_name'], $target_file)) {
                        $image_path = $target_file;
                    }
                }
                $conn->begin_transaction();
                try {
                    $stmt_item = $conn->prepare("UPDATE items SET item_name = ?, item_id = ?, client = ?, eta = ? WHERE id = ?");
                    $stmt_item->bind_param("ssssi", $data['itemName'], $data['itemId'], $data['client'], $data['eta'], $id);
                    $stmt_item->execute();
                    $stmt_item->close();
                    $stmt_progress = $conn->prepare("INSERT INTO progress (item_primary_id, progress, description, date, time, image_path) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt_progress->bind_param("isssss", $id, $data['progress'], $data['description'], $data['date'], $data['time'], $image_path);
                    $stmt_progress->execute();
                    $stmt_progress->close();
                    $conn->commit();
                    echo json_encode(array("message" => "Item updated successfully"));
                } catch (mysqli_sql_exception $exception) {
                    $conn->rollback();
                    if ($image_path && file_exists($image_path)) { unlink($image_path); }
                    throw $exception;
                }
            }
            // --- NEW: ENDPOINTS FOR ADMIN PAGE ---
            elseif (strpos($path, 'updateItemDetails/') === 0) {
                $id = substr($path, strlen('updateItemDetails/'));
                $data = json_decode(file_get_contents('php://input'), true);
                $stmt = $conn->prepare("UPDATE items SET item_name = ?, item_id = ?, client = ?, eta = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $data['itemName'], $data['itemId'], $data['client'], $data['eta'], $id);
                if ($stmt->execute()) {
                    echo json_encode(array("message" => "Item details updated successfully."));
                } else {
                    throw new Exception("Failed to update item details.");
                }
                $stmt->close();
            }
            elseif (strpos($path, 'addProgressToItem/') === 0) {
                $id = substr($path, strlen('addProgressToItem/'));
                $data = $_POST;
                $image_path = null;
                if (isset($_FILES['progressImage']) && $_FILES['progressImage']['error'] == 0) {
                    $upload_dir = 'uploads/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    $file_name = uniqid() . '-' . basename($_FILES['progressImage']['name']);
                    $target_file = $upload_dir . $file_name;
                    if (move_uploaded_file($_FILES['progressImage']['tmp_name'], $target_file)) {
                        $image_path = $target_file;
                    }
                }
                $stmt = $conn->prepare("INSERT INTO progress (item_primary_id, progress, description, date, time, image_path) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $id, $data['progress'], $data['description'], $data['date'], $data['time'], $image_path);
                if ($stmt->execute()) {
                    echo json_encode(array("message" => "Progress added successfully."));
                } else {
                    throw new Exception("Failed to add progress.");
                }
                $stmt->close();
            }
            elseif (strpos($path, 'updateProgressEntry/') === 0) {
                $id = substr($path, strlen('updateProgressEntry/'));
                $data = json_decode(file_get_contents('php://input'), true);
                // CORRECTED: Update based on `id`
                $stmt = $conn->prepare("UPDATE progress SET progress = ?, description = ?, date = ?, time = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $data['progress'], $data['description'], $data['date'], $data['time'], $id);
                if ($stmt->execute()) {
                    echo json_encode(array("message" => "Progress entry updated successfully."));
                } else {
                    throw new Exception("Failed to update progress entry.");
                }
                $stmt->close();
            }
            // --- END: NEW ENDPOINTS FOR ADMIN PAGE ---
            break;

        case 'DELETE':
            if (strpos($path, 'deleteItem/') === 0) {
                $id = substr($path, strlen('deleteItem/'));
                $conn->begin_transaction();
                try {
                    // First delete associated progress entries
                    $stmt_progress = $conn->prepare("DELETE FROM progress WHERE item_primary_id = ?");
                    $stmt_progress->bind_param("i", $id);
                    $stmt_progress->execute();
                    $stmt_progress->close();

                    // Then delete the item itself
                    $stmt_item = $conn->prepare("DELETE FROM items WHERE id = ?");
                    $stmt_item->bind_param("i", $id);
                    $stmt_item->execute();
                    $stmt_item->close();

                    $conn->commit();
                    echo json_encode(array("message" => "Item and all its progress deleted successfully"));
                } catch (mysqli_sql_exception $exception) {
                    $conn->rollback();
                    throw $exception;
                }
            }
            // --- NEW: ENDPOINT FOR ADMIN PAGE ---
            elseif (strpos($path, 'deleteProgressEntry/') === 0) {
                $id = substr($path, strlen('deleteProgressEntry/'));
                // CORRECTED: Delete based on `id`
                $stmt = $conn->prepare("DELETE FROM progress WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    echo json_encode(array("message" => "Progress entry deleted successfully."));
                } else {
                    throw new Exception("Failed to delete progress entry.");
                }
                $stmt->close();
            }
            // --- END: NEW ENDPOINT FOR ADMIN PAGE ---
            break;

        default:
            http_response_code(405);
            echo json_encode(array("error" => "Method not allowed"));
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "An unexpected error occurred.",
        "message" => $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ]);
}

$conn->close();
?>

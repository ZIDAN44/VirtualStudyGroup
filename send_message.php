<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = intval($_POST['group_id'] ?? 0);
    $user_id = intval($_POST['user_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if (!$group_id || !$user_id || empty($message)) {
        echo json_encode(["status" => "error", "message" => "Invalid input."]);
        exit();
    }

    try {
        $stmt = $conn->prepare("INSERT INTO messages (group_id, user_id, message_content, timestamp) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $group_id, $user_id, $message);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            throw new Exception("Failed to log the message.");
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    } finally {
        $stmt->close();
        $conn->close();
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
?>

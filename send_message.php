<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = intval($_POST['group_id']);
    $user_id = intval($_POST['user_id']);
    $message = trim($_POST['message']);

    if (empty($message)) {
        echo "Message cannot be empty.";
        exit();
    }

    try {
        $stmt = $conn->prepare("INSERT INTO messages (group_id, user_id, message_content, timestamp) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $group_id, $user_id, $message);

        if ($stmt->execute()) {
            echo "Message sent successfully.";
        } else {
            echo "Error sending message.";
        }
    } catch (Exception $e) {
        echo "Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    } finally {
        $stmt->close();
        $conn->close();
    }
}
?>

<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $group_id = $_POST['group_id'];
    $user_id = $_POST['user_id'];
    $message = $_POST['message'];

    // Insert the message into the database
    $stmt = $conn->prepare("INSERT INTO messages (group_id, user_id, message_content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $group_id, $user_id, $message);

    if ($stmt->execute()) {
        echo "Message sent successfully.";
    } else {
        echo "Error sending message.";
    }

    $stmt->close();
    $conn->close();
}
?>

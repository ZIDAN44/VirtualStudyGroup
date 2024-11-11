<?php
include 'config.php';

$group_id = $_GET['group_id'] ?? null;

if ($group_id) {
    $stmt = $conn->prepare("SELECT messages.message_content, messages.timestamp, users.username 
                            FROM messages 
                            JOIN users ON messages.user_id = users.user_id 
                            WHERE messages.group_id = ? 
                            ORDER BY messages.timestamp ASC");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        echo "<p><strong>" . htmlspecialchars($row['username']) . ":</strong> " . htmlspecialchars($row['message_content']) . " <small>(" . $row['timestamp'] . ")</small></p>";
    }

    $stmt->close();
}
$conn->close();
?>

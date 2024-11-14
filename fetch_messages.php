<?php
session_start();
include 'config.php';

$group_id = $_GET['group_id'] ?? null;

if (!$group_id) {
    echo "Group not specified.";
    exit();
}

// Fetch both chat messages and resources, ordered by their timestamp
$stmt = $conn->prepare("
    SELECT 'message' AS type, m.message_id AS id, u.username, m.message_content AS content, NULL AS path, m.timestamp, NULL AS deleted
    FROM messages m
    JOIN users u ON m.user_id = u.user_id
    WHERE m.group_id = ?
    UNION ALL
    SELECT 'resource' AS type, r.resource_id AS id, u.username, r.file_name AS content, r.file_path AS path, r.upload_time AS timestamp, r.deleted
    FROM resources r
    JOIN users u ON r.uploaded_by = u.user_id
    WHERE r.group_id = ?
    ORDER BY timestamp ASC
");
$stmt->bind_param("ii", $group_id, $group_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if ($row['type'] === 'message') {
        echo "<div><strong>" . htmlspecialchars($row['username']) . ":</strong> " . htmlspecialchars($row['content']) . " <small>(" . $row['timestamp'] . ")</small></div>";
    } elseif ($row['type'] === 'resource') {
        if ($row['deleted']) {
            echo "<div><strong>" . htmlspecialchars($row['username']) . ":</strong> <em>(!) This file was deleted!</em> <small>(" . $row['timestamp'] . ")</small></div>";
        } else {
            echo "<div><strong>" . htmlspecialchars($row['username']) . ":</strong> <a href='" . htmlspecialchars($row['path']) . "' target='_blank'>" . htmlspecialchars($row['content']) . "</a> <small>(" . $row['timestamp'] . ")</small>";
            echo "<form action='delete_resource.php' method='POST' style='display:inline;'>
                    <input type='hidden' name='resource_id' value='" . $row['id'] . "'>
                    <input type='hidden' name='group_id' value='" . $group_id . "'>
                    <button type='submit'>Delete</button>
                  </form></div>";
        }
    }
}

$stmt->close();
$conn->close();
?>

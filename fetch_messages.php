<?php
session_start();
include 'config.php';

$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;

if (!$group_id) {
    echo "Group not specified.";
    exit();
}

// Fetch group name to dynamically build the file path
$group_stmt = $conn->prepare("SELECT group_name FROM groups WHERE group_id = ?");
$group_stmt->bind_param("i", $group_id);
$group_stmt->execute();
$group_stmt->bind_result($group_name);
if (!$group_stmt->fetch()) {
    echo "Group not found.";
    exit();
}
$group_stmt->close();

// Sanitize the group name for file paths
$sanitizedGroupName = preg_replace('/[^A-Za-z0-9]/', '', $group_name);

// Query to fetch messages and resources
$query = "
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
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $group_id, $group_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $type = $row['type'];
    $username = htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8');
    $content = htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8');
    $timestamp = htmlspecialchars($row['timestamp'], ENT_QUOTES, 'UTF-8');
    $path = $row['path'] ? htmlspecialchars($row['path'], ENT_QUOTES, 'UTF-8') : null;
    $id = htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8');
    $deleted = $row['deleted'];

    if ($type === 'message') {
        // Display chat message
        echo "<div><strong>{$username}:</strong> {$content} <small>({$timestamp})</small></div>";
    } elseif ($type === 'resource') {
        // Construct file path dynamically
        $fileUrl = "{$minioHost}/{$minioBucketName}/{$sanitizedGroupName}_{$group_id}/res/{$path}";

        // Display resource with optional delete button
        if ($deleted) {
            echo "<div><strong>{$username}:</strong> <em>(!) This file was deleted!</em> <small>({$timestamp})</small></div>";
        } else {
            echo "<div><strong>{$username}:</strong> 
                    <a href='{$fileUrl}' target='_blank'>{$content}</a> 
                    <small>({$timestamp})</small>
                  </div>
                  <form action='delete_resource.php' method='POST' style='display:inline;'>
                      <input type='hidden' name='resource_id' value='{$id}'>
                      <input type='hidden' name='group_id' value='{$group_id}'>
                      <button type='submit'>Delete</button>
                  </form>";
        }
    }
}

$stmt->close();
$conn->close();
?>

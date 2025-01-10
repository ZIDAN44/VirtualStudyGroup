<?php
session_start();
include 'config.php';

$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;

if (!$group_id) {
    echo json_encode(["error" => "Group not specified."]);
    exit();
}

try {
    // Fetch the group name
    $group_stmt = $conn->prepare("SELECT group_name FROM groups WHERE group_id = ?");
    $group_stmt->bind_param("i", $group_id);
    $group_stmt->execute();
    $group_stmt->bind_result($group_name);

    if (!$group_stmt->fetch()) {
        throw new Exception("Group not found.");
    }
    $group_stmt->close();

    // Prepare file URL base
    $sanitizedGroupName = preg_replace('/[^A-Za-z0-9]/', '', $group_name);
    $fileUrlBase = "{$minioHost}/{$minioBucketName}/{$sanitizedGroupName}_{$group_id}/res/";

    // Fetch messages and resources
    $query = "
        SELECT 'message' AS type, u.username, m.message_content AS content, m.timestamp, NULL AS file_path, NULL AS resource_id, NULL AS deleted 
        FROM messages m
        JOIN users u ON m.user_id = u.user_id
        WHERE m.group_id = ?
        UNION ALL
        SELECT 'resource' AS type, u.username, r.file_name AS content, r.upload_time AS timestamp, r.file_path, r.resource_id, r.deleted 
        FROM resources r
        JOIN users u ON r.uploaded_by = u.user_id
        WHERE r.group_id = ?
        ORDER BY timestamp ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $group_id, $group_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['type'] === 'resource') {
            if ($row['deleted'] == 1) {
                $row['content'] = "(!) This file was deleted!";
                $row['file_url'] = null;
            } else if (!empty($row['file_path'])) {
                $row['file_url'] = $fileUrlBase . $row['file_path'];
            }
        }
        unset($row['file_path']); // Remove raw file_path from the response
        $data[] = $row;
    }

    echo json_encode($data);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
} finally {
    $stmt->close();
    $conn->close();
}
?>

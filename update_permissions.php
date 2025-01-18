<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = intval($_POST['group_id']);
    $user_id = intval($_POST['user_id']);
    $can_edit_group_info = intval($_POST['can_edit_group_info']);
    $can_manage_join_requests = intval($_POST['can_manage_join_requests']);
    $can_manage_group_members = intval($_POST['can_manage_group_members']);
    $can_manage_ban_list = intval($_POST['can_manage_ban_list']);

    $stmt = $conn->prepare("
        INSERT INTO coadmin_permissions (group_id, user_id, can_edit_group_info, can_manage_join_requests, can_manage_group_members, can_manage_ban_list)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            can_edit_group_info = VALUES(can_edit_group_info),
            can_manage_join_requests = VALUES(can_manage_join_requests),
            can_manage_group_members = VALUES(can_manage_group_members),
            can_manage_ban_list = VALUES(can_manage_ban_list)
    ");
    $stmt->bind_param("iiiiii", $group_id, $user_id, $can_edit_group_info, $can_manage_join_requests, $can_manage_group_members, $can_manage_ban_list);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Permissions updated successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update permissions."]);
    }

    $stmt->close();
}
?>

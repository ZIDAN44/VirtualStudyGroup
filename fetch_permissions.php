<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = intval($_POST['group_id']);
    $user_id = intval($_POST['user_id']);

    $stmt = $conn->prepare("
        SELECT can_edit_group_info, can_manage_join_requests, can_manage_group_members, can_manage_ban_list 
        FROM coadmin_permissions 
        WHERE group_id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $group_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["status" => "success", "data" => $result->fetch_assoc()]);
    } else {
        echo json_encode(["status" => "error", "message" => "Permissions not found."]);
    }

    $stmt->close();
}
?>

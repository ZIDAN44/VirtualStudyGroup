<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "You are not logged in."]);
    exit();
}

$user_id = $_SESSION['user_id'];
$group_id = $_POST['group_id'] ?? null;

if (!$group_id) {
    echo json_encode(["status" => "error", "message" => "Group not specified."]);
    exit();
}

// Check if the user is an Admin
$member_check_stmt = $conn->prepare("SELECT role FROM group_members WHERE user_id = ? AND group_id = ?");
$member_check_stmt->bind_param("ii", $user_id, $group_id);
$member_check_stmt->execute();
$member_check_stmt->bind_result($user_role);
$member_check_stmt->fetch();
$member_check_stmt->close();

if ($user_role !== 'Admin') {
    echo json_encode(["status" => "error", "message" => "Only Admins can remove this group."]);
    exit();
}

// Set is_deleted to true
$update_group_stmt = $conn->prepare("UPDATE groups SET is_deleted = TRUE WHERE group_id = ?");
$update_group_stmt->bind_param("i", $group_id);

if ($update_group_stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Group marked as deleted successfully."]);
    exit();
} else {
    echo json_encode(["status" => "error", "message" => "Failed to mark group as deleted."]);
    exit();
}
?>

<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$group_id = $_GET['group_id'] ?? null;

if (!$group_id) {
    echo "Group not specified.";
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
    $_SESSION['error_message'] = "Only Admins can remove this group.";
    header("Location: group_settings.php?group_id=$group_id");
    exit();
}

// Delete group and related records
$delete_group_stmt = $conn->prepare("DELETE FROM groups WHERE group_id = ?");
$delete_group_stmt->bind_param("i", $group_id);

if ($delete_group_stmt->execute()) {
    $_SESSION['success_message'] = "Group removed successfully.";
    header("Location: dashboard.php");
    exit();
} else {
    $_SESSION['error_message'] = "Failed to remove group.";
    header("Location: group_settings.php?group_id=$group_id");
    exit();
}
?>

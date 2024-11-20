<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id_to_unban = $_POST['user_id'];
    $group_id = $_POST['group_id'];
    $user_id = $_SESSION['user_id'];

    // Check if the user is an Admin or a Co-Admin with the required permission
    $permissions_stmt = $conn->prepare("
        SELECT gm.role, cp.can_manage_ban_list 
        FROM group_members gm
        LEFT JOIN coadmin_permissions cp ON gm.user_id = cp.user_id AND gm.group_id = cp.group_id
        WHERE gm.user_id = ? AND gm.group_id = ?
    ");
    $permissions_stmt->bind_param("ii", $user_id, $group_id);
    $permissions_stmt->execute();
    $permissions_stmt->bind_result($user_role, $can_manage_ban_list);
    $permissions_stmt->fetch();
    $permissions_stmt->close();

    if ($user_role !== 'Admin' && (!$can_manage_ban_list || $user_role !== 'Co-Admin')) {
        $_SESSION['error_message'] = "You are not authorized to unban members.";
        header("Location: banned_members.php?group_id=$group_id");
        exit();
    }

    // Unban the member
    $unban_stmt = $conn->prepare("DELETE FROM banned_users WHERE user_id = ? AND group_id = ?");
    $unban_stmt->bind_param("ii", $user_id_to_unban, $group_id);

    if ($unban_stmt->execute()) {
        $_SESSION['success_message'] = "Member successfully unbanned.";
    } else {
        $_SESSION['error_message'] = "Failed to unban the member. Please try again.";
    }

    $unban_stmt->close();
    header("Location: banned_members.php?group_id=$group_id");
    exit();
}
?>

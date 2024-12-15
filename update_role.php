<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : null;
    $user_id_to_update = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
    $new_role = $_POST['role'] ?? null;
    $user_id = $_SESSION['user_id'];

    if (!$group_id || !$user_id_to_update || !$new_role) {
        echo "Invalid input.";
        exit();
    }

    // Check if the current user is an Admin
    $stmt = $conn->prepare("SELECT role FROM group_members WHERE user_id = ? AND group_id = ?");
    $stmt->bind_param("ii", $user_id, $group_id);
    $stmt->execute();
    $stmt->bind_result($current_role);
    $stmt->fetch();
    $stmt->close();

    if ($current_role !== 'Admin') {
        $_SESSION['error_message'] = "Only Admins can update roles.";
        header("Location: group_members.php?group_id=$group_id");
        exit();
    }

    $conn->begin_transaction();

    try {
        // Update the user's role
        $update_stmt = $conn->prepare("UPDATE group_members SET role = ? WHERE user_id = ? AND group_id = ?");
        $update_stmt->bind_param("sii", $new_role, $user_id_to_update, $group_id);
        $update_stmt->execute();
        $update_stmt->close();

        if ($new_role === 'Co-Admin') {
            // Grant Co-Admin permissions
            $permissions_stmt = $conn->prepare("
                INSERT INTO coadmin_permissions (group_id, user_id, can_edit_group_info, can_manage_join_requests, can_manage_group_members, can_manage_ban_list) 
                VALUES (?, ?, 1, 1, 1, 1) 
                ON DUPLICATE KEY UPDATE 
                can_edit_group_info = VALUES(can_edit_group_info), 
                can_manage_join_requests = VALUES(can_manage_join_requests),
                can_manage_group_members = VALUES(can_manage_group_members),
                can_manage_ban_list = VALUES(can_manage_ban_list)
            ");
            $permissions_stmt->bind_param("ii", $group_id, $user_id_to_update);
            $permissions_stmt->execute();
            $permissions_stmt->close();
        } else {
            // Remove Co-Admin permissions if demoted
            $remove_permissions_stmt = $conn->prepare("DELETE FROM coadmin_permissions WHERE user_id = ? AND group_id = ?");
            $remove_permissions_stmt->bind_param("ii", $user_id_to_update, $group_id);
            $remove_permissions_stmt->execute();
            $remove_permissions_stmt->close();
        }

        $conn->commit();
        $_SESSION['success_message'] = "Role updated successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Failed to update role: " . $e->getMessage();
    }

    header("Location: group_members.php?group_id=$group_id");
    exit();
}
?>

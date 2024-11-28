<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = $_POST['group_id'];
    $user_id_to_update = $_POST['user_id'];
    $new_role = $_POST['role'];
    $user_id = $_SESSION['user_id'];

    // Check if the current user is an Admin
    $stmt = $conn->prepare("SELECT role FROM group_members WHERE user_id = ? AND group_id = ?");
    $stmt->bind_param("ii", $user_id, $group_id);
    $stmt->execute();
    $stmt->bind_result($current_role);
    $stmt->fetch();
    $stmt->close();

    if ($current_role !== 'Admin') {
        $_SESSION['error_message'] = "Only Admins can update roles.";
        header("Location: group_settings.php?group_id=$group_id");
        exit();
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Update the user's role
        $update_stmt = $conn->prepare("UPDATE group_members SET role = ? WHERE user_id = ? AND group_id = ?");
        $update_stmt->bind_param("sii", $new_role, $user_id_to_update, $group_id);

        if (!$update_stmt->execute()) {
            throw new Exception("Error updating role.");
        }
        $update_stmt->close();

        // Manage Co-Admin permissions
        if ($new_role === 'Co-Admin') {
            // Set default permissions
            $can_edit_group_info = 0;
            $can_manage_join_requests = 1;
            $can_manage_group_members = 0;
            $can_manage_ban_list = 0;

            // Check if the user already exists in the coadmin_permissions table
            $check_stmt = $conn->prepare("SELECT 1 FROM coadmin_permissions WHERE user_id = ? AND group_id = ?");
            $check_stmt->bind_param("ii", $user_id_to_update, $group_id);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows === 0) {
                // Insert default permissions if not already present
                $insert_permissions_stmt = $conn->prepare("
                    INSERT INTO coadmin_permissions 
                    (group_id, user_id, can_edit_group_info, can_manage_join_requests, can_manage_group_members, can_manage_ban_list) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $insert_permissions_stmt->bind_param(
                    "iiiiii", 
                    $group_id, 
                    $user_id_to_update, 
                    $can_edit_group_info, 
                    $can_manage_join_requests, 
                    $can_manage_group_members, 
                    $can_manage_ban_list
                );

                if (!$insert_permissions_stmt->execute()) {
                    throw new Exception("Error adding Co-Admin permissions.");
                }
                $insert_permissions_stmt->close();
            }
            $check_stmt->close();
        } else {
            // Remove user from coadmin_permissions if not a Co-Admin
            $delete_permissions_stmt = $conn->prepare("DELETE FROM coadmin_permissions WHERE user_id = ? AND group_id = ?");
            $delete_permissions_stmt->bind_param("ii", $user_id_to_update, $group_id);

            if (!$delete_permissions_stmt->execute()) {
                throw new Exception("Error removing Co-Admin permissions.");
            }
            $delete_permissions_stmt->close();
        }

        // Commit transaction
        $conn->commit();
        $_SESSION['success_message'] = "Role updated successfully!";
    } catch (Exception $e) {
        // Rollback transaction on failure
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
    } finally {
        $conn->close();
    }

    header("Location: group_settings.php?group_id=$group_id");
    exit();
}
?>

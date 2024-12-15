<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : null;
    $user_id_to_kick = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
    $user_id = $_SESSION['user_id'];

    if (!$group_id || !$user_id_to_kick) {
        echo "Invalid input.";
        exit();
    }

    // Fetch roles
    $stmt = $conn->prepare("
        SELECT gm.role AS user_role, target.role AS target_role 
        FROM group_members gm 
        LEFT JOIN group_members target 
        ON target.user_id = ? AND target.group_id = gm.group_id
        WHERE gm.user_id = ? AND gm.group_id = ?
    ");
    $stmt->bind_param("iii", $user_id_to_kick, $user_id, $group_id);
    $stmt->execute();
    $stmt->bind_result($user_role, $target_role);
    $stmt->fetch();
    $stmt->close();

    // Permission checks
    if (
        ($user_role === 'Admin' && $user_id !== $user_id_to_kick) || 
        ($user_role === 'Co-Admin' && $target_role === 'Member')
    ) {
        $conn->begin_transaction();
        try {
            // Remove member
            $remove_stmt = $conn->prepare("DELETE FROM group_members WHERE user_id = ? AND group_id = ?");
            $remove_stmt->bind_param("ii", $user_id_to_kick, $group_id);
            $remove_stmt->execute();

            // Decrement member count
            $decrement_stmt = $conn->prepare("UPDATE groups SET current_members = current_members - 1 WHERE group_id = ?");
            $decrement_stmt->bind_param("i", $group_id);
            $decrement_stmt->execute();

            // Add to banned users
            $ban_stmt = $conn->prepare("
                INSERT INTO banned_users (user_id, group_id, banned_by) 
                VALUES (?, ?, ?)
            ");
            $ban_stmt->bind_param("iii", $user_id_to_kick, $group_id, $user_id);
            $ban_stmt->execute();

            $conn->commit();
            $_SESSION['success_message'] = "Member successfully removed and banned!";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Failed to remove and ban member: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "You do not have permission to perform this action.";
    }

    header("Location: group_members.php?group_id=$group_id");
    exit();
}
?>

<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = $_POST['group_id'];
    $user_id_to_kick = $_POST['user_id'];
    $user_id = $_SESSION['user_id'];

    // Fetch the role of the logged-in user
    $role_check_stmt = $conn->prepare("SELECT role FROM group_members WHERE user_id = ? AND group_id = ?");
    $role_check_stmt->bind_param("ii", $user_id, $group_id);
    $role_check_stmt->execute();
    $role_check_stmt->bind_result($user_role);
    $role_check_stmt->fetch();
    $role_check_stmt->close();

    // Fetch the role of the user being kicked
    $kick_check_stmt = $conn->prepare("SELECT role FROM group_members WHERE user_id = ? AND group_id = ?");
    $kick_check_stmt->bind_param("ii", $user_id_to_kick, $group_id);
    $kick_check_stmt->execute();
    $kick_check_stmt->bind_result($target_role);
    $kick_check_stmt->fetch();
    $kick_check_stmt->close();

    // Check permissions
    if (
        ($user_role === 'Admin' && $user_id !== $user_id_to_kick) || // Admin can kick anyone except themselves
        ($user_role === 'Co-Admin' && $target_role === 'Member') // Co-Admin can only kick Members
    ) {
        // Remove the user from the group
        $delete_stmt = $conn->prepare("DELETE FROM group_members WHERE user_id = ? AND group_id = ?");
        $delete_stmt->bind_param("ii", $user_id_to_kick, $group_id);

        if ($delete_stmt->execute()) {
            $_SESSION['success_message'] = "Member successfully kicked from the group.";
        } else {
            $_SESSION['error_message'] = "Failed to kick the member. Please try again.";
        }

        $delete_stmt->close();
    } else {
        $_SESSION['error_message'] = "You do not have permission to perform this action.";
    }

    header("Location: group_members.php?group_id=$group_id");
    exit();
}
?>

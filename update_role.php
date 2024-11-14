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

    // Update the user's role
    $update_stmt = $conn->prepare("UPDATE group_members SET role = ? WHERE user_id = ? AND group_id = ?");
    $update_stmt->bind_param("sii", $new_role, $user_id_to_update, $group_id);

    if ($update_stmt->execute()) {
        $_SESSION['success_message'] = "Role updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating role.";
    }

    $update_stmt->close();
    header("Location: group_settings.php?group_id=$group_id");
    exit();
}
?>

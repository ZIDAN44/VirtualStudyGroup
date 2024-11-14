<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if group_id is set
if (isset($_POST['group_id'])) {
    $group_id = $_POST['group_id'];
    $user_id = $_SESSION['user_id'];

    // Check if the user is already a member of this group
    $check_stmt = $conn->prepare("SELECT * FROM group_members WHERE user_id = ? AND group_id = ?");
    $check_stmt->bind_param("ii", $user_id, $group_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows == 0) {
        // Add the user as a Member
        $stmt = $conn->prepare("INSERT INTO group_members (user_id, group_id, role) VALUES (?, ?, 'Member')");
        $stmt->bind_param("ii", $user_id, $group_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "You have successfully joined the group!";
        } else {
            $_SESSION['error_message'] = "Error joining group. Please try again.";
        }

        $stmt->close();
    } else {
        // User is already a member
        $_SESSION['error_message'] = "You are already a member of this group.";
    }

    $check_stmt->close();
} else {
    $_SESSION['error_message'] = "Invalid group selection.";
}

$conn->close();
header("Location: join_group.php");
exit();
?>

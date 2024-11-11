<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if group_id is set in the POST request
if (isset($_POST['group_id'])) {
    $group_id = $_POST['group_id'];
    $user_id = $_SESSION['user_id'];

    // Remove the user from the group_members table
    $stmt = $conn->prepare("DELETE FROM group_members WHERE user_id = ? AND group_id = ?");
    $stmt->bind_param("ii", $user_id, $group_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "You have successfully left the group.";
    } else {
        $_SESSION['error_message'] = "Error leaving the group. Please try again.";
    }

    $stmt->close();
} else {
    $_SESSION['error_message'] = "Invalid group selection.";
}

$conn->close();

// Redirect to the dashboard
header("Location: dashboard.php");
exit();
?>

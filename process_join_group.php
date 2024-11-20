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

    // Check if the user is banned from this group
    $ban_check_stmt = $conn->prepare("SELECT * FROM banned_users WHERE user_id = ? AND group_id = ?");
    $ban_check_stmt->bind_param("ii", $user_id, $group_id);
    $ban_check_stmt->execute();
    $ban_result = $ban_check_stmt->get_result();

    if ($ban_result->num_rows > 0) {
        $_SESSION['error_message'] = "You are banned from joining this group.";
        $ban_check_stmt->close();
        header("Location: join_group.php");
        exit();
    }
    $ban_check_stmt->close();

    // Check if the user is already a member of this group
    $check_stmt = $conn->prepare("SELECT * FROM group_members WHERE user_id = ? AND group_id = ?");
    $check_stmt->bind_param("ii", $user_id, $group_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows == 0) {
        // Fetch the group joining rule
        $rule_stmt = $conn->prepare("SELECT join_rule FROM groups WHERE group_id = ?");
        $rule_stmt->bind_param("i", $group_id);
        $rule_stmt->execute();
        $rule_stmt->bind_result($join_rule);
        $rule_stmt->fetch();
        $rule_stmt->close();

        if ($join_rule === 'auto') {
            // Add the user as a Member directly
            $stmt = $conn->prepare("INSERT INTO group_members (user_id, group_id, role) VALUES (?, ?, 'Member')");
            $stmt->bind_param("ii", $user_id, $group_id);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "You have successfully joined the group!";
            } else {
                $_SESSION['error_message'] = "Error joining group. Please try again.";
            }

            $stmt->close();
        } elseif ($join_rule === 'manual') {
            // Add a join request for Admin approval
            $request_stmt = $conn->prepare("INSERT INTO join_requests (user_id, group_id) VALUES (?, ?)");
            $request_stmt->bind_param("ii", $user_id, $group_id);

            if ($request_stmt->execute()) {
                $_SESSION['success_message'] = "Your join request has been sent. Please wait for Admin approval.";
            } else {
                $_SESSION['error_message'] = "Error sending join request. Please try again.";
            }

            $request_stmt->close();
        } else {
            $_SESSION['error_message'] = "Invalid group join rule.";
        }
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

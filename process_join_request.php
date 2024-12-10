<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$request_id = $_POST['request_id'] ?? null;
$group_id = $_POST['group_id'] ?? null;
$action = $_POST['action'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$request_id || !$group_id || !$action) {
    echo "Invalid request.";
    exit();
}

// Check if the user is an Admin or Co-Admin
$role_check_stmt = $conn->prepare("SELECT role FROM group_members WHERE user_id = ? AND group_id = ?");
$role_check_stmt->bind_param("ii", $user_id, $group_id);
$role_check_stmt->execute();
$role_check_stmt->bind_result($user_role);
$role_check_stmt->fetch();
$role_check_stmt->close();

if ($user_role !== 'Admin' && $user_role !== 'Co-Admin') {
    $_SESSION['error_message'] = "You are not authorized to process join requests.";
    header("Location: manage_join_requests.php?group_id=$group_id");
    exit();
}

if ($action === 'approve') {
    // Approve the join request
    $approve_stmt = $conn->prepare(
        "UPDATE join_requests SET status = 'approved' WHERE request_id = ?"
    );
    $approve_stmt->bind_param("i", $request_id);
    $approve_stmt->execute();

    // Add user to group
    $add_stmt = $conn->prepare(
        "INSERT INTO group_members (user_id, group_id, role)
         SELECT user_id, group_id, 'Member' FROM join_requests WHERE request_id = ?"
    );
    $add_stmt->bind_param("i", $request_id);
    $add_stmt->execute();

    // Increment the current_members count
    $update_members_stmt = $conn->prepare("UPDATE groups SET current_members = current_members + 1 WHERE group_id = ?");
    $update_members_stmt->bind_param("i", $group_id);
    $update_members_stmt->execute();
    $update_members_stmt->close();

    $_SESSION['success_message'] = "Join request approved!";
} elseif ($action === 'reject') {
    // Reject the join request
    $reject_stmt = $conn->prepare(
        "UPDATE join_requests SET status = 'rejected' WHERE request_id = ?"
    );
    $reject_stmt->bind_param("i", $request_id);
    $reject_stmt->execute();

    $_SESSION['success_message'] = "Join request rejected!";
}

header("Location: manage_join_requests.php?group_id=$group_id");
exit();
?>

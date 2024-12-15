<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : null;
$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : null;
$action = $_POST['action'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$request_id || !$group_id || !$action) {
    echo "Invalid request.";
    exit();
}

// Check if the user is an Admin or has permission to process join requests
$role_check_stmt = $conn->prepare("
    SELECT gm.role, cp.can_manage_join_requests 
    FROM group_members gm
    LEFT JOIN coadmin_permissions cp 
    ON gm.user_id = cp.user_id AND gm.group_id = cp.group_id
    WHERE gm.user_id = ? AND gm.group_id = ?
");
$role_check_stmt->bind_param("ii", $user_id, $group_id);
$role_check_stmt->execute();
$role_check_stmt->bind_result($user_role, $can_manage_join_requests);
$role_check_stmt->fetch();
$role_check_stmt->close();

if ($user_role !== 'Admin' && !$can_manage_join_requests) {
    $_SESSION['error_message'] = "You are not authorized to process join requests.";
    header("Location: manage_join_requests.php?group_id=$group_id");
    exit();
}

if ($action === 'approve') {
    // Check current and max member counts
    $member_count_stmt = $conn->prepare("
        SELECT max_members, current_members 
        FROM groups 
        WHERE group_id = ?
    ");
    $member_count_stmt->bind_param("i", $group_id);
    $member_count_stmt->execute();
    $member_count_stmt->bind_result($max_members, $current_members);
    $member_count_stmt->fetch();
    $member_count_stmt->close();

    if ($current_members < $max_members || $max_members === null) {
        // Approve join request and add user to group
        $conn->begin_transaction();
        try {
            $approve_stmt = $conn->prepare("UPDATE join_requests SET status = 'approved' WHERE request_id = ?");
            $approve_stmt->bind_param("i", $request_id);
            $approve_stmt->execute();

            $add_stmt = $conn->prepare("
                INSERT INTO group_members (user_id, group_id, role) 
                SELECT user_id, group_id, 'Member' FROM join_requests WHERE request_id = ?
            ");
            $add_stmt->bind_param("i", $request_id);
            $add_stmt->execute();

            $update_members_stmt = $conn->prepare("UPDATE groups SET current_members = current_members + 1 WHERE group_id = ?");
            $update_members_stmt->bind_param("i", $group_id);
            $update_members_stmt->execute();

            $conn->commit();
            $_SESSION['success_message'] = "Join request approved!";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "An error occurred. Please try again.";
        }
    } else {
        $_SESSION['error_message'] = "Cannot approve request. Group member limit reached.";
    }
} elseif ($action === 'reject') {
    // Reject the join request
    $reject_stmt = $conn->prepare("UPDATE join_requests SET status = 'rejected' WHERE request_id = ?");
    $reject_stmt->bind_param("i", $request_id);
    $reject_stmt->execute();

    $_SESSION['success_message'] = "Join request rejected!";
}

header("Location: manage_join_requests.php?group_id=$group_id");
exit();
?>

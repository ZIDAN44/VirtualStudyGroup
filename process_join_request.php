<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "You are not logged in."]);
    exit();
}

$request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : null;
$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : null;
$action = $_POST['action'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$request_id || !$group_id || !$action) {
    echo json_encode(["status" => "error", "message" => "Invalid request."]);
    exit();
}

// Check permissions
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
    echo json_encode(["status" => "error", "message" => "You are not authorized to process join requests."]);
    exit();
}

try {
    if ($action === 'approve') {
        $conn->begin_transaction();

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
        echo json_encode(["status" => "success", "message" => "Join request approved!"]);
    } elseif ($action === 'reject') {
        $reject_stmt = $conn->prepare("UPDATE join_requests SET status = 'rejected' WHERE request_id = ?");
        $reject_stmt->bind_param("i", $request_id);
        $reject_stmt->execute();

        echo json_encode(["status" => "success", "message" => "Join request rejected!"]);
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => "An error occurred. Please try again."]);
}
?>

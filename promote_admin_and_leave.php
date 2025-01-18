<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['group_id'], $_POST['new_admin_id'])) {
    $group_id = intval($_POST['group_id']);
    $new_admin_id = intval($_POST['new_admin_id']);
    $user_id = $_SESSION['user_id'];

    $conn->begin_transaction();
    try {
        // Promote Co-Admin to Admin
        $promote_stmt = $conn->prepare("UPDATE group_members SET role = 'Admin' WHERE user_id = ? AND group_id = ?");
        $promote_stmt->bind_param("ii", $new_admin_id, $group_id);
        $promote_stmt->execute();
        $promote_stmt->close();

        // Remove the current Admin from the group
        $leave_stmt = $conn->prepare("DELETE FROM group_members WHERE user_id = ? AND group_id = ?");
        $leave_stmt->bind_param("ii", $user_id, $group_id);
        $leave_stmt->execute();
        $leave_stmt->close();

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "New Admin appointed successfully, and you have left the group."]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request."]);
    exit();
}
?>

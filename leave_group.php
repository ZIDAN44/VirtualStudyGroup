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

    // Fetch the user's role
    $role_stmt = $conn->prepare("SELECT role FROM group_members WHERE user_id = ? AND group_id = ?");
    $role_stmt->bind_param("ii", $user_id, $group_id);
    $role_stmt->execute();
    $role_stmt->bind_result($user_role);
    $role_stmt->fetch();
    $role_stmt->close();

    if ($user_role === 'Admin') {
        $coadmins_stmt = $conn->prepare("SELECT u.user_id, u.username 
                                         FROM group_members gm 
                                         JOIN users u ON gm.user_id = u.user_id 
                                         WHERE gm.group_id = ? AND gm.role = 'Co-Admin'");
        $coadmins_stmt->bind_param("i", $group_id);
        $coadmins_stmt->execute();
        $coadmins = $coadmins_stmt->get_result();
        $coadmins_stmt->close();

        if ($coadmins->num_rows > 0) {
            $_SESSION['group_id'] = $group_id;
            $_SESSION['coadmins'] = $coadmins->fetch_all(MYSQLI_ASSOC);

            header("Location: new_admin.php");
            exit();
        } else {
            $_SESSION['error_message'] = "No Co-Admins available to promote. You cannot leave the group as Admin.";
            header("Location: group_settings.php?group_id=$group_id");
            exit();
        }
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("DELETE FROM group_members WHERE user_id = ? AND group_id = ?");
            $stmt->bind_param("ii", $user_id, $group_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to leave the group. Please try again.");
            }
            $stmt->close();

            $conn->commit();
            $_SESSION['success_message'] = "You have successfully left the group.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = $e->getMessage();
        }

        header("Location: dashboard.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = "Invalid group selection.";
    header("Location: dashboard.php");
    exit();
}
?>

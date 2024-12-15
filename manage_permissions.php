<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;
$coadmin_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

if (!$group_id || !$coadmin_id) {
    $_SESSION['error_message'] = "Group or user not specified.";
    header("Location: dashboard.php");
    exit();
}

// Check if the user is an Admin
$role_check_stmt = $conn->prepare("SELECT role FROM group_members WHERE user_id = ? AND group_id = ?");
$role_check_stmt->bind_param("ii", $user_id, $group_id);
$role_check_stmt->execute();
$role_check_stmt->bind_result($user_role);
$role_check_stmt->fetch();
$role_check_stmt->close();

if ($user_role !== 'Admin') {
    $_SESSION['error_message'] = "You are not authorized to manage permissions.";
    header("Location: group_settings.php?group_id=$group_id");
    exit();
}

// Fetch existing permissions for the Co-Admin
$permissions_stmt = $conn->prepare("
    SELECT can_edit_group_info, can_manage_join_requests, can_manage_group_members, can_manage_ban_list 
    FROM coadmin_permissions 
    WHERE group_id = ? AND user_id = ?
");
$permissions_stmt->bind_param("ii", $group_id, $coadmin_id);
$permissions_stmt->execute();
$permissions_stmt->bind_result(
    $can_edit_group_info,
    $can_manage_join_requests,
    $can_manage_group_members,
    $can_manage_ban_list
);
if (!$permissions_stmt->fetch()) {
    // Default permissions if no entry exists
    $can_edit_group_info = $can_manage_join_requests = $can_manage_group_members = $can_manage_ban_list = 0;
}
$permissions_stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_info = isset($_POST['can_edit_group_info']) ? 1 : 0;
    $manage_requests = isset($_POST['can_manage_join_requests']) ? 1 : 0;
    $manage_members = isset($_POST['can_manage_group_members']) ? 1 : 0;
    $manage_bans = isset($_POST['can_manage_ban_list']) ? 1 : 0;

    // Insert or update permissions securely
    $upsert_stmt = $conn->prepare("
        INSERT INTO coadmin_permissions (group_id, user_id, can_edit_group_info, can_manage_join_requests, can_manage_group_members, can_manage_ban_list)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            can_edit_group_info = VALUES(can_edit_group_info),
            can_manage_join_requests = VALUES(can_manage_join_requests),
            can_manage_group_members = VALUES(can_manage_group_members),
            can_manage_ban_list = VALUES(can_manage_ban_list)
    ");
    $upsert_stmt->bind_param("iiiiii", $group_id, $coadmin_id, $edit_info, $manage_requests, $manage_members, $manage_bans);

    try {
        if ($upsert_stmt->execute()) {
            $_SESSION['success_message'] = "Permissions updated successfully!";
        } else {
            throw new Exception("Failed to update permissions.");
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
    $upsert_stmt->close();
    header("Location: group_settings.php?group_id=$group_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Permissions</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <h2>Manage Permissions for Co-Admin</h2>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <p style="color: green;"><?php echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success_message']); ?></p>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <p style="color: red;"><?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error_message']); ?></p>
    <?php endif; ?>

    <form action="manage_permissions.php?group_id=<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>&user_id=<?php echo htmlspecialchars($coadmin_id, ENT_QUOTES, 'UTF-8'); ?>" method="POST">
        <label>
            <input type="checkbox" name="can_edit_group_info" <?php echo $can_edit_group_info ? 'checked' : ''; ?>>
            Can Edit Group Information
        </label><br>
        <label>
            <input type="checkbox" name="can_manage_join_requests" <?php echo $can_manage_join_requests ? 'checked' : ''; ?>>
            Can Manage Join Requests
        </label><br>
        <label>
            <input type="checkbox" name="can_manage_group_members" <?php echo $can_manage_group_members ? 'checked' : ''; ?>>
            Can Manage Group Members
        </label><br>
        <label>
            <input type="checkbox" name="can_manage_ban_list" <?php echo $can_manage_ban_list ? 'checked' : ''; ?>>
            Can Manage Ban Lists
        </label><br>
        <button type="submit">Save Permissions</button>
    </form>

    <p><a href="group_settings.php?group_id=<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>">Back to Settings</a></p>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

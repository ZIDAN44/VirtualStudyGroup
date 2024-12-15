<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;

if (!$group_id) {
    echo "Group not specified.";
    exit();
}

// Check if the user is an Admin or a Co-Admin with permission
$permissions_stmt = $conn->prepare("
    SELECT gm.role, cp.can_manage_ban_list 
    FROM group_members gm
    LEFT JOIN coadmin_permissions cp 
    ON gm.user_id = cp.user_id AND gm.group_id = cp.group_id
    WHERE gm.user_id = ? AND gm.group_id = ?
");
$permissions_stmt->bind_param("ii", $user_id, $group_id);
$permissions_stmt->execute();
$permissions_stmt->bind_result($user_role, $can_manage_ban_list);
$permissions_stmt->fetch();
$permissions_stmt->close();

if ($user_role !== 'Admin' && (!$can_manage_ban_list || $user_role !== 'Co-Admin')) {
    $_SESSION['error_message'] = "You are not authorized to manage banned members.";
    header("Location: group_settings.php?group_id=$group_id");
    exit();
}

// Fetch banned members with information about who banned them
$banned_stmt = $conn->prepare("
    SELECT b.user_id, u.username AS banned_user, b.banned_at, b.banned_by, ub.username AS banned_by_user
    FROM banned_users b
    JOIN users u ON b.user_id = u.user_id
    JOIN users ub ON b.banned_by = ub.user_id
    WHERE b.group_id = ?
");
$banned_stmt->bind_param("i", $group_id);
$banned_stmt->execute();
$banned_members = $banned_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Banned Members</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <h2>Banned Members of Group</h2>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <p style="color: green;"><?php echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success_message']); ?></p>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <p style="color: red;"><?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error_message']); ?></p>
    <?php endif; ?>

    <?php if ($banned_members->num_rows > 0): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>User Name</th>
                    <th>Banned By</th>
                    <th>Banned At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($banned = $banned_members->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($banned['banned_user'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($banned['banned_by_user'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($banned['banned_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <form action="unban_member.php" method="POST" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($banned['user_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" onclick="return confirm('Are you sure you want to unban <?php echo htmlspecialchars($banned['banned_user'], ENT_QUOTES, 'UTF-8'); ?>?');">Unban</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No banned members in this group.</p>
    <?php endif; ?>

    <p><a href="group_settings.php?group_id=<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>">Back to Settings</a></p>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

<?php
$banned_stmt->close();
$conn->close();
?>

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

// Fetch the role of the logged-in user in this group
$user_role_stmt = $conn->prepare("SELECT role FROM group_members WHERE user_id = ? AND group_id = ?");
$user_role_stmt->bind_param("ii", $user_id, $group_id);
$user_role_stmt->execute();
$user_role_stmt->bind_result($user_role);
$user_role_stmt->fetch();
$user_role_stmt->close();

if (!$user_role) {
    echo "You are not a member of this group.";
    exit();
}

// Fetch group members and their roles
$members_stmt = $conn->prepare("
    SELECT u.username, gm.role, u.user_id 
    FROM group_members gm
    JOIN users u ON gm.user_id = u.user_id
    WHERE gm.group_id = ?
");
$members_stmt->bind_param("i", $group_id);
$members_stmt->execute();
$members = $members_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Group Members</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <h2>Manage Group Members</h2>

    <ul>
        <?php while ($member = $members->fetch_assoc()): ?>
            <li>
                <?php echo htmlspecialchars($member['username'], ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($member['role'], ENT_QUOTES, 'UTF-8') . ')'; ?>

                <!-- Admin-only actions: Promote/Demote -->
                <?php if ($user_role === 'Admin' && $member['role'] !== 'Admin' && $member['user_id'] !== $user_id): ?>
                    <form action="update_role.php" method="POST" style="display:inline;">
                        <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($member['user_id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <select name="role">
                            <option value="Co-Admin" <?php echo $member['role'] === 'Co-Admin' ? 'selected' : ''; ?>>Co-Admin</option>
                            <option value="Member" <?php echo $member['role'] === 'Member' ? 'selected' : ''; ?>>Member</option>
                        </select>
                        <button type="submit">Update Role</button>
                    </form>
                <?php endif; ?>

                <!-- Kick Member/Co-Admin -->
                <?php if (($user_role === 'Admin' || ($user_role === 'Co-Admin' && $member['role'] === 'Member')) && $member['user_id'] !== $user_id): ?>
                    <form action="kick_member.php" method="POST" style="display:inline;">
                        <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($member['user_id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" onclick="return confirm('Are you sure you want to kick <?php echo htmlspecialchars($member['username'], ENT_QUOTES, 'UTF-8'); ?>?');">Kick</button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endwhile; ?>
    </ul>

    <p><a href="group_settings.php?group_id=<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>">Back to Settings</a></p>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

<?php $members_stmt->close(); ?>

<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$group_id = $_GET['group_id'] ?? null;

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

// Fetch group members and their roles
$members_stmt = $conn->prepare("SELECT u.username, gm.role, u.user_id FROM group_members gm
                                JOIN users u ON gm.user_id = u.user_id
                                WHERE gm.group_id = ?");
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
                <?php echo htmlspecialchars($member['username']) . ' (' . htmlspecialchars($member['role']) . ')'; ?>

                <!-- Admin-only actions: Promote/Demote -->
                <?php if ($user_role === 'Admin' && $member['role'] !== 'Admin' && $member['user_id'] !== $user_id): ?>
                    <form action="update_role.php" method="POST" style="display:inline;">
                        <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                        <input type="hidden" name="user_id" value="<?php echo $member['user_id']; ?>">
                        <select name="role">
                            <option value="Co-Admin" <?php echo $member['role'] === 'Co-Admin' ? 'selected' : ''; ?>>Co-Admin</option>
                            <option value="Member" <?php echo $member['role'] === 'Member' ? 'selected' : ''; ?>>Member</option>
                        </select>
                        <button type="submit">Update Role</button>
                    </form>
                <?php endif; ?>

                <!-- Kick Member/Co-Admin -->
                <?php
                $can_manage_members = false;

                if ($user_role === 'Admin') {
                    $can_manage_members = true;
                } elseif ($user_role === 'Co-Admin') {
                    $permissions_stmt = $conn->prepare("
                        SELECT can_manage_group_members 
                        FROM coadmin_permissions 
                        WHERE group_id = ? AND user_id = ?
                    ");
                    $permissions_stmt->bind_param("ii", $group_id, $user_id);
                    $permissions_stmt->execute();
                    $permissions_stmt->bind_result($can_manage_group_members);
                    $permissions_stmt->fetch();
                    $permissions_stmt->close();

                    $can_manage_members = $can_manage_group_members && $member['role'] === 'Member';
                }

                if ($can_manage_members && $member['user_id'] !== $user_id): ?>
                    <form action="kick_member.php" method="POST" style="display:inline;">
                        <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                        <input type="hidden" name="user_id" value="<?php echo $member['user_id']; ?>">
                        <button type="submit" onclick="return confirm('Are you sure you want to kick <?php echo htmlspecialchars($member['username']); ?>?');">Kick</button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endwhile; ?>
    </ul>

    <p><a href="group_settings.php?group_id=<?php echo $group_id; ?>">Back to Settings</a></p>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

<?php $members_stmt->close(); ?>
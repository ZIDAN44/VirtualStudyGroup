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

// Check if the user is a member of this group
$member_check_stmt = $conn->prepare("SELECT role FROM group_members WHERE user_id = ? AND group_id = ?");
$member_check_stmt->bind_param("ii", $user_id, $group_id);
$member_check_stmt->execute();
$member_check_stmt->bind_result($user_role);
$member_check_stmt->fetch();
$member_check_stmt->close();

if (!$user_role) {
    $_SESSION['error_message'] = "You are not a member of this group.";
    header("Location: dashboard.php");
    exit();
}

// Fetch group details
$group_stmt = $conn->prepare("SELECT group_name, description, join_rule FROM groups WHERE group_id = ?");
$group_stmt->bind_param("i", $group_id);
$group_stmt->execute();
$group_stmt->bind_result($group_name, $description, $join_rule);
$group_stmt->fetch();
$group_stmt->close();

// Fetch permissions for the current user if Co-Admin
$coadmin_permissions = [];
if ($user_role === 'Co-Admin') {
    $permissions_stmt = $conn->prepare("
        SELECT can_edit_group_info, can_manage_join_requests, can_manage_group_members 
        FROM coadmin_permissions 
        WHERE group_id = ? AND user_id = ?
    ");
    $permissions_stmt->bind_param("ii", $group_id, $user_id);
    $permissions_stmt->execute();
    $permissions_result = $permissions_stmt->get_result();
    $coadmin_permissions = $permissions_result->fetch_assoc();
    $permissions_stmt->close();
}

// Fetch group members and their roles
$members_stmt = $conn->prepare("
    SELECT u.username, gm.role, gm.user_id 
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
    <title>Group Settings - <?php echo htmlspecialchars($group_name); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <h2>Group Settings - <?php echo htmlspecialchars($group_name); ?></h2>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <p style="color: green;"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <p style="color: red;"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
    <?php endif; ?>

    <!-- Group Details Section -->
    <h3>Group Details</h3>
    <p><strong>Group Name:</strong> <?php echo htmlspecialchars($group_name); ?></p>
    <p><strong>Description:</strong> <?php echo htmlspecialchars($description); ?></p>
    <p><strong>Join Rule:</strong> <?php echo $join_rule === 'auto' ? 'Anyone can join directly' : 'Admin approval required'; ?></p>

    <!-- Group Members Section (Visible to All Members) -->
    <h3>Group Members</h3>
    <ul>
        <?php while ($member = $members->fetch_assoc()): ?>
            <li>
                <?php echo htmlspecialchars($member['username']) . ' (' . htmlspecialchars($member['role']) . ')'; ?>
                <?php if ($user_role === 'Admin' && $member['role'] === 'Co-Admin'): ?>
                    <!-- Link to Manage Permissions for Co-Admins -->
                    <a href="manage_permissions.php?group_id=<?php echo $group_id; ?>&user_id=<?php echo $member['user_id']; ?>">Manage Permissions</a>
                <?php endif; ?>
            </li>
        <?php endwhile; ?>
    </ul>

    <!-- Admin Actions -->
    <?php if ($user_role === 'Admin'): ?>
        <h3>Admin Actions</h3>
        <ul>
            <li><a href="edit_group_info.php?group_id=<?php echo $group_id; ?>">Edit Group Information</a></li>
            <li><a href="manage_join_requests.php?group_id=<?php echo $group_id; ?>">Manage Join Requests</a></li>
            <li><a href="group_members.php?group_id=<?php echo $group_id; ?>">Manage Group Members</a></li>
            <li><a href="remove_group.php?group_id=<?php echo $group_id; ?>" onclick="return confirm('Are you sure you want to delete this group? This action cannot be undone.');">Remove Group</a></li>
        </ul>
    <?php endif; ?>

    <!-- Co-Admin Actions -->
    <?php if ($user_role === 'Co-Admin'): ?>
        <h3>Co-Admin Actions</h3>
        <ul>
            <?php if ($coadmin_permissions['can_edit_group_info']): ?>
                <li><a href="edit_group_info.php?group_id=<?php echo $group_id; ?>">Edit Group Information</a></li>
            <?php endif; ?>
            <?php if ($coadmin_permissions['can_manage_join_requests']): ?>
                <li><a href="manage_join_requests.php?group_id=<?php echo $group_id; ?>">Manage Join Requests</a></li>
            <?php endif; ?>
            <?php if ($coadmin_permissions['can_manage_group_members']): ?>
                <li><a href="group_members.php?group_id=<?php echo $group_id; ?>">Manage Group Members</a></li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>

    <p><a href="group.php?group_id=<?php echo $group_id; ?>">Back to Group</a></p>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

<?php
$members_stmt->close();
?>
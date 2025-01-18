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
    echo "Invalid or missing group ID.";
    exit();
}

// Check if the group is deleted
$group_check_stmt = $conn->prepare("
    SELECT is_deleted 
    FROM groups 
    WHERE group_id = ?
");
$group_check_stmt->bind_param("i", $group_id);
$group_check_stmt->execute();
$group_check_stmt->bind_result($is_deleted);
$group_check_stmt->fetch();
$group_check_stmt->close();

if ($is_deleted) {
    echo "<script>alert('This group has been removed.'); window.location.href = 'dashboard.php';</script>";
    exit();
}

// Check if the user is a member of this group and get their role
$member_check_stmt = $conn->prepare("
    SELECT role 
    FROM group_members 
    WHERE user_id = ? AND group_id = ?
");
$member_check_stmt->bind_param("ii", $user_id, $group_id);
$member_check_stmt->execute();
$member_check_stmt->bind_result($user_role);
$member_check_stmt->fetch();
$member_check_stmt->close();

if (!$user_role) {
    echo "<script>alert('You are not a member of this group.'); window.location.href = 'dashboard.php';</script>";
    exit();
}

// Fetch group name and picture
$group_stmt = $conn->prepare("
    SELECT group_name, group_picture 
    FROM groups 
    WHERE group_id = ?
");
$group_stmt->bind_param("i", $group_id);
$group_stmt->execute();
$group_stmt->bind_result($group_name, $group_picture);
$group_stmt->fetch();
$group_stmt->close();

$user_stmt = $conn->prepare("
    SELECT username 
    FROM users 
    WHERE user_id = ?
");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_stmt->bind_result($username);
$user_stmt->fetch();
$user_stmt->close();

if (!$username) {
    echo "User not found.";
    exit();
}

// Fallback to dummy image if group_picture is empty
$group_picture = !empty($group_picture)
    ? htmlspecialchars($group_picture, ENT_QUOTES, 'UTF-8')
    : htmlspecialchars($dummyGPImage, ENT_QUOTES, 'UTF-8');

// Fetch permissions for Co-Admin
$coadmin_permissions = [];
if ($user_role === 'Co-Admin') {
    $permissions_stmt = $conn->prepare("
        SELECT can_edit_group_info, can_manage_join_requests, can_manage_group_members, can_manage_ban_list 
        FROM coadmin_permissions 
        WHERE group_id = ? AND user_id = ?
    ");
    $permissions_stmt->bind_param("ii", $group_id, $user_id);
    $permissions_stmt->execute();
    $permissions_result = $permissions_stmt->get_result();
    $coadmin_permissions = $permissions_result->fetch_assoc();
    $permissions_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($group_name, ENT_QUOTES, 'UTF-8'); ?> - Virtual Study Group</title>
    <link rel="stylesheet" href="css/group.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" />

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <!-- Common JS (for showToast) -->
    <script src="js/common.js" defer></script>

    <!-- Group JS -->
    <script src="js/group.js" defer></script>

    <!-- Remove Group JS -->
    <script src="js/remove_group.js" defer></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Group Header Section -->
    <div class="group-header">
        <img src="<?php echo $group_picture; ?>" alt="<?php echo htmlspecialchars($group_name, ENT_QUOTES, 'UTF-8'); ?> Thumbnail" class="group-header-thumbnail">
        <h2>
            <a href="group_info.php?group_id=<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($group_name, ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </h2>

        <!-- App Drawer Toggle -->
        <button id="app-drawer-toggle">
            <i class="fas fa-bars"></i> Menu
        </button>
    </div>

    <!-- App Drawer -->
    <div id="app-drawer" class="hidden">
        <h3>Group Actions</h3>
        <ul>
            <li><a href="show_all_res.php?group_id=<?php echo $group_id; ?>">Show All Resources</a></li>
            <li><a href="all_group_members.php?group_id=<?php echo $group_id; ?>">Show All Members</a></li>
        </ul>
        <?php if ($user_role === 'Admin'): ?>
            <h3>Admin Actions</h3>
            <ul>
                <li><a href="edit_group_info.php?group_id=<?php echo $group_id; ?>">Edit Group Information</a></li>
                <li><a href="manage_join_requests.php?group_id=<?php echo $group_id; ?>">Manage Join Requests</a></li>
                <li><a href="group_members.php?group_id=<?php echo $group_id; ?>">Manage Group Members</a></li>
                <li><a href="manage_events.php?group_id=<?php echo $group_id; ?>">Manage Group Events</a></li>
                <li><a href="banned_members.php?group_id=<?php echo $group_id; ?>">Manage Banned Members</a></li>
            </ul>
        <?php endif; ?>

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
                <?php if ($coadmin_permissions['can_manage_ban_list']): ?>
                    <li><a href="banned_members.php?group_id=<?php echo $group_id; ?>">Manage Banned Members</a></li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>

        <!-- More Settings Section -->
        <div id="more-settings-section" data-group-id="<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>">
            <button id="more-settings-toggle">
                <i class="fas fa-cogs"></i> More Settings
            </button>
            <div id="more-settings-menu" class="hidden">
                <ul>
                    <li>
                        <a href="#" id="remove-group-link">Remove Group</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Confirmation Modal for Remove Group -->
        <div id="remove-group-modal" class="modal hidden">
            <div class="modal-content">
                <h3>Are you sure?</h3>
                <p>Are you sure you want to remove this group? This action cannot be undone.</p>
                <button id="confirm-remove-group">Yes, Remove Group</button>
                <button id="cancel-remove-group">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Chat Section -->
    <div id="chat-box">
        <!-- Messages and resources will be loaded here -->
    </div>

    <form id="chat-form">
        <input type="text" id="chat-input" placeholder="Type a message..." required>
        <button type="submit">
            <i class="fas fa-paper-plane"></i>
        </button>
    </form>

    <!-- Resource Upload Section -->
    <div id="resource-upload">
        <input type="file" id="resource-input" placeholder="Choose a file...">
        <button id="upload-btn">
            <i class="fas fa-upload"></i> Upload
        </button>
    </div>

    <!-- Context Menu -->
    <div id="context-menu">
        <button id="delete-resource-btn">Delete Resource</button>
    </div>

    <script>
        const groupId = <?php echo json_encode($group_id); ?>;
        const userId = <?php echo json_encode($user_id); ?>;
        const username = <?php echo json_encode($username); ?>;
        const groupName = <?php echo json_encode($group_name); ?>;
        const webSocketUrl = <?php echo json_encode($webSocketUrl); ?>;
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

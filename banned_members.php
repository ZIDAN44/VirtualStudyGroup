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
    header("Location: dashboard.php");
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
    <link rel="stylesheet" href="css/banned_members.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" />

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <!-- Common JS (for showToast) -->
    <script src="js/common.js"></script>

    <!-- Banned Members JS -->
    <script src="js/banned_members.js" defer></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Back Button -->
    <div class="back-button-container">
        <a href="<?php echo isset($group_id) ? "group.php?group_id=$group_id" : "dashboard.php"; ?>" class="back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <h2>Banned Members of Group</h2>

    <!-- Real-Time Search Input -->
    <div class="search-container">
        <input type="text" id="search-banned-members" placeholder="Search banned members by username...">
    </div>

    <!-- Success/Error Messages -->
    <div id="toast-container"></div>

    <!-- Banned Members List -->
    <?php if ($banned_members->num_rows > 0): ?>
        <table id="banned-members-table" border="1" cellpadding="5" cellspacing="0">
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
                    <?php
                        $banned_user_id = htmlspecialchars($banned['user_id'], ENT_QUOTES, 'UTF-8');
                        $banned_user = htmlspecialchars($banned['banned_user'], ENT_QUOTES, 'UTF-8');
                        $banned_by_user = htmlspecialchars($banned['banned_by_user'], ENT_QUOTES, 'UTF-8');
                        $banned_at = htmlspecialchars($banned['banned_at'], ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr class="banned-member-row" data-username="<?php echo strtolower($banned_user); ?>">
                        <td><?php echo $banned_user; ?></td>
                        <td><?php echo $banned_by_user; ?></td>
                        <td><?php echo $banned_at; ?></td>
                        <td>
                            <button class="unban-member-btn" 
                                    data-user-id="<?php echo $banned_user_id; ?>" 
                                    data-group-id="<?php echo $group_id; ?>">
                                Unban
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-ban">No banned members in this group.</p>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

<?php
$banned_stmt->close();
$conn->close();
?>

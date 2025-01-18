<?php
session_start();
include 'config.php';

// Check if user is logged in
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
$user_role_stmt = $conn->prepare("
    SELECT role 
    FROM group_members 
    WHERE user_id = ? AND group_id = ?
");
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
    <title>All Members</title>
    <link rel="stylesheet" href="css/group_members.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" />

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <!-- Common JS (for showToast) -->
    <script src="js/common.js"></script>

    <!-- Group Members JS -->
    <script src="js/group_members.js" defer></script>
</head>
<body data-group-id="<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>">
    <?php include 'includes/header.php'; ?>

    <!-- Back Button -->
    <div class="back-button-container">
        <a href="<?php echo isset($group_id) ? "group.php?group_id=$group_id" : "dashboard.php"; ?>" class="back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <h2>All Members</h2>

    <!-- Real-Time Search -->
    <div class="search-container">
        <input type="text" id="search-box" placeholder="Search members...">
    </div>

    <!-- Members List -->
    <ul id="member-list">
        <?php while ($member = $members->fetch_assoc()): ?>
            <?php
                $uid = htmlspecialchars($member['user_id'], ENT_QUOTES, 'UTF-8');
                $username = htmlspecialchars($member['username'], ENT_QUOTES, 'UTF-8');
                $role = htmlspecialchars($member['role'], ENT_QUOTES, 'UTF-8');
            ?>
            <li id="member-<?php echo $uid; ?>">
                <a href="view_profile.php?user_id=<?php echo $uid; ?>&group_id=<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>&all_members=1" class="username-link">
                    <span class="username"><?php echo $username; ?></span>
                </a>
                <span class="user-role">(<?php echo $role; ?>)</span>
            </li>
        <?php endwhile; ?>
    </ul>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

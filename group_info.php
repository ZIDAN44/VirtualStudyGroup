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

// Check if the user is a member of this group
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
    echo "You are not a member of this group.";
    exit();
}

// Fetch group details
$group_stmt = $conn->prepare("
    SELECT 
        group_id, group_name, group_handle, description, created_by, created_at, 
        updated_at, group_picture, max_members, current_members, join_rule, rules, req_point 
    FROM groups 
    WHERE group_id = ?
");
$group_stmt->bind_param("i", $group_id);
$group_stmt->execute();
$group_stmt->bind_result(
    $group_id, $group_name, $group_handle, $description, $created_by, $created_at, 
    $updated_at, $group_picture, $max_members, $current_members, $join_rule, $rules, $req_point
);
$group_stmt->fetch();
$group_stmt->close();

// Fetch the username of the creator
$creator_username = "Unknown";
$creator_stmt = $conn->prepare("
    SELECT username 
    FROM users 
    WHERE user_id = ?
");
$creator_stmt->bind_param("i", $created_by);
$creator_stmt->execute();
$creator_stmt->bind_result($creator_username);
$creator_stmt->fetch();
$creator_stmt->close();

// Fallback for group picture
$group_picture = !empty($group_picture)
    ? htmlspecialchars($group_picture, ENT_QUOTES, 'UTF-8')
    : htmlspecialchars($dummyGPImage, ENT_QUOTES, 'UTF-8');

// Fetch available Co-Admins
$coadmins = [];
if ($user_role === 'Admin') {
    $coadmins_stmt = $conn->prepare("
        SELECT u.user_id, u.username 
        FROM group_members gm 
        JOIN users u ON gm.user_id = u.user_id 
        WHERE gm.group_id = ? AND gm.role = 'Co-Admin'
    ");
    $coadmins_stmt->bind_param("i", $group_id);
    $coadmins_stmt->execute();
    $coadmins_result = $coadmins_stmt->get_result();
    $coadmins = $coadmins_result->fetch_all(MYSQLI_ASSOC);
    $coadmins_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Information - <?php echo htmlspecialchars($group_name, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="css/group_info.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" />

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <!-- Common JS -->
    <script src="js/common.js"></script>

    <!-- Group Info JS -->
    <script src="js/group_info.js" defer></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Back Button -->
    <div class="back-button-container">
        <a href="<?php echo isset($group_id) ? "group.php?group_id=$group_id" : "dashboard.php"; ?>" class="back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <h2>Group Information</h2>

    <div class="group-details">
        <img src="<?php echo $group_picture; ?>" alt="Group Picture" class="group-picture">
        <p><strong>Group Name:</strong> <?php echo htmlspecialchars($group_name, ENT_QUOTES, 'UTF-8'); ?></p>
        <p><strong>Group Handle:</strong> <?php echo htmlspecialchars($group_handle, ENT_QUOTES, 'UTF-8'); ?></p>
        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8')); ?></p>
        <p><strong>Created By:</strong> <?php echo htmlspecialchars($creator_username, ENT_QUOTES, 'UTF-8'); ?></p>
        <p><strong>Created At:</strong> <?php echo htmlspecialchars($created_at, ENT_QUOTES, 'UTF-8'); ?></p>
        <p><strong>Maximum Members:</strong> <?php echo $max_members !== null ? htmlspecialchars($max_members, ENT_QUOTES, 'UTF-8') : 'No Limit'; ?></p>
        <p><strong>Current Members:</strong> <?php echo htmlspecialchars($current_members, ENT_QUOTES, 'UTF-8'); ?></p>
        <p><strong>Join Rule:</strong> <?php echo $join_rule === 'auto' ? 'Anyone can join directly' : 'Admin approval required'; ?></p>
        <p><strong>Group Rules:</strong>
            <?php
            if (strpos($rules, "\n") !== false):
                // If rules contain line breaks, treat them as a list
                echo '<ul class="group-rules">';
                foreach (explode("\n", $rules) as $rule) {
                    echo '<li>' . htmlspecialchars(trim($rule), ENT_QUOTES, 'UTF-8') . '</li>';
                }
                echo '</ul>';
            else:
                // Single-line rule
                echo '<span class="group-rules inline">' . htmlspecialchars($rules, ENT_QUOTES, 'UTF-8') . '</span>';
            endif;
            ?>
        </p>
        <p><strong>Required Points to Join:</strong> <?php echo htmlspecialchars($req_point, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <!-- Leave Group Button -->
    <form id="leave-group-form">
        <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>">
        <button type="button" id="leave-group-button">Leave Group</button>
    </form>

    <!-- Modal for Selecting New Admin -->
    <div id="new-admin-modal" class="modal hidden">
        <div class="modal-content">
            <h3>Select a New Admin</h3>
            <p>You must promote a Co-Admin to Admin before leaving the group.</p>
            <form id="new-admin-form">
                <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>">
                <label for="new_admin_id">Choose a Co-Admin:</label>
                <select name="new_admin_id" id="new-admin-id" required>
                    <option value="" disabled selected>-- Select a Co-Admin --</option>
                    <?php foreach ($coadmins as $coadmin): ?>
                        <option value="<?php echo htmlspecialchars($coadmin['user_id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($coadmin['username'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Promote and Leave</button>
                <button type="button" id="cancel-admin-selection">Cancel</button>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

<?php
$conn->close();
?>

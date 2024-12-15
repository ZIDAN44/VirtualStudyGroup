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

// Check if the user is an Admin or has permission to manage join requests
$role_check_stmt = $conn->prepare("
    SELECT gm.role, cp.can_manage_join_requests 
    FROM group_members gm
    LEFT JOIN coadmin_permissions cp 
    ON gm.user_id = cp.user_id AND gm.group_id = cp.group_id
    WHERE gm.user_id = ? AND gm.group_id = ?
");
$role_check_stmt->bind_param("ii", $user_id, $group_id);
$role_check_stmt->execute();
$role_check_stmt->bind_result($user_role, $can_manage_join_requests);
$role_check_stmt->fetch();
$role_check_stmt->close();

if ($user_role !== 'Admin' && !$can_manage_join_requests) {
    $_SESSION['error_message'] = "You are not authorized to manage join requests.";
    header("Location: group_settings.php?group_id=$group_id");
    exit();
}

// Fetch pending join requests
$requests_stmt = $conn->prepare("
    SELECT jr.request_id, u.username, jr.request_time 
    FROM join_requests jr
    JOIN users u ON jr.user_id = u.user_id
    WHERE jr.group_id = ? AND jr.status = 'pending'
");
$requests_stmt->bind_param("i", $group_id);
$requests_stmt->execute();
$requests = $requests_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Join Requests</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <h2>Manage Join Requests</h2>

    <?php if (isset($_SESSION['success_message'])): ?>
        <p style="color: green;"><?php echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success_message']); ?></p>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <p style="color: red;"><?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error_message']); ?></p>
    <?php endif; ?>

    <?php if ($requests->num_rows > 0): ?>
        <ul>
            <?php while ($request = $requests->fetch_assoc()): ?>
                <li>
                    <strong><?php echo htmlspecialchars($request['username'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    (Requested on <?php echo htmlspecialchars($request['request_time'], ENT_QUOTES, 'UTF-8'); ?>)
                    <form action="process_join_request.php" method="POST" style="display:inline;">
                        <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['request_id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>">
                        <button name="action" value="approve">Approve</button>
                        <button name="action" value="reject">Reject</button>
                    </form>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No pending join requests.</p>
    <?php endif; ?>

    <p><a href="group_settings.php?group_id=<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>">Back to Settings</a></p>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

<?php
$requests_stmt->close();
$conn->close();
?>

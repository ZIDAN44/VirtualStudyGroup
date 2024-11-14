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

// Check if the user is an Admin or Co-Admin
$role_check_stmt = $conn->prepare("SELECT role FROM group_members WHERE user_id = ? AND group_id = ?");
$role_check_stmt->bind_param("ii", $user_id, $group_id);
$role_check_stmt->execute();
$role_check_stmt->bind_result($user_role);
$role_check_stmt->fetch();
$role_check_stmt->close();

if ($user_role !== 'Admin' && $user_role !== 'Co-Admin') {
    $_SESSION['error_message'] = "You are not authorized to manage join requests.";
    header("Location: group_settings.php?group_id=$group_id");
    exit();
}

// Fetch pending join requests
$requests_stmt = $conn->prepare(
    "SELECT jr.request_id, u.username, jr.request_time
     FROM join_requests jr
     JOIN users u ON jr.user_id = u.user_id
     WHERE jr.group_id = ? AND jr.status = 'pending'"
);
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

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <p style="color: green;">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </p>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <p style="color: red;">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </p>
    <?php endif; ?>

    <?php if ($requests->num_rows > 0): ?>
        <ul>
            <?php while ($request = $requests->fetch_assoc()): ?>
                <li>
                    <strong><?php echo htmlspecialchars($request['username']); ?></strong>
                    (Requested on <?php echo $request['request_time']; ?>)
                    <form action="process_join_request.php" method="POST" style="display:inline;">
                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                        <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                        <button name="action" value="approve">Approve</button>
                        <button name="action" value="reject">Reject</button>
                    </form>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No pending join requests.</p>
    <?php endif; ?>

    <p><a href="group_settings.php?group_id=<?php echo $group_id; ?>">Back to Settings</a></p>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

<?php
$requests_stmt->close();
?>

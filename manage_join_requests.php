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
    header("Location: dashboard.php");
    exit();
}

// Fetch pending join requests
$requests_stmt = $conn->prepare("
    SELECT jr.request_id, u.user_id, u.username, jr.request_time 
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
    <link rel="stylesheet" href="css/manage_join_requests.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" />

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <!-- Common JS (for showToast) -->
    <script src="js/common.js"></script>

    <!-- Manage Join Requests JS -->
    <script src="js/manage_join_requests.js" defer></script>
</head>
<body data-group-id="<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>">
    <?php include 'includes/header.php'; ?>

    <!-- Back Button -->
    <div class="back-button-container">
        <a href="<?php echo isset($group_id) ? "group.php?group_id=$group_id" : "dashboard.php"; ?>" class="back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <h2>Manage Join Requests</h2>

    <!-- Real-Time Search Input -->
    <div class="search-container">
        <input type="text" id="search-requests" placeholder="Search requests by username...">
    </div>

    <!-- Requests List -->
    <?php if ($requests->num_rows > 0): ?>
        <ul id="requests-list">
            <?php while ($request = $requests->fetch_assoc()): ?>
                <?php
                    $user_id = htmlspecialchars($request['user_id'], ENT_QUOTES, 'UTF-8');
                    $username = htmlspecialchars($request['username'], ENT_QUOTES, 'UTF-8');
                    $request_time = htmlspecialchars($request['request_time'], ENT_QUOTES, 'UTF-8');
                    $request_id = htmlspecialchars($request['request_id'], ENT_QUOTES, 'UTF-8');
                ?>
                <li class="request-item" data-username="<?php echo strtolower($username); ?>" id="request-<?php echo $request_id; ?>">
                    <strong><?php echo $username; ?></strong>
                    (Requested on <?php echo $request_time; ?>)
                    | <a href="view_profile.php?user_id=<?php echo $user_id; ?>&group_id=<?php echo $group_id; ?>">View Profile</a>
                    <button 
                        class="approve-btn" 
                        data-request-id="<?php echo $request_id; ?>" 
                        data-user-id="<?php echo $user_id; ?>">Approve</button>
                    <button 
                        class="reject-btn" 
                        data-request-id="<?php echo $request_id; ?>" 
                        data-user-id="<?php echo $user_id; ?>">Reject</button>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p class="no-req">No pending join requests.</p>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

<?php
$requests_stmt->close();
$conn->close();
?>

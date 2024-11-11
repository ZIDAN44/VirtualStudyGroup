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
$member_check_stmt = $conn->prepare("SELECT * FROM group_members WHERE user_id = ? AND group_id = ?");
$member_check_stmt->bind_param("ii", $user_id, $group_id);
$member_check_stmt->execute();
$membership_result = $member_check_stmt->get_result();

if ($membership_result->num_rows == 0) {
    // User is not a member of the group; redirect them to the dashboard
    $_SESSION['error_message'] = "You are not a member of this group.";
    header("Location: dashboard.php");
    exit();
}

$member_check_stmt->close();

// Fetch group name
$group_stmt = $conn->prepare("SELECT group_name FROM groups WHERE group_id = ?");
$group_stmt->bind_param("i", $group_id);
$group_stmt->execute();
$group_stmt->bind_result($group_name);
$group_stmt->fetch();
$group_stmt->close();

if (!$group_name) {
    echo "Group not found.";
    exit();
}

// Fetch uploaded resources for this group
$stmt = $conn->prepare("SELECT * FROM resources WHERE group_id = ? ORDER BY upload_time DESC");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$resources = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($group_name); ?> - Virtual Study Group</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <h2><?php echo htmlspecialchars($group_name); ?></h2>

    <!-- Chat section -->
    <div id="chat-box" style="border: 1px solid #ccc; height: 400px; overflow-y: scroll; padding: 10px;">
        <!-- Messages will be loaded here -->
    </div>

    <form id="chat-form" style="margin-top: 10px;">
        <input type="text" id="chat-input" placeholder="Type a message..." required>
        <button type="submit">Send</button>
    </form>

    <!-- Leave Group Button -->
    <form action="leave_group.php" method="POST" style="margin-top: 20px;">
        <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
        <button type="submit" style="background-color: red; color: white; padding: 10px; border: none;">Leave Group</button>
    </form>

    <h3>Upload a Resource</h3>
    <form action="upload_resource.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
        <label for="resource">Choose a file to upload:</label>
        <input type="file" name="resource" required>
        <button type="submit">Upload</button>
    </form>

    <h3>Available Resources</h3>
    <?php if ($resources->num_rows > 0): ?>
        <ul>
            <?php while ($resource = $resources->fetch_assoc()): ?>
                <li>
                    <a href="<?php echo htmlspecialchars($resource['file_path']); ?>" target="_blank"><?php echo htmlspecialchars($resource['file_name']); ?></a>
                    <small>Uploaded on <?php echo $resource['upload_time']; ?></small>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No resources uploaded yet.</p>
    <?php endif; ?>

    <script src="js/group_chat.js"></script>
    <script>
        const groupId = <?php echo json_encode($group_id); ?>;
        const userId = <?php echo json_encode($_SESSION['user_id']); ?>;
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>

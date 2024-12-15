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
    $_SESSION['error_message'] = "You are not a member of this group.";
    header("Location: dashboard.php");
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

if (!$group_name) {
    echo "Group not found.";
    exit();
}

// Fallback to dummy image if group_picture is empty
$group_picture = !empty($group_picture) 
    ? htmlspecialchars($group_picture, ENT_QUOTES, 'UTF-8') 
    : htmlspecialchars($dummyGPImage, ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($group_name, ENT_QUOTES, 'UTF-8'); ?> - Virtual Study Group</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/group_style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Group Header Section -->
    <div class="group-header">
        <img src="<?php echo $group_picture; ?>" alt="<?php echo htmlspecialchars($group_name, ENT_QUOTES, 'UTF-8'); ?> Thumbnail" class="group-header-thumbnail">
        <h2>
            <a href="group_settings.php?group_id=<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($group_name, ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </h2>
    </div>

    <!-- Chat Section -->
    <div id="chat-box" style="border: 1px solid #ccc; height: 400px; overflow-y: scroll; padding: 10px;">
        <!-- Messages and resources will be loaded here -->
    </div>

    <form id="chat-form" style="margin-top: 10px;">
        <input type="text" id="chat-input" placeholder="Type a message..." required>
        <button type="submit">Send</button>
    </form>

    <!-- Upload Resource Button -->
    <button id="upload-btn" style="margin-top: 20px;">Upload a Resource</button>
    <form id="upload-form" action="upload_resource.php" method="POST" enctype="multipart/form-data" style="display: none;">
        <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="file" id="resource-input" name="resource" style="display: none;" required>
    </form>

    <script src="js/group_chat.js"></script>
    <script>
        const groupId = <?php echo json_encode($group_id); ?>;
        const userId = <?php echo json_encode($_SESSION['user_id']); ?>;

        // Handle the upload button click
        document.getElementById('upload-btn').addEventListener('click', function () {
            document.getElementById('resource-input').click();
        });

        // Handle file selection and auto-submit
        document.getElementById('resource-input').addEventListener('change', function () {
            if (this.files.length > 0) {
                const confirmUpload = confirm("Do you want to upload this file?");
                if (confirmUpload) {
                    document.getElementById('upload-form').submit();
                }
            }
        });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

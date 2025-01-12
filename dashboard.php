<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch groups the user is part of
$user_id = $_SESSION['user_id'];

$query = "
    SELECT g.group_id, g.group_name, g.group_handle, g.description, g.group_picture
    FROM groups g
    JOIN group_members gm ON g.group_id = gm.group_id
    WHERE gm.user_id = ?
";

if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    die("Database query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Virtual Study Group</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/group_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" />
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <h2>
        Welcome, <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?>!
        <a href="user_profile.php" class="settings-icon" title="Settings" aria-label="Settings">
            <i class="fas fa-cog"></i>
        </a>
    </h2>

    <?php
    // Display success or error message if set
    if (isset($_SESSION['success_message'])) {
        echo "<p style='color: green;'>" . htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8') . "</p>";
        unset($_SESSION['success_message']);
    } elseif (isset($_SESSION['error_message'])) {
        echo "<p style='color: red;'>" . htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') . "</p>";
        unset($_SESSION['error_message']);
    }
    ?>

    <!-- Options to create or join a new group -->
    <div>
        <a href="create_group.php" class="button">Create a New Group</a>
        <a href="join_group.php" class="button">Join an Existing Group</a>
    </div>

    <!-- Display the groups the user is part of -->
    <h3>Your Study Groups</h3>

    <?php if ($result->num_rows > 0): ?>
        <ul class="group-list">
            <?php while ($group = $result->fetch_assoc()): ?>
                <?php
                $group_name = htmlspecialchars($group['group_name'], ENT_QUOTES, 'UTF-8');
                $group_handle = htmlspecialchars($group['group_handle'], ENT_QUOTES, 'UTF-8');
                $description = htmlspecialchars($group['description'], ENT_QUOTES, 'UTF-8');
                $group_picture = !empty($group['group_picture']) 
                    ? htmlspecialchars($group['group_picture'], ENT_QUOTES, 'UTF-8') 
                    :
                    $dummyGPImage;
                $group_id = htmlspecialchars($group['group_id'], ENT_QUOTES, 'UTF-8');
                ?>
                <li class="group-item">
                    <img src="<?php echo $group_picture; ?>" alt="<?php echo $group_name; ?> Thumbnail" class="group-thumbnail">
                    <div class="group-info">
                        <h4>
                            <?php echo $group_name; ?> 
                            <span class="group-handle">(<?php echo $group_handle; ?>)</span>
                        </h4>
                        <p><?php echo $description; ?></p>
                        <a href="group.php?group_id=<?php echo $group_id; ?>" class="enter-group">Enter Group Chat</a>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>You are not a member of any groups yet. Create or join a group to get started.</p>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>

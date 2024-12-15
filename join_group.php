<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch groups the user is NOT a member of
$stmt = $conn->prepare("
    SELECT g.group_id, g.group_name, g.group_handle, g.description
    FROM groups g
    LEFT JOIN group_members gm ON g.group_id = gm.group_id AND gm.user_id = ?
    WHERE gm.group_id IS NULL
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$available_groups = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join a Group - Virtual Study Group</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/group_style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <h2>Join an Existing Group</h2>

    <?php if (isset($_SESSION['success_message'])): ?>
        <p style="color: green;"><?php echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success_message']); ?></p>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <p style="color: red;"><?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error_message']); ?></p>
    <?php endif; ?>

    <?php if ($available_groups->num_rows > 0): ?>
        <ul class="group-list">
            <?php while ($group = $available_groups->fetch_assoc()): ?>
                <li class="group-item">
                    <div class="group-info">
                        <h4>
                            <?php echo htmlspecialchars($group['group_name'], ENT_QUOTES, 'UTF-8'); ?> 
                            <span class="group-handle">(<?php echo htmlspecialchars($group['group_handle'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                        </h4>
                        <p><?php echo htmlspecialchars($group['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <form action="process_join_group.php" method="POST" class="join-group-form">
                        <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group['group_id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="join-group-button">Join Group</button>
                    </form>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No groups available to join at the moment.</p>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>

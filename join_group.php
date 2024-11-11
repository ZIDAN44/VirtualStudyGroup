<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get the current user ID
$user_id = $_SESSION['user_id'];

// Fetch groups the user is NOT a member of
$stmt = $conn->prepare("SELECT g.group_id, g.group_name, g.description
                        FROM groups g
                        LEFT JOIN group_members gm ON g.group_id = gm.group_id AND gm.user_id = ?
                        WHERE gm.group_id IS NULL");
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
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <h2>Join an Existing Group</h2>

    <?php
    // Display success or error messages if set
    if (isset($_SESSION['success_message'])) {
        echo "<p style='color: green;'>" . $_SESSION['success_message'] . "</p>";
        unset($_SESSION['success_message']);
    } elseif (isset($_SESSION['error_message'])) {
        echo "<p style='color: red;'>" . $_SESSION['error_message'] . "</p>";
        unset($_SESSION['error_message']);
    }
    ?>

    <?php if ($available_groups->num_rows > 0): ?>
        <ul>
            <?php while ($group = $available_groups->fetch_assoc()): ?>
                <li>
                    <h4><?php echo htmlspecialchars($group['group_name']); ?></h4>
                    <p><?php echo htmlspecialchars($group['description']); ?></p>
                    <form action="process_join_group.php" method="POST">
                        <input type="hidden" name="group_id" value="<?php echo $group['group_id']; ?>">
                        <button type="submit">Join Group</button>
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

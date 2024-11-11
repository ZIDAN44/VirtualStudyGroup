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
$stmt = $conn->prepare("SELECT g.group_id, g.group_name, g.description 
                        FROM groups g
                        JOIN group_members gm ON g.group_id = gm.group_id
                        WHERE gm.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Virtual Study Group</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>

    <?php
    // Display success or error message if set
    if (isset($_SESSION['success_message'])) {
        echo "<p style='color: green;'>" . $_SESSION['success_message'] . "</p>";
        unset($_SESSION['success_message']);
    } elseif (isset($_SESSION['error_message'])) {
        echo "<p style='color: red;'>" . $_SESSION['error_message'] . "</p>";
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
        <ul>
            <?php while ($group = $result->fetch_assoc()): ?>
                <li>
                    <h4><?php echo htmlspecialchars($group['group_name']); ?></h4>
                    <p><?php echo htmlspecialchars($group['description']); ?></p>
                    <a href="group.php?group_id=<?php echo $group['group_id']; ?>">Enter Group Chat</a>
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

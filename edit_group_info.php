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

// Check if the user is an Admin
$member_check_stmt = $conn->prepare("SELECT role FROM group_members WHERE user_id = ? AND group_id = ?");
$member_check_stmt->bind_param("ii", $user_id, $group_id);
$member_check_stmt->execute();
$member_check_stmt->bind_result($user_role);
$member_check_stmt->fetch();
$member_check_stmt->close();

if ($user_role !== 'Admin') {
    $_SESSION['error_message'] = "Only Admins can edit group information.";
    header("Location: group_settings.php?group_id=$group_id");
    exit();
}

// Fetch group details, including the join rule
$group_stmt = $conn->prepare("SELECT group_name, description, join_rule FROM groups WHERE group_id = ?");
$group_stmt->bind_param("i", $group_id);
$group_stmt->execute();
$group_stmt->bind_result($group_name, $description, $join_rule);
$group_stmt->fetch();
$group_stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_group_name = $_POST['group_name'];
    $new_description = $_POST['description'];
    $new_join_rule = $_POST['join_rule'];

    // Update group information, including the join rule
    $update_stmt = $conn->prepare("UPDATE groups SET group_name = ?, description = ?, join_rule = ? WHERE group_id = ?");
    $update_stmt->bind_param("sssi", $new_group_name, $new_description, $new_join_rule, $group_id);

    if ($update_stmt->execute()) {
        $_SESSION['success_message'] = "Group information updated successfully!";
        header("Location: group_settings.php?group_id=$group_id");
        exit();
    } else {
        $_SESSION['error_message'] = "Failed to update group information.";
    }

    $update_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Group Info - <?php echo htmlspecialchars($group_name); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <h2>Edit Group Information</h2>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <p style="color: green;"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <p style="color: red;"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
    <?php endif; ?>

    <form action="edit_group_info.php?group_id=<?php echo $group_id; ?>" method="POST">
        <label for="group_name">Group Name:</label>
        <input type="text" name="group_name" value="<?php echo htmlspecialchars($group_name); ?>" required>

        <label for="description">Description:</label>
        <textarea name="description" rows="5" required><?php echo htmlspecialchars($description); ?></textarea>

        <h3>Group Joining Rules</h3>
        <label>
            <input type="radio" name="join_rule" value="auto" <?php echo $join_rule === 'auto' ? 'checked' : ''; ?>>
            New members can join without approval
        </label><br>
        <label>
            <input type="radio" name="join_rule" value="manual" <?php echo $join_rule === 'manual' ? 'checked' : ''; ?>>
            New members must wait for Admin approval
        </label><br>

        <button type="submit">Save Changes</button>
    </form>

    <p><a href="group_settings.php?group_id=<?php echo $group_id; ?>">Back to Settings</a></p>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

<?php
session_start();
include 'config.php';
include 'upload_group_pic.php';

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

// Check if the user is an Admin or a Co-Admin with the required permission
$permissions_stmt = $conn->prepare("
    SELECT gm.role, cp.can_edit_group_info 
    FROM group_members gm
    LEFT JOIN coadmin_permissions cp ON gm.user_id = cp.user_id AND gm.group_id = cp.group_id
    WHERE gm.user_id = ? AND gm.group_id = ?
");
$permissions_stmt->bind_param("ii", $user_id, $group_id);
$permissions_stmt->execute();
$permissions_stmt->bind_result($user_role, $can_edit_group_info);
$permissions_stmt->fetch();
$permissions_stmt->close();

if ($user_role !== 'Admin' && (!$can_edit_group_info || $user_role !== 'Co-Admin')) {
    $_SESSION['error_message'] = "You are not authorized to edit group information.";
    header("Location: group_settings.php?group_id=$group_id");
    exit();
}

// Fetch group details
$group_stmt = $conn->prepare("
    SELECT group_name, group_handle, description, group_picture, max_members, join_rule, rules 
    FROM groups 
    WHERE group_id = ?
");
$group_stmt->bind_param("i", $group_id);
$group_stmt->execute();
$group_stmt->bind_result($group_name, $group_handle, $description, $group_picture, $max_members, $join_rule, $rules);
$group_stmt->fetch();
$group_stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_group_name = trim($_POST['group_name']);
    $new_group_handle = trim($_POST['group_handle']);
    $new_description = trim($_POST['description']);
    $new_join_rule = $_POST['join_rule'];
    $new_rules = $_POST['rules'] ?? null;
    $new_max_members = isset($_POST['max_members']) && $_POST['max_members'] !== '' ? (int)$_POST['max_members'] : null;

    // Handle group picture upload
    if (isset($_FILES['group_picture']) && $_FILES['group_picture']['error'] === 0) {
        $new_group_picture = uploadGroupPicture($_FILES['group_picture'], $group_id);
        if (!$new_group_picture) {
            $_SESSION['error_message'] = "Failed to upload group picture.";
            header("Location: edit_group_info.php?group_id=$group_id");
            exit();
        }
    } else {
        $new_group_picture = $group_picture;
    }

    // Update group information
    $update_stmt = $conn->prepare("
        UPDATE groups 
        SET group_name = ?, 
            group_handle = ?, 
            description = ?, 
            group_picture = ?, 
            max_members = ?, 
            join_rule = ?, 
            rules = ? 
        WHERE group_id = ?
    ");
    $update_stmt->bind_param(
        "ssssissi", 
        $new_group_name, 
        $new_group_handle, 
        $new_description, 
        $new_group_picture, 
        $new_max_members, 
        $new_join_rule, 
        $new_rules, 
        $group_id
    );

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

    <form action="edit_group_info.php?group_id=<?php echo $group_id; ?>" method="POST" enctype="multipart/form-data">
        <label for="group_name">Group Name:</label>
        <input type="text" name="group_name" value="<?php echo htmlspecialchars($group_name); ?>" required>

        <label for="group_handle">Group Handle:</label>
        <input type="text" name="group_handle" value="<?php echo htmlspecialchars($group_handle); ?>" required>

        <label for="description">Description:</label>
        <textarea name="description" rows="5" required><?php echo htmlspecialchars($description); ?></textarea>

        <label for="group_picture">Group Picture:</label>
        <input type="file" name="group_picture" accept="image/*">
        <?php if ($group_picture): ?>
            <p>Current Picture: <img src="<?php echo $group_picture; ?>" alt="Group Picture" style="max-width: 100px;"></p>
        <?php endif; ?>

        <label for="max_members">Maximum Members:</label>
        <input type="number" name="max_members" value="<?php echo htmlspecialchars($max_members); ?>" placeholder="Leave empty for no limit">

        <h3>Group Joining Rules</h3>
        <label>
            <input type="radio" name="join_rule" value="auto" <?php echo $join_rule === 'auto' ? 'checked' : ''; ?>>
            New members can join without approval
        </label><br>
        <label>
            <input type="radio" name="join_rule" value="manual" <?php echo $join_rule === 'manual' ? 'checked' : ''; ?>>
            New members must wait for Admin approval
        </label><br>

        <label for="rules">Group Rules:</label>
        <textarea name="rules" rows="5"><?php echo htmlspecialchars($rules); ?></textarea>

        <button type="submit">Save Changes</button>
    </form>

    <p><a href="group_settings.php?group_id=<?php echo $group_id; ?>">Back to Settings</a></p>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

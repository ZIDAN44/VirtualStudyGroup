<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $group_name = $_POST['group_name'];
    $description = $_POST['description'];
    $created_by = $_SESSION['user_id'];

    // Insert the new group into the groups table
    $stmt = $conn->prepare("INSERT INTO groups (group_name, description, created_by) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $group_name, $description, $created_by);

    if ($stmt->execute()) {
        // Get the ID of the newly created group
        $group_id = $conn->insert_id;

        // Automatically add the creator as a member of the group
        $member_stmt = $conn->prepare("INSERT INTO group_members (user_id, group_id) VALUES (?, ?)");
        $member_stmt->bind_param("ii", $created_by, $group_id);

        if ($member_stmt->execute()) {
            // Success: Redirect to dashboard with success message
            $_SESSION['success_message'] = "Study group created successfully, and you have been added as a member!";
            header("Location: dashboard.php");
            exit();
        } else {
            // Error in adding creator as a member
            $error_message = "Group created, but there was an error adding you as a member.";
        }

        $member_stmt->close();
    } else {
        $error_message = "Error creating study group. Please try again.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create a Study Group - Virtual Study Group</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <h2>Create a Study Group</h2>

    <?php
    // Display error message if exists
    if (isset($error_message)) {
        echo "<p style='color: red;'>$error_message</p>";
    }
    ?>

    <form action="create_group.php" method="POST">
        <label for="group_name">Group Name:</label>
        <input type="text" name="group_name" required>

        <label for="description">Description:</label>
        <textarea name="description" rows="5" required></textarea>

        <button type="submit">Create Group</button>
    </form>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

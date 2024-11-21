<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Generate a unique group handle
function generateUniqueGroupHandle($conn, $group_name) {
    do {
        // Create a base handle by sanitizing the group name and prefixing with @
        $base_handle = '@' . strtolower(preg_replace('/[^A-Za-z0-9]/', '', $group_name));
        $unique_handle = $base_handle . rand(1000, 9999);

        // Check if the handle already exists in the database
        $stmt = $conn->prepare("SELECT 1 FROM groups WHERE group_handle = ?");
        $stmt->bind_param("s", $unique_handle);
        $stmt->execute();
        $result = $stmt->get_result();
    } while ($result->num_rows > 0);

    $stmt->close();
    return $unique_handle;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $group_name = trim($_POST['group_name']);
    $description = trim($_POST['description'] ?? '');
    $created_by = $_SESSION['user_id'];
    $max_members = isset($_POST['max_members']) && $_POST['max_members'] !== '' ? (int)$_POST['max_members'] : null;
    $join_rule = $_POST['join_rule'] ?? 'auto';
    $rules = $_POST['rules'] ?? null;

    // Validate required fields
    if (empty($group_name)) {
        $error_message = "Group name is required.";
    } else {
        // Begin transaction
        $conn->begin_transaction();
        $member_stmt = null;

        try {
            // Generate a unique group handle
            $group_handle = generateUniqueGroupHandle($conn, $group_name);

            // Insert the new group into the groups table
            $stmt = $conn->prepare(
                "INSERT INTO groups (group_name, group_handle, description, created_by, max_members, join_rule, rules, current_members, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())"
            );
            $stmt->bind_param("sssisss", $group_name, $group_handle, $description, $created_by, $max_members, $join_rule, $rules);

            if (!$stmt->execute()) {
                throw new Exception("Error creating study group: " . $stmt->error);
            }

            // Get the ID of the newly created group
            $group_id = $stmt->insert_id;

            // Automatically add the creator as an Admin
            $member_stmt = $conn->prepare("INSERT INTO group_members (user_id, group_id, role) VALUES (?, ?, 'Admin')");
            $member_stmt->bind_param("ii", $created_by, $group_id);

            if (!$member_stmt->execute()) {
                throw new Exception("Group created, but there was an error adding you as Admin: " . $member_stmt->error);
            }

            // Commit transaction
            $conn->commit();

            $_SESSION['success_message'] = "Study group created successfully, and you have been added as Admin!";
            header("Location: dashboard.php");
            exit();
        } catch (Exception $e) {
            // Rollback transaction on failure
            $conn->rollback();
            $error_message = $e->getMessage();
        } finally {
            if (isset($stmt) && $stmt !== null) {
                $stmt->close();
            }
            if (isset($member_stmt) && $member_stmt !== null) {
                $member_stmt->close();
            }
            $conn->close();
        }
    }
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
    if (isset($error_message)) {
        echo "<p style='color: red;'>$error_message</p>";
    }
    ?>

    <form action="create_group.php" method="POST">
        <label for="group_name">Group Name:</label>
        <input type="text" name="group_name" required>

        <label for="description">Description:</label>
        <textarea name="description" rows="5"></textarea>

        <label for="max_members">Maximum Members:</label>
        <input type="number" name="max_members" placeholder="Leave empty for default limit">

        <label for="join_rule">Join Rule:</label>
        <select name="join_rule">
            <option value="auto">Auto</option>
            <option value="manual">Manual</option>
        </select>

        <label for="rules">Group Rules:</label>
        <textarea name="rules" rows="5" placeholder="Optional: Add group-specific rules or policies"></textarea>

        <button type="submit">Create Group</button>
    </form>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

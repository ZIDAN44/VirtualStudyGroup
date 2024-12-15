<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Validate Co-Admins session
if (!isset($_SESSION['group_id']) || !isset($_SESSION['coadmins'])) {
    $_SESSION['error_message'] = "Invalid operation. Please try again.";
    header("Location: dashboard.php");
    exit();
}

$group_id = intval($_SESSION['group_id']);
$coadmins = $_SESSION['coadmins'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_admin_id = intval($_POST['new_admin_id']);
    $user_id = $_SESSION['user_id'];

    // Start transaction
    $conn->begin_transaction();
    try {
        // Promote the selected Co-Admin to Admin
        $promote_stmt = $conn->prepare("UPDATE group_members SET role = 'Admin' WHERE user_id = ? AND group_id = ?");
        $promote_stmt->bind_param("ii", $new_admin_id, $group_id);
        if (!$promote_stmt->execute()) {
            throw new Exception("Failed to promote the selected Co-Admin to Admin.");
        }
        $promote_stmt->close();

        // Remove the new Admin from coadmin_permissions
        $remove_permissions_stmt = $conn->prepare("DELETE FROM coadmin_permissions WHERE user_id = ? AND group_id = ?");
        $remove_permissions_stmt->bind_param("ii", $new_admin_id, $group_id);
        if (!$remove_permissions_stmt->execute()) {
            throw new Exception("Failed to remove permissions for the new Admin.");
        }
        $remove_permissions_stmt->close();

        // Remove the current Admin from the group
        $leave_stmt = $conn->prepare("DELETE FROM group_members WHERE user_id = ? AND group_id = ?");
        $leave_stmt->bind_param("ii", $user_id, $group_id);
        if (!$leave_stmt->execute()) {
            throw new Exception("Failed to leave the group.");
        }
        $leave_stmt->close();

        $conn->commit();

        $_SESSION['success_message'] = "You have successfully left the group, and a new Admin has been appointed.";
        unset($_SESSION['group_id'], $_SESSION['coadmins']);
        header("Location: dashboard.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: new_admin.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select New Admin</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h2>Select a New Admin</h2>

    <?php if (isset($_SESSION['error_message'])): ?>
        <p style="color: red;"><?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error_message']); ?></p>
    <?php endif; ?>

    <form action="new_admin.php" method="POST">
        <label for="new_admin_id">Choose a Co-Admin to promote:</label>
        <select name="new_admin_id" id="new_admin_id" required>
            <?php foreach ($coadmins as $coadmin): ?>
                <option value="<?php echo htmlspecialchars($coadmin['user_id'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($coadmin['username'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Promote and Leave</button>
    </form>
</body>
</html>

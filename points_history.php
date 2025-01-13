<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must log in to view your points history.";
    header("Location: login.php");
    exit();
}

// Fetch points history for the user
$user_id = $_SESSION['user_id'];
$history_stmt = $conn->prepare("
    SELECT 
        history_id, points_change, reason, created_at
    FROM points_history
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$history_stmt->bind_param("i", $user_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();

if (!$history_result) {
    $_SESSION['error_message'] = "Failed to retrieve points history.";
    header("Location: user_profile.php");
    exit();
}

$history_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Points History - Virtual Study Group</title>
    <link rel="stylesheet" href="css/user_profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" />
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <h2>Points History</h2>

    <?php if (isset($_SESSION['success_message'])): ?>
        <p style="color: green;"><?php echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success_message']); ?></p>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <p style="color: red;"><?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error_message']); ?></p>
    <?php endif; ?>

    <?php if ($history_result->num_rows > 0): ?>
        <table class="point-table">
            <thead>
                <tr>
                    <th>History ID</th>
                    <th>Points Change</th>
                    <th>Reason</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($history = $history_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($history['history_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php 
                                // Display '+' for positive points, '-' for negative
                                echo ($history['points_change'] > 0) ? 
                                    '+' . htmlspecialchars($history['points_change'], ENT_QUOTES, 'UTF-8') . ' Points' : 
                                    htmlspecialchars($history['points_change'], ENT_QUOTES, 'UTF-8') . ' Points';
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($history['reason'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($history['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>You have no points transactions yet.</p>
    <?php endif; ?>

    <p><a href="user_profile.php">Back to Profile</a></p>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

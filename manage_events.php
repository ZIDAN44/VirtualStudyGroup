<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;

if (!$group_id) {
    echo "Group not specified.";
    exit();
}

// Verify if the user is an admin
$admin_check_stmt = $conn->prepare("
    SELECT role 
    FROM group_members 
    WHERE user_id = ? AND group_id = ? AND role = 'Admin'
");
$admin_check_stmt->bind_param("ii", $user_id, $group_id);
$admin_check_stmt->execute();
$is_admin = $admin_check_stmt->fetch();
$admin_check_stmt->close();

if (!$is_admin) {
    echo "Access denied. Only Admins can manage events.";
    exit();
}

// Fetch all events for this group
$events_stmt = $conn->prepare("
    SELECT id, title, description, start_time, end_time, location, is_recurring, recurrence_pattern 
    FROM events 
    WHERE group_id = ?
    ORDER BY start_time ASC
");
$events_stmt->bind_param("i", $group_id);
$events_stmt->execute();
$events = $events_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Manage Events</h1>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <p style="color: green;"><?php echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success_message']); ?></p>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <p style="color: red;"><?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error_message']); ?></p>
    <?php endif; ?>

    <!-- Add New Event Button -->
    <a href="add_event.php?group_id=<?php echo $group_id; ?>">Add New Event</a>

    <!-- Display Events -->
    <ul>
        <?php while ($event = $events->fetch_assoc()): ?>
            <li>
                <h3><?php echo htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars($event['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p>
                    <strong>Start:</strong> <?php echo date("F j, Y, g:i a", strtotime($event['start_time'])); ?><br>
                    <strong>End:</strong> <?php echo $event['end_time'] ? date("F j, Y, g:i a", strtotime($event['end_time'])) : 'N/A'; ?><br>
                    <strong>Location:</strong> <?php echo htmlspecialchars($event['location'], ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <?php if ($event['is_recurring']): ?>
                    <p><strong>Recurring:</strong> <?php echo htmlspecialchars($event['recurrence_pattern'], ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <!-- RSVP Details -->
                <p>
                    <strong>RSVP:</strong> 
                    <a href="view_rsvp.php?event_id=<?php echo $event['id']; ?>">View Participants</a>
                </p>

                <!-- Event Actions -->
                <p>
                    <a href="edit_event.php?event_id=<?php echo $event['id']; ?>">Edit</a> | 
                    <a href="delete_event.php?event_id=<?php echo $event['id']; ?>" onclick="return confirm('Are you sure you want to delete this event?');">Delete</a>
                </p>
            </li>
        <?php endwhile; ?>
    </ul>

    <!-- Back to Group Page -->
    <p><a href="group_settings.php?group_id=<?php echo $group_id; ?>">Back to Group Settings</a></p>
</body>
</html>

<?php
$events_stmt->close();
$conn->close();
?>

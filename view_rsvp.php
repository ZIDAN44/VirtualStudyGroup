<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : null;

if (!$event_id) {
    echo "Event not specified.";
    exit();
}

// Fetch event and group details
$event_stmt = $conn->prepare("
    SELECT e.group_id, e.title, e.organizer_id, g.role
    FROM events e
    JOIN group_members g ON e.group_id = g.group_id
    WHERE e.id = ? AND g.user_id = ?
");
$event_stmt->bind_param("ii", $event_id, $user_id);
$event_stmt->execute();
$event_details = $event_stmt->get_result()->fetch_assoc();
$event_stmt->close();

if (!$event_details) {
    echo "Event not found or you don't have access.";
    exit();
}

// Check if the user is an admin or the organizer
$is_admin = $event_details['role'] === 'Admin';
$is_organizer = $event_details['organizer_id'] === $user_id;

if (!$is_admin && !$is_organizer) {
    echo "Access denied.";
    exit();
}

// Fetch RSVP statuses
$rsvp_stmt = $conn->prepare("
    SELECT u.username, ep.status 
    FROM event_participants ep
    JOIN users u ON ep.user_id = u.user_id
    WHERE ep.event_id = ?
");
$rsvp_stmt->bind_param("i", $event_id);
$rsvp_stmt->execute();
$rsvp_result = $rsvp_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSVP Status for <?php echo htmlspecialchars($event_details['title'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>RSVP Status for "<?php echo htmlspecialchars($event_details['title'], ENT_QUOTES, 'UTF-8'); ?>"</h1>

    <!-- Back to Manage Events -->
    <p><a href="manage_events.php?group_id=<?php echo $event_details['group_id']; ?>">Back to Manage Events</a></p>

    <!-- Display RSVP Status -->
    <ul>
        <?php while ($rsvp = $rsvp_result->fetch_assoc()): ?>
            <li>
                <strong><?php echo htmlspecialchars($rsvp['username'], ENT_QUOTES, 'UTF-8'); ?></strong> - 
                <?php echo htmlspecialchars(ucfirst($rsvp['status']), ENT_QUOTES, 'UTF-8'); ?>
            </li>
        <?php endwhile; ?>
    </ul>
</body>
</html>

<?php
$rsvp_stmt->close();
$conn->close();
?>

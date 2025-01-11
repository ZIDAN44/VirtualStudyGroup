<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : null;
$status = isset($_POST['status']) ? $_POST['status'] : null;

if (!$event_id || !in_array($status, ['yes', 'no', 'maybe'])) {
    echo "Invalid request.";
    exit();
}

// Check if event exists
$stmt = $conn->prepare("SELECT id FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event_exists = $stmt->fetch();
$stmt->close();

if (!$event_exists) {
    echo "Event not found.";
    exit();
}

// Update RSVP status
$rsvp_stmt = $conn->prepare("
    INSERT INTO event_participants (event_id, user_id, status) 
    VALUES (?, ?, ?) 
    ON DUPLICATE KEY UPDATE status = VALUES(status)
");
$rsvp_stmt->bind_param("iis", $event_id, $user_id, $status);
if ($rsvp_stmt->execute()) {
    echo "RSVP updated successfully.";
} else {
    echo "Failed to update RSVP.";
}
$rsvp_stmt->close();
?>

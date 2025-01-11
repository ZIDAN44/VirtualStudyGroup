<?php
session_start();
include 'config.php';

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

// Fetch event details
$stmt = $conn->prepare("SELECT group_id, organizer_id FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    echo "Event not found.";
    exit();
}

$group_id = $event['group_id'];

// Check if user is the organizer or admin
$role_stmt = $conn->prepare("
    SELECT role 
    FROM group_members 
    WHERE user_id = ? AND group_id = ?
");
$role_stmt->bind_param("ii", $user_id, $group_id);
$role_stmt->execute();
$role_stmt->bind_result($role);
$role_stmt->fetch();
$role_stmt->close();

if ($role !== 'Admin' && $event['organizer_id'] != $user_id) {
    echo "Access denied.";
    exit();
}

// Delete event
$delete_stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
$delete_stmt->bind_param("i", $event_id);
if ($delete_stmt->execute()) {
    $_SESSION['success_message'] = "Event deleted successfully!";
    header("Location: manage_events.php?group_id=$group_id");
} else {
    $_SESSION['error_message'] = "Failed to delete event.";
}
$delete_stmt->close();
?>

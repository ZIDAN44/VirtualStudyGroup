<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $location = $_POST['location'];
    $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
    $recurrence_pattern = $_POST['recurrence_pattern'];

    $stmt = $conn->prepare("
        INSERT INTO events (group_id, title, description, start_time, end_time, location, organizer_id, is_recurring, recurrence_pattern) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssssiis", $group_id, $title, $description, $start_time, $end_time, $location, $user_id, $is_recurring, $recurrence_pattern);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Event created successfully!";
        header("Location: manage_events.php?group_id=$group_id");
    } else {
        $_SESSION['error_message'] = "Failed to create event.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Add New Event</h1>
    <form method="POST">
        <label>Title: <input type="text" name="title" required></label><br>
        <label>Description: <textarea name="description" required></textarea></label><br>
        <label>Start Time: <input type="datetime-local" name="start_time" required></label><br>
        <label>End Time: <input type="datetime-local" name="end_time"></label><br>
        <label>Location: <input type="text" name="location" required></label><br>
        <label>Recurring: <input type="checkbox" name="is_recurring"></label><br>
        <label>Recurrence Pattern: 
            <select name="recurrence_pattern">
                <option value="">None</option>
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
            </select>
        </label><br>
        <button type="submit">Create Event</button>
    </form>
</body>
</html>

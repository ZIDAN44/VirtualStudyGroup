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

// Fetch the event details
$stmt = $conn->prepare("
    SELECT group_id, title, description, start_time, end_time, location, is_recurring, recurrence_pattern, organizer_id
    FROM events 
    WHERE id = ?
");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    echo "Event not found.";
    exit();
}

$group_id = $event['group_id'];

// Check if the user is the organizer or admin
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $location = $_POST['location'];
    $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
    $recurrence_pattern = $_POST['recurrence_pattern'];

    // If the user unticks 'is_recurring', set 'recurrence_pattern' to NULL
    if ($is_recurring === 0) {
        $recurrence_pattern = null;
    }

    $update_stmt = $conn->prepare("
        UPDATE events 
        SET title = ?, description = ?, start_time = ?, end_time = ?, location = ?, is_recurring = ?, recurrence_pattern = ?
        WHERE id = ?
    ");
    $update_stmt->bind_param("sssssisi", $title, $description, $start_time, $end_time, $location, $is_recurring, $recurrence_pattern, $event_id);
    if ($update_stmt->execute()) {
        $_SESSION['success_message'] = "Event updated successfully!";
        header("Location: manage_events.php?group_id=$group_id");
        exit();
    } else {
        $_SESSION['error_message'] = "Failed to update event.";
    }
    $update_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Edit Event</h1>
    <form method="POST">
        <label>Title: <input type="text" name="title" value="<?php echo htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?>" required></label><br>
        <label>Description: <textarea name="description" required><?php echo htmlspecialchars($event['description'], ENT_QUOTES, 'UTF-8'); ?></textarea></label><br>
        <label>Start Time: <input type="datetime-local" name="start_time" value="<?php echo date('Y-m-d\TH:i', strtotime($event['start_time'])); ?>" required></label><br>
        <label>End Time: <input type="datetime-local" name="end_time" value="<?php echo $event['end_time'] ? date('Y-m-d\TH:i', strtotime($event['end_time'])) : ''; ?>"></label><br>
        <label>Location: <input type="text" name="location" value="<?php echo htmlspecialchars($event['location'], ENT_QUOTES, 'UTF-8'); ?>" required></label><br>
        <label>Recurring: <input type="checkbox" name="is_recurring" <?php echo $event['is_recurring'] ? 'checked' : ''; ?>></label><br>
        <label>Recurrence Pattern: 
            <select name="recurrence_pattern">
                <option value="" <?php echo !$event['recurrence_pattern'] ? 'selected' : ''; ?>>None</option>
                <option value="daily" <?php echo $event['recurrence_pattern'] === 'daily' ? 'selected' : ''; ?>>Daily</option>
                <option value="weekly" <?php echo $event['recurrence_pattern'] === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                <option value="monthly" <?php echo $event['recurrence_pattern'] === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
            </select>
        </label><br>
        <button type="submit">Save Changes</button>
    </form>
</body>
</html>

<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($_SESSION['user_id']) || !isset($data['group_ids'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $selected_group_ids = array_map('intval', $data['group_ids']); // Ensure group IDs are integers

    // Define the maximum number of pins allowed
    $MAX_PINS = 6;

    // Fetch existing pinned groups
    $existing_group_ids_stmt = $conn->prepare("SELECT group_id FROM user_pinned_groups WHERE user_id = ?");
    $existing_group_ids_stmt->bind_param("i", $user_id);
    $existing_group_ids_stmt->execute();
    $existing_result = $existing_group_ids_stmt->get_result();
    $existing_group_ids = [];
    while ($row = $existing_result->fetch_assoc()) {
        $existing_group_ids[] = $row['group_id'];
    }
    $existing_group_ids_stmt->close();

    // Determine groups to pin and unpin
    $groups_to_pin = array_diff($selected_group_ids, $existing_group_ids);
    $groups_to_unpin = array_diff($existing_group_ids, $selected_group_ids);

    // Validate the total number of pinned groups does not exceed the maximum allowed
    if (count($existing_group_ids) - count($groups_to_unpin) + count($groups_to_pin) > $MAX_PINS) {
        echo json_encode(['status' => 'error', 'message' => "You can only pin up to {$MAX_PINS} groups."]);
        exit();
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Pin new groups, ensuring they are not deleted
        if (!empty($groups_to_pin)) {
            $pin_stmt = $conn->prepare("
                INSERT INTO user_pinned_groups (user_id, group_id)
                SELECT ?, group_id FROM groups WHERE group_id = ? AND is_deleted = 0
            ");
            foreach ($groups_to_pin as $group_id) {
                $pin_stmt->bind_param("ii", $user_id, $group_id);
                $pin_stmt->execute();
            }
            $pin_stmt->close();
        }

        // Unpin groups
        if (!empty($groups_to_unpin)) {
            $unpin_stmt = $conn->prepare("DELETE FROM user_pinned_groups WHERE user_id = ? AND group_id = ?");
            foreach ($groups_to_unpin as $group_id) {
                $unpin_stmt->bind_param("ii", $user_id, $group_id);
                $unpin_stmt->execute();
            }
            $unpin_stmt->close();
        }

        // Commit transaction
        $conn->commit();

        echo json_encode(['status' => 'success', 'message' => 'Pins updated successfully.']);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Failed to update pins. Please try again.']);
    }
}
?>

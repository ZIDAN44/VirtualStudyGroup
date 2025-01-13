<?php
session_start();
include 'config.php';

// Set header for JSON response
header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Retrieve and sanitize POST parameters
$field = isset($_POST['field']) ? trim($_POST['field']) : '';
$value = isset($_POST['value']) ? trim($_POST['value']) : '';

// Allowed fields to prevent unauthorized updates
$allowed_fields = ['full_name', 'phone_number', 'status_message', 'date_of_birth', 'gender', 'geo_location'];

// Validate field
if (!in_array($field, $allowed_fields)) {
    echo json_encode(['success' => false, 'message' => 'Invalid field.']);
    exit();
}

// Define allowed geo locations
$allowed_geo_locations = ["Bangladesh", "USA", "Canada", "Germany", "Australia"];

// Validation based on field type
switch ($field) {
    case 'date_of_birth':
        // Validate date format (YYYY-MM-DD)
        $d = DateTime::createFromFormat('Y-m-d', $value);
        if (!($d && $d->format('Y-m-d') === $value)) {
            echo json_encode(['success' => false, 'message' => 'Invalid date format.']);
            exit();
        }
        break;
    case 'gender':
        // Validate gender value
        $allowed_genders = ['Male', 'Female', 'Other'];
        if (!in_array($value, $allowed_genders)) {
            echo json_encode(['success' => false, 'message' => 'Invalid gender selection.']);
            exit();
        }
        break;
    case 'geo_location':
        // Validate geo location value
        if (!in_array($value, $allowed_geo_locations)) {
            echo json_encode(['success' => false, 'message' => 'Invalid geo location selection.']);
            exit();
        }
        break;
    default:
        break;
}

// Prepare and execute the update query
$update_stmt = $conn->prepare("UPDATE users SET $field = ?, updated_at = NOW() WHERE user_id = ?");
$update_stmt->bind_param("si", $value, $user_id);

if ($update_stmt->execute()) {
    // After successful update, recalculate profile_com
    $fields_to_check = ['full_name', 'phone_number', 'status_message', 'date_of_birth', 'gender', 'geo_location'];
    $completed_fields = 0;

    $check_stmt = $conn->prepare("SELECT " . implode(", ", $fields_to_check) . " FROM users WHERE user_id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user_data = $result->fetch_assoc();
        foreach ($fields_to_check as $check_field) {
            if (!empty(trim($user_data[$check_field]))) {
                $completed_fields++;
            }
        }
    }
    $check_stmt->close();

    // Calculate new profile_com
    // Starting at 50%, each completed field adds ~8.33%
    $base_com = 50;
    $increment = 50 / count($fields_to_check); // ~8.33%
    $new_com = $base_com + ($completed_fields * $increment);
    if ($new_com > 100) {
        $new_com = 100;
    }
    $new_com = round($new_com); // Round to nearest integer

    // Update profile_com in the database
    $update_com_stmt = $conn->prepare("UPDATE users SET profile_com = ? WHERE user_id = ?");
    $update_com_stmt->bind_param("ii", $new_com, $user_id);
    if ($update_com_stmt->execute()) {
        // After updating profile_com, check if bonus should be awarded
        $bonus_awarded = false;
        $new_points = null; // Initialize new_points

        if ($new_com >= 100) {
            // Check if profile_bonus is already true
            $bonus_check_stmt = $conn->prepare("SELECT profile_bonus FROM users WHERE user_id = ?");
            $bonus_check_stmt->bind_param("i", $user_id);
            $bonus_check_stmt->execute();
            $bonus_result = $bonus_check_stmt->get_result();
            if ($bonus_result->num_rows === 1) {
                $bonus_row = $bonus_result->fetch_assoc();
                if (!$bonus_row['profile_bonus']) {
                    $points_change = 10;
                    $reason = 'Profile Completion Bonus';

                    $insert_bonus_stmt = $conn->prepare("INSERT INTO points_history (user_id, points_change, reason, created_at) VALUES (?, ?, ?, NOW())");
                    $insert_bonus_stmt->bind_param("iis", $user_id, $points_change, $reason);
                    if ($insert_bonus_stmt->execute()) {
                        $set_bonus_stmt = $conn->prepare("UPDATE users SET profile_bonus = TRUE WHERE user_id = ?");
                        $set_bonus_stmt->bind_param("i", $user_id);
                        if ($set_bonus_stmt->execute()) {
                            $bonus_awarded = true;

                            $points_stmt = $conn->prepare("SELECT points FROM users WHERE user_id = ?");
                            $points_stmt->bind_param("i", $user_id);
                            $points_stmt->execute();
                            $points_result = $points_stmt->get_result();
                            if ($points_result->num_rows === 1) {
                                $points_row = $points_result->fetch_assoc();
                                $new_points = $points_row['points'];
                            }
                            $points_stmt->close();
                        }
                        $set_bonus_stmt->close();
                    }
                    $insert_bonus_stmt->close();
                }
            }
            $bonus_check_stmt->close();
        }

        // Retrieve the new updated_at timestamp
        $timestamp_stmt = $conn->prepare("SELECT updated_at FROM users WHERE user_id = ?");
        $timestamp_stmt->bind_param("i", $user_id);
        $timestamp_stmt->execute();
        $timestamp_result = $timestamp_stmt->get_result();

        if ($timestamp_result->num_rows === 1) {
            $timestamp_row = $timestamp_result->fetch_assoc();
            $updated_at = $timestamp_row['updated_at'];
        } else {
            // Fallback in case the SELECT fails
            $updated_at = date("Y-m-d H:i:s");
        }
        $timestamp_stmt->close();

        // Prepare the response
        $response = [
            'success' => true, 
            'new_value' => htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), 
            'profile_com' => $new_com,
            'updated_at' => $updated_at
        ];

        if ($bonus_awarded) {
            $response['bonus_awarded'] = true;
            $response['message'] = 'Profile completed! You have received a 10-point bonus.';
            $response['new_points'] = $new_points;
        }

        echo json_encode($response);
    } else {
        // Failed to update profile_com, but the main update was successful
        // Retrieve the updated_at timestamp
        $timestamp_stmt = $conn->prepare("SELECT updated_at FROM users WHERE user_id = ?");
        $timestamp_stmt->bind_param("i", $user_id);
        $timestamp_stmt->execute();
        $timestamp_result = $timestamp_stmt->get_result();

        if ($timestamp_result->num_rows === 1) {
            $timestamp_row = $timestamp_result->fetch_assoc();
            $updated_at = $timestamp_row['updated_at'];
        } else {
            // Fallback in case the SELECT fails
            $updated_at = date("Y-m-d H:i:s");
        }
        $timestamp_stmt->close();

        // Return success response with new_value and updated_at, but with a message about profile_com failure
        echo json_encode([
            'success' => true, 
            'new_value' => htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), 
            'profile_com' => '50', 
            'updated_at' => $updated_at,
            'message' => 'Profile updated, but failed to update profile completion.'
        ]);
    }
    $update_com_stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed.']);
}

$update_stmt->close();
$conn->close();
?>

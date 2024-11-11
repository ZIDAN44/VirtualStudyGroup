<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $group_id = $_POST['group_id'];
    $user_id = $_SESSION['user_id'];
    $upload_dir = "uploads/resources/";

    // Check if upload directory exists; if not, create it
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Handle file upload
    if (isset($_FILES['resource']) && $_FILES['resource']['error'] == 0) {
        $file_name = basename($_FILES['resource']['name']);
        $file_path = $upload_dir . $file_name;

        // Move the uploaded file to the server
        if (move_uploaded_file($_FILES['resource']['tmp_name'], $file_path)) {
            // Save file info to the database
            $stmt = $conn->prepare("INSERT INTO resources (group_id, uploaded_by, file_name, file_path) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $group_id, $user_id, $file_name, $file_path);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Resource uploaded successfully!";
            } else {
                $_SESSION['error_message'] = "Error saving file info to the database.";
            }

            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Error uploading file.";
        }
    } else {
        $_SESSION['error_message'] = "No file uploaded or file upload error.";
    }
}

header("Location: group.php?group_id=" . $group_id);
exit();
?>

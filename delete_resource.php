<?php
session_start();
require_once 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$resource_id = intval($_POST['resource_id'] ?? 0);
$group_id = intval($_POST['group_id'] ?? 0);

if (!$resource_id || !$group_id) {
    $_SESSION['error_message'] = "Invalid request.";
    header("Location: group.php?group_id=$group_id");
    exit();
}

try {
    // Validate user permissions
    $stmt = $conn->prepare("SELECT role FROM group_members WHERE user_id = ? AND group_id = ?");
    $stmt->bind_param("ii", $user_id, $group_id);
    $stmt->execute();
    $stmt->bind_result($user_role);
    if (!$stmt->fetch() || !in_array($user_role, ['Admin', 'Co-Admin'])) {
        throw new Exception("You do not have permission to delete this file.");
    }
    $stmt->close();

    // Fetch the file hash and original file name from the database
    $stmt = $conn->prepare("SELECT file_path, file_name FROM resources WHERE resource_id = ? AND group_id = ?");
    $stmt->bind_param("ii", $resource_id, $group_id);
    $stmt->execute();
    $stmt->bind_result($file_hash, $file_name);
    if (!$stmt->fetch()) {
        throw new Exception("File not found.");
    }
    $stmt->close();

    // Fetch group name for constructing MinIO file path
    $group_stmt = $conn->prepare("SELECT group_name FROM groups WHERE group_id = ?");
    $group_stmt->bind_param("i", $group_id);
    $group_stmt->execute();
    $group_stmt->bind_result($group_name);
    if (!$group_stmt->fetch()) {
        throw new Exception("Group not found.");
    }
    $group_stmt->close();

    // Sanitize the group name for use in file paths
    $sanitizedGroupName = preg_replace('/[^A-Za-z0-9]/', '', $group_name);

    // Construct MinIO file path dynamically using the hash
    $filePath = "{$sanitizedGroupName}_{$group_id}/res/{$file_hash}";

    // Delete the file from MinIO
    $method = "DELETE";
    $date = gmdate('D, d M Y H:i:s T');

    // Create the string to sign
    $stringToSign = "$method\n\n\n$date\n/$minioBucketName/$filePath";

    // Generate the signature
    $signature = base64_encode(hash_hmac('sha1', $stringToSign, $minioSecretKey, true));

    // Set up headers for MinIO delete
    $headers = [
        "Date: $date",
        "Authorization: AWS $minioAccessKey:$signature"
    ];

    // Execute the DELETE request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$minioHost/$minioBucketName/$filePath");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 204) { // 204 No Content indicates successful deletion
        // Mark the file as deleted in the database
        $update_stmt = $conn->prepare("UPDATE resources SET deleted = TRUE WHERE resource_id = ?");
        $update_stmt->bind_param("i", $resource_id);
        $update_stmt->execute();
        $update_stmt->close();

        $_SESSION['success_message'] = "File '{$file_name}' deleted successfully.";
    } else {
        throw new Exception("Failed to delete file from storage. HTTP Code: $httpCode.");
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
}

header("Location: group.php?group_id=$group_id");
exit();
?>

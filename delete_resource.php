<?php
session_start();
require_once 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$resource_id = $_POST['resource_id'] ?? null;
$group_id = $_POST['group_id'] ?? null;

if (!$resource_id || !$group_id) {
    echo "Invalid request.";
    exit();
}

// Check the user's role in the group
$stmt = $conn->prepare("SELECT role FROM group_members WHERE user_id = ? AND group_id = ?");
$stmt->bind_param("ii", $user_id, $group_id);
$stmt->execute();
$stmt->bind_result($user_role);
$stmt->fetch();
$stmt->close();

if ($user_role !== 'Admin' && $user_role !== 'Co-Admin') {
    $_SESSION['error_message'] = "You do not have permission to delete this file.";
    header("Location: group.php?group_id=$group_id");
    exit();
}

// Fetch the file path from the database
$stmt = $conn->prepare("SELECT file_path FROM resources WHERE resource_id = ? AND group_id = ?");
$stmt->bind_param("ii", $resource_id, $group_id);
$stmt->execute();
$stmt->bind_result($file_path);
$stmt->fetch();
$stmt->close();

if (!$file_path) {
    $_SESSION['error_message'] = "File not found.";
    header("Location: group.php?group_id=$group_id");
    exit();
}

// Delete the file from MinIO
$method = "DELETE";
$date = gmdate('D, d M Y H:i:s T');
$resourceName = parse_url($file_path, PHP_URL_PATH);
$resourceName = ltrim($resourceName, '/');

// Create the string to sign
$stringToSign = "$method\n\n\n$date\n/$resourceName";

// Generate the signature
$signature = base64_encode(hash_hmac('sha1', $stringToSign, $minioSecretKey, true));

// Set up headers for MinIO delete
$headers = [
    "Date: $date",
    "Authorization: AWS $minioAccessKey:$signature"
];

// Execute the DELETE request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$minioHost/$resourceName");
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

    $_SESSION['success_message'] = "File deleted successfully.";
} else {
    $_SESSION['error_message'] = "Failed to delete file from storage. HTTP Code: $httpCode.";
}

header("Location: group.php?group_id=$group_id");
exit();
?>

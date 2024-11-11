<?php
session_start();
require_once 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $group_id = $_POST['group_id'];
    $user_id = $_SESSION['user_id'];

    // Fetch the group name from the database
    $stmt = $conn->prepare("SELECT group_name FROM groups WHERE group_id = ?");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $stmt->bind_result($group_name);
    $stmt->fetch();
    $stmt->close();

    if (!$group_name) {
        $_SESSION['error_message'] = "Group not found.";
        header("Location: dashboard.php");
        exit();
    }

    // Format the group name for the MinIO path (remove spaces and special characters)
    $sanitizedGroupName = preg_replace('/[^A-Za-z0-9]/', '', $group_name);

    // Handle file upload
    if (isset($_FILES['resource']) && $_FILES['resource']['error'] == 0) {
        $file = $_FILES['resource'];
        $originalFileName = basename($file['name']);
        $encodedFileName = rawurlencode($originalFileName);
        $filePath = $file['tmp_name'];
        $fileContents = file_get_contents($filePath);

        // Create file name with groupname_id/res as a prefix
        $fileName = $sanitizedGroupName . '_' . $group_id . '/res/' . $encodedFileName;

        // MinIO-specific headers
        $method = "PUT";
        $date = gmdate('D, d M Y H:i:s T');
        $contentType = mime_content_type($filePath);
        $contentLength = strlen($fileContents);

        // Create the string to sign
        $stringToSign = "$method\n\n$contentType\n$date\n/$minioBucketName/$fileName";

        // Generate the signature
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $minioSecretKey, true));

        // Set up headers for MinIO upload
        $headers = [
            "Date: $date",
            "Content-Type: $contentType",
            "Authorization: AWS $minioAccessKey:$signature",
            "Content-Length: $contentLength"
        ];

        // Upload to MinIO via cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$minioHost/$minioBucketName/$fileName");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContents);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            // File uploaded successfully; save to database
            $fileUrl = "$minioHost/$minioBucketName/$fileName";
            $stmt = $conn->prepare("INSERT INTO resources (group_id, uploaded_by, file_name, file_path) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $group_id, $user_id, $originalFileName, $fileUrl);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Resource uploaded successfully!";
            } else {
                $_SESSION['error_message'] = "Error saving file info to the database.";
            }

            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Error uploading file to MinIO. HTTP Code: $httpCode.";
        }
    } else {
        $_SESSION['error_message'] = "No file uploaded or file upload error.";
    }
}

header("Location: group.php?group_id=" . $group_id);
exit();
?>

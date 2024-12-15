<?php
session_start();
require_once 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = intval($_POST['group_id']);
    $user_id = $_SESSION['user_id'];

    try {
        // Fetch the group name from the database
        $stmt = $conn->prepare("SELECT group_name FROM groups WHERE group_id = ?");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $stmt->bind_result($group_name);
        if (!$stmt->fetch()) {
            throw new Exception("Group not found.");
        }
        $stmt->close();

        // Format the group name for the MinIO path (remove spaces and special characters)
        $sanitizedGroupName = preg_replace('/[^A-Za-z0-9]/', '', $group_name);

        // Handle file upload
        if (isset($_FILES['resource']) && $_FILES['resource']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['resource'];
            $originalFileName = basename($file['name']);
            $filePath = $file['tmp_name'];
            $fileContents = file_get_contents($filePath);

            // Generate a unique hash for the file
            $fileHash = hash_file('sha256', $filePath);
            $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);
            $hashedFileName = $fileHash . '.' . $fileExtension;

            // Create file name with groupname_id/res as a prefix
            $fileName = $sanitizedGroupName . '_' . $group_id . '/res/' . $hashedFileName;

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
                if (!$stmt->execute()) {
                    throw new Exception("Error saving file info to the database.");
                }
                $_SESSION['success_message'] = "Resource uploaded successfully!";
            } else {
                throw new Exception("Error uploading file to MinIO. HTTP Code: $httpCode.");
            }
        } else {
            throw new Exception("No file uploaded or file upload error.");
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

header("Location: group.php?group_id=" . $group_id);
exit();
?>

<?php
function uploadProfilePicture($file, $user_id)
{
    global $minioHost, $minioBucketName, $minioAccessKey, $minioSecretKey;

    // Ensure a valid file upload
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception("Invalid file upload.");
    }

    $originalFileName = basename($file['name']);
    $filePath = $file['tmp_name'];
    $contentType = mime_content_type($filePath);

    // Validate allowed MIME types
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($contentType, $allowedMimeTypes)) {
        throw new Exception("Invalid file type. Only JPEG, PNG, and GIF are allowed.");
    }

    // Generate a unique file name with user_id as a prefix
    $sanitizedFileName = preg_replace('/[^A-Za-z0-9\._-]/', '', $originalFileName);
    $uniqueFileName = "user_" . intval($user_id) . "/profile_pictures/" . uniqid() . "_" . $sanitizedFileName;

    // Read file contents and calculate length
    $fileContents = file_get_contents($filePath);
    $contentLength = strlen($fileContents);

    // Ensure the file is not empty
    if ($contentLength === 0) {
        throw new Exception("Uploaded file is empty.");
    }

    // MinIO-specific headers
    $method = "PUT";
    $date = gmdate('D, d M Y H:i:s T');
    $stringToSign = "$method\n\n$contentType\n$date\n/$minioBucketName/$uniqueFileName";
    $signature = base64_encode(hash_hmac('sha1', $stringToSign, $minioSecretKey, true));

    $headers = [
        "Date: $date",
        "Content-Type: $contentType",
        "Authorization: AWS $minioAccessKey:$signature",
        "Content-Length: $contentLength"
    ];

    // Upload to MinIO via cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$minioHost/$minioBucketName/$uniqueFileName");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContents);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode === 200) {
        // File uploaded successfully
        return "$minioHost/$minioBucketName/$uniqueFileName";
    } else {
        // Handle upload failure
        $message = "Error uploading to MinIO.";
        if ($error) {
            $message .= " cURL error: " . htmlspecialchars($error, ENT_QUOTES, 'UTF-8');
        }
        $message .= " HTTP Code: $httpCode.";
        throw new Exception($message);
    }
}

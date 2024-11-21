<?php
function uploadGroupPicture($file, $group_id)
{
    global $minioHost, $minioBucketName, $minioAccessKey, $minioSecretKey;

    $originalFileName = basename($file['name']);
    $filePath = $file['tmp_name'];
    $contentType = mime_content_type($filePath);

    // Generate a unique file name with group_id as a prefix
    $sanitizedFileName = preg_replace('/[^A-Za-z0-9\._-]/', '', $originalFileName);
    $uniqueFileName = "group_" . $group_id . "/pictures/" . uniqid() . "_" . $sanitizedFileName;

    // MinIO-specific headers
    $method = "PUT";
    $date = gmdate('D, d M Y H:i:s T');
    $fileContents = file_get_contents($filePath);
    $contentLength = strlen($fileContents);

    // Create the string to sign
    $stringToSign = "$method\n\n$contentType\n$date\n/$minioBucketName/$uniqueFileName";

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
    curl_setopt($ch, CURLOPT_URL, "$minioHost/$minioBucketName/$uniqueFileName");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContents);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        // Return file URL on success
        return "$minioHost/$minioBucketName/$uniqueFileName";
    } else {
        return false;
    }
}

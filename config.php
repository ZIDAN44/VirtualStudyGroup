<?php
// config.php - Database and MinIO configuration

require_once __DIR__ . '/env_loader.php';

try {
    loadEnv(__DIR__ . '/.env');
} catch (Exception $e) {
    // Terminate with a user-friendly error message
    die("Critical configuration error. Please check the environment settings.");
}

// Database configuration from environment variables
$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_NAME'] ?? 'database';
$dbUser = $_ENV['DB_USER'] ?? 'user';
$dbPassword = $_ENV['DB_PASSWORD'] ?? '';

// MinIO configuration from environment variables
$minioBucketName = $_ENV['MINIO_BUCKET_NAME'] ?? '';
$minioAccessKey = $_ENV['MINIO_ACCESS_KEY'] ?? '';
$minioSecretKey = $_ENV['MINIO_SECRET_KEY'] ?? '';
$minioHost = $_ENV['MINIO_HOST'] ?? '';

// Load WebSocket URL from environment variables
$webSocketUrl = $_ENV['WEBSOCKET_URL'] ?? '';

// Dummy Group Profile Picture URL
$dummyGPImage = rtrim($minioHost, '/') . '/' . $minioBucketName . '/Dummy_Pic/Dummy_GProfile.jpg';

// Dummy User Profile Picture URL
$dummyUPImage = rtrim($minioHost, '/') . '/' . $minioBucketName . '/Dummy_Pic/Dummy_Profile.jpg';

// Validate essential configurations
if (empty($minioAccessKey) || empty($minioSecretKey) || empty($minioBucketName)) {
    die("Critical configuration error: MinIO settings are missing in the .env file.");
}

// Create a database connection
$conn = new mysqli($dbHost, $dbUser, $dbPassword, $dbName, (int)$dbPort);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . htmlspecialchars($conn->connect_error, ENT_QUOTES, 'UTF-8'));
}

// Set character set to UTF-8 for security and compatibility
if (!$conn->set_charset("utf8mb4")) {
    die("Error loading character set utf8mb4: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}
?>

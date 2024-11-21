<?php
// config.php - Database and MinIO configuration

require_once __DIR__ . '/env_loader.php';

try {
    loadEnv(__DIR__ . '/.env');
} catch (Exception $e) {
    die($e->getMessage());
}

// Database configuration from environment variables
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbname = $_ENV['DB_NAME'] ?? 'database';
$user = $_ENV['DB_USER'] ?? 'user';
$password = $_ENV['DB_PASSWORD'] ?? '';

// MinIO configuration from environment variables
$minioBucketName = $_ENV['MINIO_BUCKET_NAME'];
$minioAccessKey = $_ENV['MINIO_ACCESS_KEY'];
$minioSecretKey = $_ENV['MINIO_SECRET_KEY'];
$minioHost = $_ENV['MINIO_HOST'];

// Dummy Group profile Pic
$dummyGPImage = "{$minioHost}/{$minioBucketName}/Dummy_Pic/Dummy_GProfile.png";

// Create database connection
$conn = new mysqli($host, $user, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

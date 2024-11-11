<?php
// config.php - Database configuration

// Ensure the correct path to env_loader.php
require_once __DIR__ . '/env_loader.php';

try {
    loadEnv(__DIR__ . '/.env');
} catch (Exception $e) {
    die($e->getMessage());
}

// Fetch database configuration from environment variables
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbname = $_ENV['DB_NAME'] ?? 'database';
$user = $_ENV['DB_USER'] ?? 'user';
$password = $_ENV['DB_PASSWORD'] ?? '';

// Create connection using the host, port, and database name
$conn = new mysqli($host, $user, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

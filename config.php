<?php
// config.php - Database configuration

$host = "localhost";
$user = "root";
$password = "";
$dbname = "studygroup";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<?php
// env_loader.php - Load environment variables from .env file

function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        throw new Exception("Environment file (.env) not found at the specified path: " . htmlspecialchars($filePath, ENT_QUOTES, 'UTF-8'));
    }

    $envVariables = [];

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments and empty lines
        if (strpos(trim($line), '#') === 0 || empty(trim($line))) {
            continue;
        }

        // Split into name and value pair
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            throw new Exception("Invalid line in .env file: " . htmlspecialchars($line, ENT_QUOTES, 'UTF-8'));
        }

        $name = trim($parts[0]);
        $value = trim($parts[1]);

        // Remove surrounding quotes from the value
        $value = trim($value, '"\'');

        $_ENV[$name] = $value;
        $envVariables[$name] = $value;
    }

    return $envVariables;
}
?>

<?php
// env_loader.php

function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        throw new Exception("The .env file does not exist.");
    }

    $envVariables = [];

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Remove surrounding quotes from the value, if present
        $value = trim($value, '"\'');

        $_ENV[$name] = $value;  // Load into $_ENV
        $envVariables[$name] = $value; // Load into local array
    }

    return $envVariables;
}
?>

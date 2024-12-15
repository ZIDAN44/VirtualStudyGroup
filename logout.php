<?php
session_start();

// Check if a session exists before trying to destroy it
if (session_status() === PHP_SESSION_ACTIVE) {
    // Unset all session variables
    $_SESSION = [];

    // Destroy the session cookie (if it exists)
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();
}

// Redirect to the login page
header("Location: login.php");
exit();

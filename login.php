<?php
session_start();
include 'config.php';

// Redirect logged-in users to the dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error_message = "Username and Password are required.";
    } else {
        try {
            // Prepare the query to fetch user info
            $stmt = $conn->prepare("SELECT user_id, username, password FROM users WHERE username = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->bind_result($user_id, $fetched_username, $hashed_password);
            $user_found = $stmt->fetch();
            $stmt->close();

            if ($user_found) {
                // Verify the password
                if (password_verify($password, $hashed_password)) {
                    // Password is correct; set session variables
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $fetched_username;

                    // Redirect to the dashboard
                    echo json_encode(["status" => "success", "message" => "Login successful!"]);
                    exit();
                } else {
                    $error_message = "Invalid username or password.";
                }
            } else {
                $error_message = "Invalid username or password.";
            }
        } catch (Exception $e) {
            $error_message = "An error occurred. Please try again later.";
            error_log("Login error: " . $e->getMessage());
        } finally {
            $conn->close();
        }
    }

    if (!empty($error_message)) {
        echo json_encode(["status" => "error", "message" => $error_message]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Virtual Study Group</title>
    <link rel="stylesheet" href="css/login.css">

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <!-- Common JS (for showToast) -->
    <script src="js/common.js" defer></script>

    <!-- Login JS -->
    <script src="js/login.js" defer></script>
</head>
<body data-action="login.php">
    <?php include 'includes/header.php'; ?>
    
    <div class="login-container">
        <h1>Login</h1>
        <p>Welcome back! Please log in to continue.</p>
        <form id="login-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" placeholder="Enter your username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="login-button">Login</button>
        </form>
        <p class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </p>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

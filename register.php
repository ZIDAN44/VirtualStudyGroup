<?php
session_start();
include 'config.php';

// Redirect logged-in users to the dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $username = trim($_POST['username']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate required fields
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        echo json_encode([
            "status" => "error",
            "message" => "All fields are required."
        ]);
        exit();
    }

    if (strlen($username) < 4 || strlen($username) > 20 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        echo json_encode([
            "status" => "error",
            "message" => "Username must be 4-20 characters long and can only contain letters, numbers, and underscores."
        ]);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid email format. Please enter a valid email."
        ]);
        exit();
    }

    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        echo json_encode([
            "status" => "error",
            "message" => "Password must be at least 8 characters long and include an uppercase letter, a lowercase letter, and a number."
        ]);
        exit();
    }

    if ($password !== $confirm_password) {
        echo json_encode([
            "status" => "error",
            "message" => "Passwords do not match. Please ensure both passwords are the same."
        ]);
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Start database transaction
    $conn->begin_transaction();
    try {
        // Check for existing user by username or email
        $check_user_stmt = $conn->prepare("SELECT 1 FROM users WHERE username = ? OR email = ?");
        $check_user_stmt->bind_param("ss", $username, $email);
        $check_user_stmt->execute();
        $check_user_stmt->store_result();

        if ($check_user_stmt->num_rows > 0) {
            throw new Exception("Username or email already exists.");
        }
        $check_user_stmt->close();

        $insert_user_stmt = $conn->prepare("
            INSERT INTO users (username, email, password, profile_com, created_at, updated_at) 
            VALUES (?, ?, ?, 50, NOW(), NOW())
        ");
        $insert_user_stmt->bind_param("sss", $username, $email, $hashed_password);

        if (!$insert_user_stmt->execute()) {
            throw new Exception("Error creating user account.");
        }

        // Retrieve the inserted user's ID
        $new_user_id = $insert_user_stmt->insert_id;
        $insert_user_stmt->close();

        // Insert initial points into points_history
        $insert_points_history_stmt = $conn->prepare("
            INSERT INTO points_history (user_id, points_change, reason, created_at, updated_at) 
            VALUES (?, 50, 'Registration Bonus', NOW(), NOW())
        ");
        $insert_points_history_stmt->bind_param("i", $new_user_id);

        if (!$insert_points_history_stmt->execute()) {
            throw new Exception("Error recording points history.");
        }
        $insert_points_history_stmt->close();

        // Commit the transaction
        $conn->commit();

        // Set session and redirect
        echo json_encode([
            "status" => "success",
            "message" => "Registration successful! Redirecting..."
        ]);
        exit();
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
        exit();
    } finally {
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Virtual Study Group</title>
    <link rel="stylesheet" href="css/register.css">

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <!-- Common JS (for showToast) -->
    <script src="js/common.js" defer></script>

    <!-- Register JS -->
    <script src="js/register.js" defer></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="register-container">
        <h1>Register</h1>
        <p>Create an account to join Virtual Study Groups</p>
        <form id="register-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" placeholder="Enter your username" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" placeholder="Create a strong password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm your password" required>
            </div>

            <button type="submit" class="register-button">Register</button>
        </form>
        <p class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

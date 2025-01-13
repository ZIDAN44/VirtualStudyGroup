<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $username = trim($_POST['username']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate required fields
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error_message'] = "All fields are required.";
        header("Location: register.php");
        exit();
    }

    // Validate password match
    if ($password !== $confirm_password) {
        $_SESSION['error_message'] = "Passwords do not match.";
        header("Location: register.php");
        exit();
    }

    // Hash the password securely
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

        // Insert the new user into the database
        $insert_user_stmt = $conn->prepare("
            INSERT INTO users (username, email, password, profile_com, created_at, updated_at) 
            VALUES (?, ?, ?, 50, NOW(), NOW())
        ");
        $insert_user_stmt->bind_param("sss", $username, $email, $hashed_password);

        if (!$insert_user_stmt->execute()) {
            throw new Exception("Error inserting user: " . $insert_user_stmt->error);
        }

        // Retrieve the inserted user's ID
        $new_user_id = $insert_user_stmt->insert_id;
        $insert_user_stmt->close();

        // Insert initial points into points_history
        $insert_points_history_stmt = $conn->prepare("
            INSERT INTO points_history (user_id, points_change, reason, created_at, updated_at) 
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        $initial_points = 50;
        $reason = "Registration Bonus";
        $insert_points_history_stmt->bind_param("iis", $new_user_id, $initial_points, $reason);

        if (!$insert_points_history_stmt->execute()) {
            throw new Exception("Error inserting points history: " . $insert_points_history_stmt->error);
        }
        $insert_points_history_stmt->close();

        // Commit the transaction
        $conn->commit();

        // Set session and redirect to the dashboard
        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['username'] = $username;
        $_SESSION['success_message'] = "Registration successful!";
        header("Location: dashboard.php");
        exit();
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: register.php");
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
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h2>Register</h2>

    <!-- Display success or error messages -->
    <?php if (isset($_SESSION['error_message'])): ?>
        <p style="color: red;"><?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error_message']); ?></p>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <label for="username">Username:</label>
        <input type="text" name="username" required>

        <label for="email">Email:</label>
        <input type="email" name="email" required>

        <label for="password">Password:</label>
        <input type="password" name="password" required>

        <label for="confirm_password">Confirm Password:</label>
        <input type="password" name="confirm_password" required>

        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a></p>
</body>
</html>

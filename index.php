<?php
session_start();

// If the user is logged in, redirect to the dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Study Group</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <h1>Welcome to the Virtual Study Group</h1>
        <p>Create or join study groups, share resources, and chat with others.</p>
        <a href="login.php" class="button">Login</a>
        <a href="register.php" class="button">Register</a>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

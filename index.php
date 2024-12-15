<?php
session_start();

// Redirect logged-in users to the dashboard
if (!empty($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Virtual Study Group</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <h1>Welcome to the Virtual Study Group</h1>
        <p>Create or join study groups, share resources, and chat with others.</p>
        <div class="button-container">
            <a href="login.php" class="button">Login</a>
            <a href="register.php" class="button">Register</a>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

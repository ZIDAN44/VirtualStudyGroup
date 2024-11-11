<?php
// includes/header.php - Header section

echo '<header>
        <nav>
            <a href="' . (isset($_SESSION['user_id']) ? 'dashboard.php' : 'index.php') . '">Home</a> |';

// Only show the Dashboard and Logout links if the user is logged in
if (isset($_SESSION['user_id'])) {
    echo ' <a href="dashboard.php">Dashboard</a> | 
           <a href="logout.php">Logout</a>';
} else {
    // Only show the Login and Register links if the user is not logged in
    echo ' <a href="login.php">Login</a> | 
           <a href="register.php">Register</a>';
}

echo '    </nav>
      </header>';
?>

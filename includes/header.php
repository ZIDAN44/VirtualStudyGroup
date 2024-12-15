<?php
?>
<header>
    <nav>
        <a href="<?= isset($_SESSION['user_id']) ? 'dashboard.php' : 'index.php' ?>">Home</a> |
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="dashboard.php">Dashboard</a> |
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a> |
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>

<?php
?>
<header>
    <link rel="stylesheet" href="css/header.css">
    <nav>
        <!-- Left Links: Home -->
        <div class="left-links">
            <a href="<?= isset($_SESSION['user_id']) ? 'dashboard.php' : 'index.php' ?>">Home</a>
        </div>

        <!-- Right Links: Welcome Message and Profile -->
        <div class="right-links">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="user_profile.php?user_id=<?= $_SESSION['user_id'] ?>" class="welcome-message">
                    Welcome, <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php elseif (basename($_SERVER['PHP_SELF']) !== 'register.php' && basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
                <a href="login.php">Login</a> |
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

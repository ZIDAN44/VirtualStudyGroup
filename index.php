<?php
session_start();

// Redirect logged-in users to the dashboard
if (!empty($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

include 'config.php';

// Fetch groups to display for the guest user, excluding deleted groups, in random order
$pinned_groups_stmt = $conn->prepare("
    SELECT group_id, group_name, group_handle, group_picture, current_members
    FROM groups
    WHERE is_deleted = 0
    ORDER BY RAND()
    LIMIT 6
");
$pinned_groups_stmt->execute();
$pinned_groups_result = $pinned_groups_stmt->get_result();

// Define a list of specific gradient background classes
$gradient_classes = [
    "gradient-green",
    "gradient-blue",
    "gradient-pink",
    "gradient-purple",
    "gradient-teal",
    "gradient-dark-blue",
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Virtual Study Group</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" />

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <!-- Common JS (for showToast) -->
    <script src="js/common.js" defer></script>

    <!-- Index JS -->
    <script src="js/index.js" defer></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="dashboard-container">
        <a href="index.php" class="dashboard-header">
            <h1>Welcome to Virtual Study Group</h1>
        </a>

        <!-- Search Bar
        <div class="search-bar">
            <input type="text" id="search-groups" placeholder="Search for study groups...">
            <button id="search-btn">Search</button>
        </div> -->

        <!-- Quick Links
        <div class="quick-links">
            <button onclick="window.location.href='login.php'">Login</button>
            <button onclick="window.location.href='register.php'">Sign Up</button>
        </div> -->

        <!-- Featured Groups Section -->
        <h2>Explore Study Groups</h2>
        <div class="explore-sections" id="explore-sections">
            <?php if ($pinned_groups_result->num_rows > 0): ?>
                <?php while ($group = $pinned_groups_result->fetch_assoc()): ?>
                    <?php
                    $group_id = htmlspecialchars($group['group_id'], ENT_QUOTES, 'UTF-8');
                    $group_name = htmlspecialchars($group['group_name'], ENT_QUOTES, 'UTF-8');
                    $group_handle = htmlspecialchars($group['group_handle'], ENT_QUOTES, 'UTF-8');
                    $group_picture = !empty($group['group_picture'])
                        ? htmlspecialchars($group['group_picture'], ENT_QUOTES, 'UTF-8')
                        : $dummyGPImage;
                    $current_members = htmlspecialchars($group['current_members'], ENT_QUOTES, 'UTF-8');
                    $random_class = $gradient_classes[array_rand($gradient_classes)];
                    ?>
                    <div class="explore-card <?php echo $random_class; ?>" data-group-id="<?php echo $group_id; ?>">
                        <img src="<?php echo $group_picture; ?>" alt="Group Thumbnail" class="group-thumbnail">
                        <h3><?php echo $group_name; ?></h3>
                        <p class="group-handle"><?php echo $group_handle; ?></p>
                        <p class="group-members">Members: <?php echo $current_members; ?></p>
                        <button type="button" class="join-group-button">Join Group</button>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No groups available at the moment. Please check back later.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php $conn->close(); ?>

<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Define the number of groups per page
$groups_per_page = 5;

// Get the current page from the URL, default to 1 if not set or invalid
if (isset($_GET['page']) && is_numeric($_GET['page'])) {
    $current_page = (int) $_GET['page'];
    if ($current_page < 1) {
        $current_page = 1;
    }
} else {
    $current_page = 1;
}

// Calculate the OFFSET for SQL
$offset = ($current_page - 1) * $groups_per_page;

// Fetch total number of groups for pagination
$total_groups_stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM groups g
    JOIN group_members gm ON g.group_id = gm.group_id
    WHERE gm.user_id = ? AND g.is_deleted = 0
");
$total_groups_stmt->bind_param("i", $user_id);
$total_groups_stmt->execute();
$total_groups_result = $total_groups_stmt->get_result();
$total_groups = $total_groups_result->fetch_assoc()['total'];
$total_pages = ceil($total_groups / $groups_per_page);

// Fetch all groups the user is a member of, excluding deleted groups, with LIMIT and OFFSET
$my_groups_stmt = $conn->prepare("
    SELECT 
        g.group_id, 
        g.group_name, 
        g.group_handle, 
        g.group_picture, 
        g.current_members, 
        gm.role 
    FROM groups g
    JOIN group_members gm ON g.group_id = gm.group_id
    WHERE gm.user_id = ? AND g.is_deleted = 0
    ORDER BY g.created_at DESC
    LIMIT ? OFFSET ?
");
$my_groups_stmt->bind_param("iii", $user_id, $groups_per_page, $offset);
$my_groups_stmt->execute();
$my_groups_result = $my_groups_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Groups - Virtual Study Groups</title>
    <link rel="stylesheet" href="css/my_groups.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" />

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <!-- Common JS (for showToast) -->
    <script src="js/common.js" defer></script>

    <!-- Search Group JS -->
    <script src="js/search_group.js" defer></script>

    <!-- My Groups JS -->
    <script src="js/my_groups.js" defer></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="my-groups-container">
        <a href="my_groups.php" class="my-groups-header">
            <h1>My Groups</h1>
        </a>

        <!-- Search Bar -->
        <div class="search-bar">
            <input type="text" id="my-groups-search" placeholder="Search within your groups...">
            <button id="my-groups-search-btn">Search</button>
        </div>

        <!-- Search Results -->
        <div class="search-results" id="my-groups-search-results" style="display: none;">
            <h2>Search Results</h2>
            <ul class="my-groups-list" id="my-groups-list">
                <!-- Search results will be injected here -->
            </ul>
            <p id="no-search-results" style="display: none;">No groups found.</p>
            <!-- Pagination for Search Results -->
            <div class="pagination search-pagination" id="search-pagination" style="display: none;">
                <a href="#" class="pagination-btn" id="search-prev-btn">Previous</a>
                <span class="current-page" id="search-current-page">Page 1</span>
                <a href="#" class="pagination-btn" id="search-next-btn">Next</a>
            </div>
        </div>

        <!-- Original My Groups List -->
        <div class="original-groups-list" id="original-groups-list">
            <?php if ($my_groups_result->num_rows > 0): ?>
                <ul class="my-groups-list">
                    <?php while ($group = $my_groups_result->fetch_assoc()): ?>
                        <?php
                        $group_id = htmlspecialchars($group['group_id'], ENT_QUOTES, 'UTF-8');
                        $group_name = htmlspecialchars($group['group_name'], ENT_QUOTES, 'UTF-8');
                        $group_handle = htmlspecialchars($group['group_handle'], ENT_QUOTES, 'UTF-8');
                        $group_picture = !empty($group['group_picture'])
                            ? htmlspecialchars($group['group_picture'], ENT_QUOTES, 'UTF-8')
                            : $dummyGPImage;
                        $current_members = htmlspecialchars($group['current_members'], ENT_QUOTES, 'UTF-8');
                        $role = htmlspecialchars($group['role'], ENT_QUOTES, 'UTF-8');
                        ?>
                        <li class="group-item">
                            <img src="<?php echo $group_picture; ?>" alt="Group Thumbnail" class="group-thumbnail">
                            <div class="group-details">
                                <h3><?php echo $group_name; ?></h3>
                                <p class="group-handle"><?php echo $group_handle; ?></p>
                                <p class="group-members">Members: <?php echo $current_members; ?></p>
                                <p class="group-role">Role: <?php echo ucfirst($role); ?></p>
                            </div>
                            <a href="group.php?group_id=<?php echo $group_id; ?>" class="view-group-btn">View Group</a>
                        </li>
                    <?php endwhile; ?>
                </ul>

                <!-- Pagination Controls -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="my_groups.php?page=<?php echo $current_page - 1; ?>" class="pagination-btn">Previous</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">Previous</span>
                        <?php endif; ?>

                        <span class="current-page">Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="my_groups.php?page=<?php echo $current_page + 1; ?>" class="pagination-btn">Next</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">Next</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p>You are not a member of any groups.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

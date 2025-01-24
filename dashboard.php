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
$groups_per_page = 6;

// Get the current page for pinned groups from the URL, default to 1 if not set or invalid
if (isset($_GET['pinned_page']) && is_numeric($_GET['pinned_page'])) {
    $current_pinned_page = (int) $_GET['pinned_page'];
    if ($current_pinned_page < 1) {
        $current_pinned_page = 1;
    }
} else {
    $current_pinned_page = 1;
}

// Calculate the OFFSET for pinned groups
$pinned_offset = ($current_pinned_page - 1) * $groups_per_page;

// Fetch total number of pinned groups for pagination
$total_pinned_stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM user_pinned_groups upg
    JOIN groups g ON upg.group_id = g.group_id
    WHERE upg.user_id = ? AND g.is_deleted = 0
");
$total_pinned_stmt->bind_param("i", $user_id);
$total_pinned_stmt->execute();
$total_pinned_result = $total_pinned_stmt->get_result();
$total_pinned_groups = $total_pinned_result->fetch_assoc()['total'];
$total_pinned_pages = ceil($total_pinned_groups / $groups_per_page);

// Fetch pinned groups with LIMIT and OFFSET
$pinned_groups_stmt = $conn->prepare("
    SELECT g.group_id, g.group_name, g.group_handle, g.group_picture, g.current_members
    FROM groups g
    JOIN user_pinned_groups upg ON g.group_id = upg.group_id
    WHERE upg.user_id = ? AND g.is_deleted = 0
    ORDER BY upg.pinned_at DESC
    LIMIT ? OFFSET ?
");
$pinned_groups_stmt->bind_param("iii", $user_id, $groups_per_page, $pinned_offset);
$pinned_groups_stmt->execute();
$pinned_groups_result = $pinned_groups_stmt->get_result();

// Fetch only groups where the user is a member for the modal and not deleted
$all_groups_stmt = $conn->prepare("
    SELECT g.group_id, g.group_name, g.group_handle, g.group_picture, g.current_members,
    CASE
        WHEN g.group_id IN (SELECT group_id FROM user_pinned_groups WHERE user_id = ?) THEN 1
        ELSE 0
    END AS is_pinned
    FROM groups g
    JOIN group_members gm ON g.group_id = gm.group_id
    WHERE gm.user_id = ? AND g.is_deleted = 0
");
$all_groups_stmt->bind_param("ii", $user_id, $user_id);
$all_groups_stmt->execute();
$all_groups_result = $all_groups_stmt->get_result();

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
    <title>Dashboard - Virtual Study Group</title>
    <link rel="stylesheet" href="css/dashboard.css">
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

    <!-- Pin Manager JS -->
    <script src="js/pin_manager.js" defer></script>

    <!-- Dashboard JS -->
    <script src="js/dashboard.js" defer></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="dashboard-container">
        <a href="dashboard.php" class="dashboard-header">
            <h1>Welcome to Virtual Study Group</h1>
        </a>

        <!-- Search Bar -->
        <div class="search-bar">
            <input type="text" id="search-groups" placeholder="Search for study groups...">
            <button id="search-btn">Search</button>
        </div>

        <!-- Quick Links -->
        <div class="quick-links">
            <button onclick="window.location.href='create_group.php'">Create a Group</button>
            <button onclick="window.location.href='my_groups.php'">My Groups</button>
            <button id="customize-pins-btn">Customize your Pins</button>
        </div>

        <!-- Pinned Groups Section -->
        <h2>Pinned Groups</h2>
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
                        <a href="group.php?group_id=<?php echo $group_id; ?>" class="enter-group">View Group</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No groups pinned yet!</p>
            <?php endif; ?>
        </div>

        <!-- Pagination Controls for Pinned Groups -->
        <?php if ($total_pinned_pages > 1): ?>
            <div class="pagination">
                <?php if ($current_pinned_page > 1): ?>
                    <a href="dashboard.php?pinned_page=<?php echo $current_pinned_page - 1; ?>" class="pagination-btn">Previous</a>
                <?php else: ?>
                    <span class="pagination-btn disabled">Previous</span>
                <?php endif; ?>

                <span class="current-page">Page <?php echo $current_pinned_page; ?> of <?php echo $total_pinned_pages; ?></span>

                <?php if ($current_pinned_page < $total_pinned_pages): ?>
                    <a href="dashboard.php?pinned_page=<?php echo $current_pinned_page + 1; ?>" class="pagination-btn">Next</a>
                <?php else: ?>
                    <span class="pagination-btn disabled">Next</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Modal -->
        <div class="modal" id="group-modal">
            <div class="modal-content">
                <h2>Select Groups to Pin/Unpin</h2>
                <button class="close-modal" id="close-modal">&times;</button>

                <!-- Search Box for Modal -->
                <div class="modal-search-bar">
                    <input type="text" id="modal-search-groups" placeholder="Search groups...">
                </div>

                <div class="modal-groups" id="modal-groups">
                    <form id="pin-unpin-form">
                        <?php while ($group = $all_groups_result->fetch_assoc()): ?>
                            <?php
                            $group_id = htmlspecialchars($group['group_id'], ENT_QUOTES, 'UTF-8');
                            $group_name = htmlspecialchars($group['group_name'], ENT_QUOTES, 'UTF-8');
                            $group_handle = htmlspecialchars($group['group_handle'], ENT_QUOTES, 'UTF-8');
                            $group_picture = !empty($group['group_picture'])
                                ? htmlspecialchars($group['group_picture'], ENT_QUOTES, 'UTF-8')
                                : $dummyGPImage;
                            $is_pinned = $group['is_pinned'];
                            ?>
                            <div class="modal-group" data-group-id="<?php echo $group_id; ?>">
                                <label for="group-<?php echo $group_id; ?>" class="modal-group-label">
                                    <img src="<?php echo $group_picture; ?>" alt="Group Thumbnail" class="modal-group-thumbnail">
                                    <div class="modal-group-info">
                                        <h4><?php echo $group_name; ?> <span>(<?php echo $group_handle; ?>)</span></h4>
                                    </div>
                                </label>
                                <input type="checkbox"
                                    name="group_ids[]"
                                    value="<?php echo $group_id; ?>"
                                    id="group-<?php echo $group_id; ?>"
                                    <?php echo $is_pinned ? 'checked' : ''; ?>>
                            </div>
                        <?php endwhile; ?>
                        <button type="submit" class="save-button">Save Changes</button>
                    </form>
                </div>

                <!-- Modal Pagination Controls -->
                <div class="modal-pagination" id="modal-pagination">
                    <button id="modal-prev-btn" class="pagination-btn">Previous</button>
                    <span id="modal-current-page" class="current-page">Page 1 of 1</span>
                    <button id="modal-next-btn" class="pagination-btn">Next</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

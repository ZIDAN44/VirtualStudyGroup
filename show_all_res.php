<?php
session_start();
require_once 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must log in to view resources.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;

if (!$group_id) {
    $_SESSION['error_message'] = "Group not specified.";
    header("Location: dashboard.php");
    exit();
}

try {
    // Fetch the group name for sanitization
    $group_stmt = $conn->prepare("SELECT group_name FROM groups WHERE group_id = ?");
    $group_stmt->bind_param("i", $group_id);
    $group_stmt->execute();
    $group_stmt->bind_result($group_name);
    if (!$group_stmt->fetch()) {
        throw new Exception("Group not found.");
    }
    $group_stmt->close();

    // Sanitize group name for file URLs
    $sanitizedGroupName = preg_replace('/[^A-Za-z0-9]/', '', $group_name);

    // Fetch resources for the group
    $resources_stmt = $conn->prepare("
        SELECT 
            r.resource_id, r.file_name, r.file_path, u.username AS uploader, 
            COUNT(rv.vote_id) AS vote_count
        FROM resources r
        LEFT JOIN users u ON r.uploaded_by = u.user_id
        LEFT JOIN resource_votes rv ON r.resource_id = rv.resource_id
        WHERE r.group_id = ?
        GROUP BY r.resource_id
        ORDER BY r.upload_time DESC
    ");
    $resources_stmt->bind_param("i", $group_id);
    $resources_stmt->execute();
    $resources_result = $resources_stmt->get_result();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Failed to retrieve resources: " . $e->getMessage();
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources for Group <?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="css/show_all_res.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" />

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" crossorigin="anonymous"></script>

    <!-- Common JS -->
    <script src="js/common.js" defer></script>

    <!-- Show All Resources JS -->
    <script src="js/show_all_res.js" defer></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Add Back Button below Header -->
    <div class="back-button-container">
        <a href="<?php
                    echo isset($group_id)
                        ? "group.php?group_id=$group_id"
                        : "dashboard.php";
                    ?>"
            class="back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <h2>Resources for Group <?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?></h2>

    <!-- Why Vote Section -->
    <div class="why-vote-container">
        <h3>Why Vote for Resources?</h3>
        <p>
            By voting for resources, you help highlight the most valuable and useful content for the group.
        </p>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <script>
            showToast("<?php echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); ?>", "success");
        </script>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <script>
            showToast("<?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); ?>", "error");
        </script>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <?php if ($resources_result->num_rows > 0): ?>
        <table class="resource-table">
            <thead>
                <tr>
                    <th>Resource</th>
                    <th>Uploader</th>
                    <th>Votes</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($resource = $resources_result->fetch_assoc()): ?>
                    <?php
                    $fileUrlBase = "{$minioHost}/{$minioBucketName}/{$sanitizedGroupName}_{$group_id}/res/" . htmlspecialchars($resource['file_path'], ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo $fileUrlBase; ?>" target="_blank">
                                <?php echo htmlspecialchars($resource['file_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($resource['uploader'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td id="vote-count-<?php echo $resource['resource_id']; ?>">
                            <?php echo htmlspecialchars($resource['vote_count'], ENT_QUOTES, 'UTF-8'); ?>
                        </td>
                        <td>
                            <button
                                class="vote-btn"
                                data-resource-id="<?php echo $resource['resource_id']; ?>">
                                Vote
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-resources">No resources found for this group.</p>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

<?php $resources_stmt->close(); $conn->close(); ?>

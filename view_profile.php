<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get the user ID to view
$user_id = $_SESSION['user_id'];
$view_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;

if (!$view_user_id) {
    $_SESSION['error_message'] = "User not specified.";
    header("Location: dashboard.php");
    exit();
}

try {
    // Fetch user details
    $user_stmt = $conn->prepare("
        SELECT 
            user_id, username, full_name, email, phone_number, profile_picture, status_message, 
            date_of_birth, gender, geo_location, created_at, profile_com, points
        FROM users 
        WHERE user_id = ?
    ");
    $user_stmt->bind_param("i", $view_user_id);
    $user_stmt->execute();
    $result = $user_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("User not found.");
    }

    $user_data = $result->fetch_assoc();
    $user_stmt->close();

    // Fallback to dummy image if profile picture is empty
    $profile_picture = !empty($user_data['profile_picture'])
        ? htmlspecialchars($user_data['profile_picture'], ENT_QUOTES, 'UTF-8')
        : htmlspecialchars($dummyUPImage, ENT_QUOTES, 'UTF-8');
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile of <?php echo htmlspecialchars($user_data['username'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="css/user_profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" />
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Add Back Button below Header -->
    <div class="back-button-container">
        <a href="<?php
                    if (isset($_GET['all_members']) && $_GET['all_members'] === '1') {
                        echo "all_group_members.php?group_id=" . htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8');
                    } elseif (isset($group_id)) {
                        echo "manage_join_requests.php?group_id=" . htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8');
                    } else {
                        echo "dashboard.php";
                    }
                    ?>"
            class="back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>


    <!-- Profile Header Section -->
    <div class="profile-header">
        <div class="profile-picture-container">
            <img
                src="<?php echo $profile_picture; ?>"
                alt="<?php echo htmlspecialchars($user_data['username'], ENT_QUOTES, 'UTF-8'); ?> Thumbnail"
                class="profile-header-thumbnail">
        </div>
        <h2><?php echo htmlspecialchars($user_data['username'], ENT_QUOTES, 'UTF-8'); ?></h2>
    </div>

    <!-- Profile Details -->
    <div class="profile-container">
        <table class="profile-details">
            <tr>
                <th>User Name:</th>
                <td><?php echo !empty($user_data['username']) ? htmlspecialchars($user_data['username'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?></td>
            </tr>
            <tr>
                <th>Full Name:</th>
                <td><?php echo !empty($user_data['full_name']) ? htmlspecialchars($user_data['full_name'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?></td>
            </tr>
            <tr>
                <th>Email:</th>
                <td><?php echo !empty($user_data['email']) ? htmlspecialchars($user_data['email'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?></td>
            </tr>
            <tr>
                <th>Phone Number:</th>
                <td><?php echo !empty($user_data['phone_number']) ? htmlspecialchars($user_data['phone_number'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?></td>
            </tr>
            <tr>
                <th>Status Message:</th>
                <td><?php echo !empty($user_data['status_message']) ? htmlspecialchars($user_data['status_message'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?></td>
            </tr>
            <tr>
                <th>Date of Birth:</th>
                <td><?php echo !empty($user_data['date_of_birth']) ? htmlspecialchars($user_data['date_of_birth'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?></td>
            </tr>
            <tr>
                <th>Gender:</th>
                <td><?php echo !empty($user_data['gender']) ? htmlspecialchars($user_data['gender'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?></td>
            </tr>
            <tr>
                <th>Geo Location:</th>
                <td><?php echo !empty($user_data['geo_location']) ? htmlspecialchars($user_data['geo_location'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?></td>
            </tr>
            <tr>
                <th>Points:</th>
                <td>
                    <a href="points_history.php?user_id=<?php echo htmlspecialchars($user_data['user_id'], ENT_QUOTES, 'UTF-8'); ?>&group_id=<?php echo isset($group_id) ? htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8') : ''; ?>" class="points-link">
                        <?php echo !empty($user_data['points']) ? htmlspecialchars($user_data['points'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?> Points
                    </a>
                </td>
            </tr>
            <tr>
                <th>Profile Completion:</th>
                <td>
                    <div class="profile-completion-container">
                        <span id="profileComText"><?php echo !empty($user_data['profile_com']) ? htmlspecialchars($user_data['profile_com'], ENT_QUOTES, 'UTF-8') . '%' : 'N/A'; ?></span>
                        <div class="progress-bar">
                            <div class="progress" id="profileComBar" style="width: <?php echo !empty($user_data['profile_com']) ? htmlspecialchars($user_data['profile_com'], ENT_QUOTES, 'UTF-8') . '%' : '0%'; ?>;"></div>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <th>Account Created:</th>
                <td><?php echo !empty($user_data['created_at']) ? htmlspecialchars($user_data['created_at'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?></td>
            </tr>
        </table>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>

</html>
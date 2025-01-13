<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must log in to view your profile.";
    header("Location: login.php");
    exit();
}

// Fetch user details from the database
$user_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare("
    SELECT 
        user_id, username, full_name, email, phone_number, profile_picture, status_message, 
        date_of_birth, gender, geo_location, created_at, updated_at, profile_com, points
    FROM users 
    WHERE user_id = ?
");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$result = $user_stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: logout.php");
    exit();
}

$user = $result->fetch_assoc();
$user_stmt->close();

// Fallback to dummy image if profile picture is empty
$profile_picture = !empty($user['profile_picture']) 
    ? htmlspecialchars($user['profile_picture'], ENT_QUOTES, 'UTF-8') 
    : htmlspecialchars($dummyUPImage, ENT_QUOTES, 'UTF-8');

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    if ($_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        include 'upload_profile_pic.php';

        // Upload profile picture
        $fileUrl = uploadProfilePicture($_FILES['profile_picture'], $user['user_id']);
        if ($fileUrl) {
            // Update profile picture URL in the database
            $update_pic_stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
            $update_pic_stmt->bind_param("si", $fileUrl, $user_id);
            if ($update_pic_stmt->execute()) {
                $_SESSION['success_message'] = "Profile picture updated successfully.";
            } else {
                $_SESSION['error_message'] = "Error updating profile picture: " . $update_pic_stmt->error;
            }
            $update_pic_stmt->close();
        } else {
            $_SESSION['error_message'] = "Failed to upload profile picture.";
        }
    } else {
        $_SESSION['error_message'] = "Error uploading file.";
    }

    header("Location: user_profile.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Profile</title>
    <link rel="stylesheet" href="css/user_profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" />

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <script src="js/common.js" defer></script>
    <script src="js/user_profile.js" defer></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hidden Element to Store user_id -->
    <span id="userId" style="display: none;"><?php echo htmlspecialchars($user_id, ENT_QUOTES, 'UTF-8'); ?></span>

    <!-- Profile Header Section -->
    <div class="profile-header">
        <div class="profile-picture-container">
            <img 
                src="<?php echo $profile_picture; ?>" 
                alt="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?> Thumbnail" 
                class="profile-header-thumbnail"
                id="profileThumbnail"
            >
            <div class="overlay" id="profilePictureOverlay">
                <i class="fas fa-camera camera-icon"></i>
            </div>
        </div>

        <h2>
            <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>
        </h2>
    </div>

    <!-- Display success or error messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <p style="color: green;"><?php echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success_message']); ?></p>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <p style="color: red;"><?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error_message']); ?></p>
    <?php endif; ?>

    <table>
        <tr><th>User ID:</th><td><?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
        <tr><th>Username:</th><td><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
        <tr>
            <th>Full Name:</th>
            <td class="editable" data-field="full_name">
                <?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?: 'Click to edit'; ?>
            </td>
        </tr>
        <tr>
            <th>Email:</th>
            <td><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <th>Phone Number:</th>
            <td class="editable" data-field="phone_number">
                <?php echo htmlspecialchars($user['phone_number'], ENT_QUOTES, 'UTF-8') ?: 'Click to edit'; ?>
            </td>
        </tr>
        <tr>
            <th>Status Message:</th>
            <td class="editable" data-field="status_message">
                <?php echo htmlspecialchars($user['status_message'], ENT_QUOTES, 'UTF-8') ?: 'Click to edit'; ?>
            </td>
        </tr>
        <tr>
            <th>Date of Birth:</th>
            <td class="editable" data-field="date_of_birth">
                <?php echo htmlspecialchars($user['date_of_birth'], ENT_QUOTES, 'UTF-8') ?: 'Click to edit'; ?>
            </td>
        </tr>
        <tr>
            <th>Gender:</th>
            <td class="editable" data-field="gender">
                <?php echo htmlspecialchars($user['gender'], ENT_QUOTES, 'UTF-8') ?: 'Click to edit'; ?>
            </td>
        </tr>
        <tr>
            <th>Geo Location:</th>
            <td class="editable" data-field="geo_location">
                <?php echo htmlspecialchars($user['geo_location'], ENT_QUOTES, 'UTF-8') ?: 'Click to edit'; ?>
            </td>
        </tr>
        <tr>
            <th>Points:</th>
            <td>
                <a href="points_history.php" class="points-link">
                    <?php echo htmlspecialchars($user['points'], ENT_QUOTES, 'UTF-8'); ?> Points
                </a>
            </td>
        </tr>
        <tr>
            <th>Profile Completion:</th>
            <td>
                <div id="profileComText"><?php echo htmlspecialchars($user['profile_com'] . '%', ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="progress-bar">
                    <div class="progress" id="profileComBar" style="width: <?php echo htmlspecialchars($user['profile_com'], ENT_QUOTES, 'UTF-8'); ?>%;"></div>
                </div>
            </td>
        </tr>
        <tr>
            <th>Account Created:</th>
            <td id="createDate">
                <?php echo htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8'); ?>
            </td>
        </tr>
        <tr>
            <th>Last Updated:</th>
            <td id="lastUpdated">
                <?php echo htmlspecialchars($user['updated_at'], ENT_QUOTES, 'UTF-8'); ?>
            </td>
        </tr>
    </table>

    <!-- Hidden Profile Picture Upload Form (triggered via JavaScript) -->
    <form 
        action="user_profile.php" 
        method="POST" 
        enctype="multipart/form-data"
        id="profilePicForm"
        style="display: none;" 
    >
        <input 
            type="file" 
            name="profile_picture" 
            accept="image/*" 
            id="profilePicInput" 
            onchange="document.getElementById('profilePicForm').submit();"
        >
    </form>
</body>
</html>

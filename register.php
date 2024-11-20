<?php
session_start();
include 'config.php';

// Fetch available time zones
$timeZones = timezone_identifiers_list();

// Fetch geo locations (mocking online data)
$geoLocations = ["Bangladesh", "USA", "Canada", "Germany", "Australia"];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $phone_number = trim($_POST['phone_number']);
    $full_name = trim($_POST['full_name'] ?? '');
    $status_message = trim($_POST['status_message'] ?? '');
    $language_pref = $_POST['language_pref'] ?? 'en';
    $time_zone = $_POST['time_zone'] ?? null;
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $geo_location = $_POST['geo_location'] ?? null;
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Validate required fields
    if (empty($username) || empty($email) || empty($password) || empty($phone_number)) {
        die("Username, email, password, and phone number are required.");
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Check if username, email, or phone number already exists
        $check_user = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ? OR phone_number = ?");
        $check_user->bind_param("sss", $username, $email, $phone_number);
        $check_user->execute();
        $result = $check_user->get_result();

        if ($result->num_rows > 0) {
            throw new Exception("Username, email, or phone number already exists. Please choose a different one.");
        }

        // Insert new user into the database
        $stmt = $conn->prepare(
            "INSERT INTO users (username, email, password, phone_number, full_name, status_message, language_pref, time_zone, date_of_birth, gender, geo_location, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $stmt->bind_param(
            "sssssssssss",
            $username,
            $email,
            $hashed_password,
            $phone_number,
            $full_name,
            $status_message,
            $language_pref,
            $time_zone,
            $date_of_birth,
            $gender,
            $geo_location
        );

        if (!$stmt->execute()) {
            throw new Exception("Error inserting user: " . $stmt->error);
        }

        // Get the newly inserted user's ID
        $user_id = $conn->insert_id;

        // Handle profile picture upload if provided
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            include 'upload_profile_pic.php';
            $fileUrl = uploadProfilePicture($_FILES['profile_picture'], $user_id);

            if (!$fileUrl) {
                throw new Exception("Failed to upload profile picture.");
            }

            // Update user record with profile picture URL
            $update_profile_pic = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
            $update_profile_pic->bind_param("si", $fileUrl, $user_id);
            if (!$update_profile_pic->execute()) {
                throw new Exception("Error updating profile picture: " . $update_profile_pic->error);
            }
        }

        // Commit transaction
        $conn->commit();

        // Set session and redirect
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        header("Location: dashboard.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on failure
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    } finally {
        // Close resources
        $check_user->close();
        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Virtual Study Group</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h2>Register</h2>
    <form action="register.php" method="POST" enctype="multipart/form-data">
        <label for="username">Username:</label>
        <input type="text" name="username" required>

        <label for="email">Email:</label>
        <input type="email" name="email" required>

        <label for="password">Password:</label>
        <input type="password" name="password" required>

        <label for="phone_number">Phone Number:</label>
        <input type="text" name="phone_number" required>

        <label for="full_name">Full Name:</label>
        <input type="text" name="full_name">

        <label for="status_message">Status Message:</label>
        <input type="text" name="status_message">

        <label for="language_pref">Preferred Language:</label>
        <select name="language_pref">
            <option value="en" selected>English</option>
        </select>

        <label for="time_zone">Time Zone:</label>
        <select name="time_zone">
            <?php foreach ($timeZones as $timeZone): ?>
                <option value="<?= htmlspecialchars($timeZone) ?>" <?= $timeZone === 'Asia/Dhaka' ? 'selected' : '' ?>>
                    <?= htmlspecialchars($timeZone) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="date_of_birth">Date of Birth:</label>
        <input type="date" name="date_of_birth">

        <label for="gender">Gender:</label>
        <div>
            <label><input type="radio" name="gender" value="Male" checked> Male</label>
            <label><input type="radio" name="gender" value="Female"> Female</label>
            <label><input type="radio" name="gender" value="Others"> Others</label>
        </div>

        <label for="geo_location">Geo Location:</label>
        <select name="geo_location">
            <?php foreach ($geoLocations as $geoLocation): ?>
                <option value="<?= htmlspecialchars($geoLocation) ?>"><?= htmlspecialchars($geoLocation) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="profile_picture">Profile Picture:</label>
        <input type="file" name="profile_picture" accept="image/*">

        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a></p>
</body>
</html>

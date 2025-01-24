<?php
session_start();
include 'config.php';
include 'upload_group_pic.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;

if (!$group_id) {
    echo json_encode(["status" => "error", "message" => "Group not specified."]);
    exit();
}

// Check if the user is an Admin or a Co-Admin with permission
$permissions_stmt = $conn->prepare("
    SELECT gm.role, cp.can_edit_group_info 
    FROM group_members gm
    LEFT JOIN coadmin_permissions cp 
    ON gm.user_id = cp.user_id AND gm.group_id = cp.group_id
    WHERE gm.user_id = ? AND gm.group_id = ?
");
$permissions_stmt->bind_param("ii", $user_id, $group_id);
$permissions_stmt->execute();
$permissions_stmt->bind_result($user_role, $can_edit_group_info);
$permissions_stmt->fetch();
$permissions_stmt->close();

if ($user_role !== 'Admin' && (!$can_edit_group_info || $user_role !== 'Co-Admin')) {
    echo json_encode(["status" => "error", "message" => "You are not authorized to edit group information."]);
    exit();
}

// Fetch group details
$group_stmt = $conn->prepare("
    SELECT group_name, group_handle, description, group_picture, max_members, join_rule, rules, current_members, req_point
    FROM groups 
    WHERE group_id = ?
");
$group_stmt->bind_param("i", $group_id);
$group_stmt->execute();
$group_stmt->bind_result($group_name, $group_handle, $description, $group_picture, $max_members, $join_rule, $rules, $current_members, $req_point);
$group_stmt->fetch();
$group_stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_group_name = trim($_POST['group_name']);
    $new_group_handle = trim($_POST['group_handle']);
    $new_description = trim($_POST['description']);
    $new_join_rule = $_POST['join_rule'];
    $new_rules = $_POST['rules'] ?? null;
    $new_max_members = isset($_POST['max_members']) && $_POST['max_members'] !== '' ? (int)$_POST['max_members'] : 15;
    $new_req_point_input = trim($_POST['req_point'] ?? '');

    // Ensure maximum members is not less than current members
    if ($new_max_members < $current_members) {
        echo json_encode([
            "status" => "error",
            "message" => "Maximum members cannot be less than the current number of members ($current_members)."
        ]);
        exit();
    }

    // Validate required points
    if (!is_numeric($new_req_point_input) || (int)$new_req_point_input < 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Required points must be a non-negative integer."
        ]);
        exit();
    }
    $new_req_point = (int)$new_req_point_input;

    // Check for unique group handle
    $handle_check_stmt = $conn->prepare("SELECT 1 FROM groups WHERE group_handle = ? AND group_id != ?");
    $handle_check_stmt->bind_param("si", $new_group_handle, $group_id);
    $handle_check_stmt->execute();
    $handle_check_stmt->store_result();

    if ($handle_check_stmt->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "The group handle '$new_group_handle' is already taken."]);
        $handle_check_stmt->close();
        exit();
    }
    $handle_check_stmt->close();

    // Handle group picture upload
    if (isset($_FILES['group_picture']) && $_FILES['group_picture']['error'] === 0) {
        try {
            $new_group_picture = uploadGroupPicture($_FILES['group_picture'], $group_id);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            exit();
        }
    } else {
        $new_group_picture = $group_picture;
    }

    // Update group information
    $update_stmt = $conn->prepare("
        UPDATE groups 
        SET group_name = ?, 
            group_handle = ?, 
            description = ?, 
            group_picture = ?, 
            max_members = ?, 
            join_rule = ?, 
            rules = ?, 
            req_point = ? 
        WHERE group_id = ?
    ");
    $update_stmt->bind_param(
        "ssssissii",
        $new_group_name,
        $new_group_handle,
        $new_description,
        $new_group_picture,
        $new_max_members,
        $new_join_rule,
        $new_rules,
        $new_req_point,
        $group_id
    );

    if ($update_stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Group information updated successfully!"]);
        exit();
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update group information."]);
    }

    $update_stmt->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Group Info - <?php echo htmlspecialchars($group_name, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="css/edit_group_info.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" />

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <!-- Common JS -->
    <script src="js/common.js"></script>

    <!-- Edit Group Info JS -->
    <script src="js/edit_group_info.js" defer></script>
</head>
<body data-group-id="<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>">
    <?php include 'includes/header.php'; ?>

    <!-- Back Button -->
    <div class="back-button-container">
        <a href="<?php echo isset($group_id) ? "group.php?group_id=$group_id" : "dashboard.php"; ?>" class="back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <h2>Edit Group Information</h2>

    <form id="edit-group-form" enctype="multipart/form-data">
        <div class="form-group">
            <label for="group_name">Group Name:</label>
            <input type="text" name="group_name" value="<?php echo htmlspecialchars($group_name, ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>

        <div class="form-group">
            <label for="group_handle">Group Handle:</label>
            <input type="text" name="group_handle" value="<?php echo htmlspecialchars($group_handle, ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>

        <div class="form-group">
            <label for="description">Description:</label>
            <textarea name="description" rows="5"><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="form-group">
            <label for="group_picture">Group Picture:</label>
            <?php if ($group_picture): ?>
                <p>Current Picture: <img src="<?php echo htmlspecialchars($group_picture, ENT_QUOTES, 'UTF-8'); ?>" alt="Group Picture" style="max-width: 100px;"></p>
            <?php endif; ?>
            <input type="file" name="group_picture" accept="image/*">

        </div>

        <div class="form-group">
            <label for="max_members">Maximum Members:</label>
            <input type="number" name="max_members" value="<?php echo htmlspecialchars($max_members, ENT_QUOTES, 'UTF-8'); ?>" min="1" max="1000" placeholder="Default: 15">
        </div>

        <div class="radio-group">
            <label>
                <input type="radio" name="join_rule" value="auto" <?php echo $join_rule === 'auto' ? 'checked' : ''; ?>>
                New members can join without approval
            </label>
            <label>
                <input type="radio" name="join_rule" value="manual" <?php echo $join_rule === 'manual' ? 'checked' : ''; ?>>
                New members must wait for Admin approval
            </label>
        </div>

        <div class="form-group">
            <label for="rules">Group Rules:</label>
            <textarea name="rules" rows="5"><?php echo htmlspecialchars($rules, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="form-group">
            <label for="req_point">Required Points to Join:</label>
            <input type="number" name="req_point" min="0" value="<?php echo htmlspecialchars($req_point, ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>

        <button type="submit">Save Changes</button>
    </form>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

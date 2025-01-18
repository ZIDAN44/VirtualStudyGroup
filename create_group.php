<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Generate a unique group handle
function generateUniqueGroupHandle($conn, $group_name) {
    do {
        $base_handle = '@' . strtolower(preg_replace('/[^A-Za-z0-9]/', '', $group_name));
        $unique_handle = $base_handle . rand(1000, 9999);

        $stmt = $conn->prepare("SELECT 1 FROM groups WHERE group_handle = ?");
        $stmt->bind_param("s", $unique_handle);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
    } while ($exists);

    return $unique_handle;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_name = trim($_POST['group_name']);
    $description = trim($_POST['description'] ?? '');
    $created_by = $_SESSION['user_id'];
    $max_members = isset($_POST['max_members']) && $_POST['max_members'] !== '' ? (int)$_POST['max_members'] : 15;
    $join_rule = in_array($_POST['join_rule'], ['auto', 'manual']) ? $_POST['join_rule'] : 'auto';
    $rules = trim($_POST['rules'] ?? '');
    $req_point_input = trim($_POST['req_point'] ?? '');

    if (empty($group_name)) {
        echo json_encode(["status" => "error", "message" => "Group name is required."]);
        exit();
    } elseif (strlen($group_name) > 255) {
        echo json_encode(["status" => "error", "message" => "Group name must not exceed 255 characters."]);
        exit();
    } elseif (!is_numeric($req_point_input) || (int)$req_point_input < 0) {
        echo json_encode(["status" => "error", "message" => "Required points must be a non-negative integer."]);
        exit();
    } else {
        $req_point = (int)$req_point_input;

        $conn->begin_transaction();
        $stmt = null;
        $member_stmt = null;

        try {
            $group_handle = generateUniqueGroupHandle($conn, $group_name);

            $stmt = $conn->prepare("
                INSERT INTO groups (group_name, group_handle, description, created_by, max_members, join_rule, rules, req_point, current_members, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
            ");
            $stmt->bind_param("sssisssi", $group_name, $group_handle, $description, $created_by, $max_members, $join_rule, $rules, $req_point);

            if (!$stmt->execute()) {
                throw new Exception("Error creating study group: " . $stmt->error);
            }

            $group_id = $stmt->insert_id;

            $member_stmt = $conn->prepare("INSERT INTO group_members (user_id, group_id, role) VALUES (?, ?, 'Admin')");
            $member_stmt->bind_param("ii", $created_by, $group_id);

            if (!$member_stmt->execute()) {
                throw new Exception("Group created, but there was an error adding you as Admin: " . $member_stmt->error);
            }

            $conn->commit();

            echo json_encode(["status" => "success", "message" => "Study group created successfully!"]);
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            exit();
        } finally {
            if ($stmt !== null) {
                $stmt->close();
            }
            if ($member_stmt !== null) {
                $member_stmt->close();
            }
            $conn->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Virtual Study Group</title>
    <link rel="stylesheet" href="css/create_group.css">

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <!-- Common JS -->
    <script src="js/common.js" defer></script>

    <!-- Create Group JS -->
    <script src="js/create_group.js" defer></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <h2>Create Virtual Study Group</h2>

    <form action="create_group.php" method="POST">
        <div class="form-group">
            <label for="group_name">Group Name:</label>
            <input type="text" name="group_name" maxlength="255" required>
        </div>

        <div class="form-group">
            <label for="description">Description:</label>
            <textarea name="description" rows="5"></textarea>
        </div>

        <div class="form-group">
            <label for="max_members">Maximum Members:</label>
            <input type="number" name="max_members" min="1" max="1000" placeholder="Default: 15">
        </div>

        <div class="form-group">
            <label for="join_rule">Join Rule:</label>
            <select name="join_rule" required>
                <option value="" disabled selected>-- Select Join Rule --</option>
                <option value="auto">No Approval Required</option>
                <option value="manual">Admin Approval Required</option>
            </select>
        </div>

        <div class="form-group">
            <label for="rules">Group Rules:</label>
            <textarea name="rules" rows="5" placeholder="Optional: Add group-specific rules or policies"></textarea>
        </div>

        <div class="form-group">
            <label for="req_point">Required Points to Join:</label>
            <input type="number" name="req_point" min="0" value="0" required>
        </div>

        <button type="submit">Create Group</button>
    </form>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

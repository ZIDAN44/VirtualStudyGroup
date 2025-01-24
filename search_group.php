<?php
// search_group.php
session_start();
include 'config.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "You are not logged in."]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Retrieve and sanitize input
$type = isset($_GET['type']) ? trim($_GET['type']) : '';

if ($type === 'explore') {
    // Handle explore search (GET request with 'query' and optional 'page')
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        echo json_encode(["status" => "error", "message" => "Invalid request method."]);
        exit();
    }

    $query = isset($_GET['query']) ? trim($_GET['query']) : '';
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $groups_per_page = 5;
    $offset = ($page - 1) * $groups_per_page;

    if (empty($query)) {
        echo json_encode(["status" => "error", "message" => "Search query cannot be empty."]);
        exit();
    }

    // Define gradient classes
    $gradient_classes = ["gradient-green", "gradient-blue", "gradient-pink", "gradient-purple", "gradient-teal", "gradient-dark-blue"];

    // Explore search SQL
    $search_sql = "
        SELECT 
            g.group_id, 
            g.group_name, 
            g.group_handle, 
            g.group_picture, 
            g.current_members,
            CASE
                WHEN gm.user_id IS NOT NULL THEN 1
                ELSE 0
            END AS is_member
        FROM groups g
        LEFT JOIN group_members gm ON g.group_id = gm.group_id AND gm.user_id = ?
        WHERE g.is_deleted = 0
          AND (g.group_name LIKE CONCAT('%', ?, '%') OR g.group_handle LIKE CONCAT('%', ?, '%'))
        ORDER BY g.created_at DESC
        LIMIT ? OFFSET ?
    ";
    $search_stmt = $conn->prepare($search_sql);
    $search_stmt->bind_param("issii", $user_id, $query, $query, $groups_per_page, $offset);
    $search_stmt->execute();
    $search_result = $search_stmt->get_result();

    // Get total count for pagination
    $count_sql = "
        SELECT COUNT(*) AS total
        FROM groups g
        WHERE g.is_deleted = 0
          AND (g.group_name LIKE CONCAT('%', ?, '%') OR g.group_handle LIKE CONCAT('%', ?, '%'))
    ";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("ss", $query, $query);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_groups = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_groups / $groups_per_page);

    if ($search_result->num_rows > 0) {
        $groups = [];
        while ($row = $search_result->fetch_assoc()) {
            $groups[] = [
                "groupId" => htmlspecialchars($row['group_id'], ENT_QUOTES, 'UTF-8'),
                "groupName" => htmlspecialchars($row['group_name'], ENT_QUOTES, 'UTF-8'),
                "groupHandle" => htmlspecialchars($row['group_handle'], ENT_QUOTES, 'UTF-8'),
                "groupPicture" => !empty($row['group_picture']) ? htmlspecialchars($row['group_picture'], ENT_QUOTES, 'UTF-8') : $dummyGPImage,
                "currentMembers" => htmlspecialchars($row['current_members'], ENT_QUOTES, 'UTF-8'),
                "isMember" => (bool)$row['is_member'],
                "gradientClass" => $gradient_classes[array_rand($gradient_classes)],
            ];
        }
        echo json_encode([
            "status" => "success",
            "groups" => $groups,
            "currentPage" => $page,
            "totalPages" => $total_pages
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "No groups found."]);
    }

    $search_stmt->close();
    $count_stmt->close();
    $conn->close();

} elseif ($type === 'pins') {
    // Handle pin/unpin (POST request with 'group_ids')
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(["status" => "error", "message" => "Invalid request method."]);
        exit();
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['group_ids']) || !is_array($data['group_ids'])) {
        echo json_encode(["status" => "error", "message" => "Invalid request data."]);
        exit();
    }

    $group_ids = array_map('intval', $data['group_ids']); // Sanitize group IDs

    // Define the maximum number of pins allowed
    $MAX_PINS = 6;

    // Fetch existing pinned groups
    $existing_group_ids_stmt = $conn->prepare("SELECT group_id FROM user_pinned_groups WHERE user_id = ?");
    $existing_group_ids_stmt->bind_param("i", $user_id);
    $existing_group_ids_stmt->execute();
    $existing_result = $existing_group_ids_stmt->get_result();
    $existing_group_ids = [];
    while ($row = $existing_result->fetch_assoc()) {
        $existing_group_ids[] = $row['group_id'];
    }
    $existing_group_ids_stmt->close();

    // Determine groups to pin and unpin
    $groups_to_pin = array_diff($group_ids, $existing_group_ids);
    $groups_to_unpin = array_diff($existing_group_ids, $group_ids);

    // Validate the total number of pinned groups does not exceed the maximum allowed
    if (count($existing_group_ids) - count($groups_to_unpin) + count($groups_to_pin) > $MAX_PINS) {
        echo json_encode(['status' => 'error', 'message' => "You can only pin up to {$MAX_PINS} groups."]);
        exit();
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Pin new groups, ensuring they are not deleted
        if (!empty($groups_to_pin)) {
            $pin_stmt = $conn->prepare("
                INSERT INTO user_pinned_groups (user_id, group_id)
                SELECT ?, group_id FROM groups WHERE group_id = ? AND is_deleted = 0
            ");
            foreach ($groups_to_pin as $group_id) {
                $pin_stmt->bind_param("ii", $user_id, $group_id);
                $pin_stmt->execute();
            }
            $pin_stmt->close();
        }

        // Unpin groups
        if (!empty($groups_to_unpin)) {
            $unpin_stmt = $conn->prepare("DELETE FROM user_pinned_groups WHERE user_id = ? AND group_id = ?");
            foreach ($groups_to_unpin as $group_id) {
                $unpin_stmt->bind_param("ii", $user_id, $group_id);
                $unpin_stmt->execute();
            }
            $unpin_stmt->close();
        }

        // Commit transaction
        $conn->commit();

        echo json_encode(['status' => 'success', 'message' => 'Pins updated successfully.']);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Failed to update pins. Please try again.']);
    }

    $conn->close();

} elseif ($type === 'my_groups') {
    // Handle search within "My Groups" (GET request with 'query' and 'page')
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        echo json_encode(["status" => "error", "message" => "Invalid request method."]);
        exit();
    }

    $query = isset($_GET['query']) ? trim($_GET['query']) : '';
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $groups_per_page = 5;
    $offset = ($page - 1) * $groups_per_page;

    if (empty($query)) {
        echo json_encode(["status" => "error", "message" => "Search query cannot be empty."]);
        exit();
    }

    // Search within user's own groups
    $search_sql = "
        SELECT 
            g.group_id, 
            g.group_name, 
            g.group_handle, 
            g.group_picture, 
            g.current_members,
            gm.role
        FROM groups g
        JOIN group_members gm ON g.group_id = gm.group_id
        WHERE gm.user_id = ?
          AND g.is_deleted = 0
          AND (g.group_name LIKE CONCAT('%', ?, '%') OR g.group_handle LIKE CONCAT('%', ?, '%'))
        ORDER BY g.created_at DESC
        LIMIT ? OFFSET ?
    ";
    $search_stmt = $conn->prepare($search_sql);
    $search_stmt->bind_param("issii", $user_id, $query, $query, $groups_per_page, $offset);
    $search_stmt->execute();
    $search_result = $search_stmt->get_result();

    // Get total count for pagination
    $count_sql = "
        SELECT COUNT(*) AS total
        FROM groups g
        JOIN group_members gm ON g.group_id = gm.group_id
        WHERE gm.user_id = ?
          AND g.is_deleted = 0
          AND (g.group_name LIKE CONCAT('%', ?, '%') OR g.group_handle LIKE CONCAT('%', ?, '%'))
    ";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("iss", $user_id, $query, $query);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_groups = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_groups / $groups_per_page);

    if ($search_result->num_rows > 0) {
        $groups = [];
        while ($row = $search_result->fetch_assoc()) {
            $groups[] = [
                "groupId" => htmlspecialchars($row['group_id'], ENT_QUOTES, 'UTF-8'),
                "groupName" => htmlspecialchars($row['group_name'], ENT_QUOTES, 'UTF-8'),
                "groupHandle" => htmlspecialchars($row['group_handle'], ENT_QUOTES, 'UTF-8'),
                "groupPicture" => !empty($row['group_picture']) ? htmlspecialchars($row['group_picture'], ENT_QUOTES, 'UTF-8') : $dummyGPImage,
                "currentMembers" => htmlspecialchars($row['current_members'], ENT_QUOTES, 'UTF-8'),
                "role" => htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8'),
            ];
        }
        echo json_encode([
            "status" => "success",
            "groups" => $groups,
            "currentPage" => $page,
            "totalPages" => $total_pages
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "No groups found."]);
    }

    $search_stmt->close();
    $count_stmt->close();
    $conn->close();

} else {
    echo json_encode(["status" => "error", "message" => "Invalid search type."]);
    exit();
}
?>

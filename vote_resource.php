<?php
session_start();
require_once 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resource_id = intval($_POST['resource_id']);
    $user_id = $_SESSION['user_id'];

    try {
        // Check if the user has already voted for this resource
        $vote_check_stmt = $conn->prepare("
            SELECT 1
            FROM resource_votes
            WHERE resource_id = ? AND user_id = ?
        ");
        $vote_check_stmt->bind_param("ii", $resource_id, $user_id);
        $vote_check_stmt->execute();
        $vote_check_stmt->store_result();

        if ($vote_check_stmt->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "You have already voted for this resource."]);
            exit();
        }
        $vote_check_stmt->close();

        // Insert the vote
        $vote_insert_stmt = $conn->prepare("
            INSERT INTO resource_votes (resource_id, user_id) 
            VALUES (?, ?)
        ");
        $vote_insert_stmt->bind_param("ii", $resource_id, $user_id);
        if (!$vote_insert_stmt->execute()) {
            throw new Exception("Error casting vote: " . $vote_insert_stmt->error);
        }
        $vote_insert_stmt->close();

        // Fetch updated vote count
        $vote_count_stmt = $conn->prepare("
            SELECT COUNT(*) AS vote_count
            FROM resource_votes
            WHERE resource_id = ?
        ");
        $vote_count_stmt->bind_param("i", $resource_id);
        $vote_count_stmt->execute();
        $vote_count_stmt->bind_result($vote_count);
        $vote_count_stmt->fetch();
        $vote_count_stmt->close();

        // Return success response with updated vote count
        echo json_encode([
            "status" => "success",
            "message" => "Vote cast successfully!",
            "vote_count" => $vote_count
        ]);
        exit();
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit();
    }
}

echo json_encode(["status" => "error", "message" => "Invalid request."]);
exit();
?>

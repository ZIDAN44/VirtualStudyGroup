<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['group_id'])) {
    $group_id = intval($_POST['group_id']);
    $user_id = $_SESSION['user_id'];

    try {
        // Check if the user is banned from the group
        $ban_check_stmt = $conn->prepare("SELECT 1 FROM banned_users WHERE user_id = ? AND group_id = ?");
        $ban_check_stmt->bind_param("ii", $user_id, $group_id);
        $ban_check_stmt->execute();
        $ban_check_stmt->store_result();
        if ($ban_check_stmt->num_rows > 0) {
            throw new Exception("You are banned from joining this group.");
        }
        $ban_check_stmt->close();

        // Check if the user is already a member of the group
        $member_check_stmt = $conn->prepare("SELECT 1 FROM group_members WHERE user_id = ? AND group_id = ?");
        $member_check_stmt->bind_param("ii", $user_id, $group_id);
        $member_check_stmt->execute();
        $member_check_stmt->store_result();
        if ($member_check_stmt->num_rows > 0) {
            throw new Exception("You are already a member of this group.");
        }
        $member_check_stmt->close();

        // Check for existing pending join requests
        $request_check_stmt = $conn->prepare("SELECT 1 FROM join_requests WHERE user_id = ? AND group_id = ? AND status = 'pending'");
        $request_check_stmt->bind_param("ii", $user_id, $group_id);
        $request_check_stmt->execute();
        $request_check_stmt->store_result();
        if ($request_check_stmt->num_rows > 0) {
            throw new Exception("You have already sent a join request for this group. Please wait for approval.");
        }
        $request_check_stmt->close();

        // Fetch group details
        $rule_stmt = $conn->prepare("SELECT join_rule, max_members, current_members, req_point FROM groups WHERE group_id = ?");
        $rule_stmt->bind_param("i", $group_id);
        $rule_stmt->execute();
        $rule_stmt->bind_result($join_rule, $max_members, $current_members, $req_point);
        if (!$rule_stmt->fetch()) {
            throw new Exception("Group not found.");
        }
        $rule_stmt->close();

        // Fetch user's current points
        $points_stmt = $conn->prepare("SELECT points FROM users WHERE user_id = ?");
        $points_stmt->bind_param("i", $user_id);
        $points_stmt->execute();
        $points_stmt->bind_result($user_points);
        if (!$points_stmt->fetch()) {
            throw new Exception("User not found.");
        }
        $points_stmt->close();

        // Check if user has enough points
        if ($user_points < $req_point) {
            throw new Exception("You do not have enough points to join this group. Required: $req_point points.");
        }

        if ($join_rule === 'manual') {
            // Add a join request
            $request_stmt = $conn->prepare("INSERT INTO join_requests (user_id, group_id) VALUES (?, ?)");
            $request_stmt->bind_param("ii", $user_id, $group_id);
            if (!$request_stmt->execute()) {
                throw new Exception("Error sending join request. Please try again.");
            }
            $_SESSION['success_message'] = "Your join request has been sent. Please wait for Admin approval.";
            $request_stmt->close();
        } elseif ($join_rule === 'auto') {
            // Start a transaction
            $conn->begin_transaction();

            try {
                if (($max_members === null || $max_members === 0) || $current_members < $max_members) {
                    // Add the user as a Member
                    $add_member_stmt = $conn->prepare("INSERT INTO group_members (user_id, group_id, role) VALUES (?, ?, 'Member')");
                    $add_member_stmt->bind_param("ii", $user_id, $group_id);
                    if (!$add_member_stmt->execute()) {
                        throw new Exception("Error adding member to the group.");
                    }

                    // Increment current members count
                    $update_members_stmt = $conn->prepare("UPDATE groups SET current_members = current_members + 1 WHERE group_id = ?");
                    $update_members_stmt->bind_param("i", $group_id);
                    if (!$update_members_stmt->execute()) {
                        throw new Exception("Error updating member count.");
                    }

                    // Commit the transaction
                    $conn->commit();

                    $_SESSION['success_message'] = "You have successfully joined the group!";
                    $update_members_stmt->close();
                    $add_member_stmt->close();
                } else {
                    throw new Exception("This group is full and cannot accept new members.");
                }
            } catch (Exception $e) {
                // Rollback the transaction if any query fails
                $conn->rollback();
                throw $e;
            }
        } else {
            throw new Exception("Invalid group join rule.");
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    } finally {
        $conn->close();
    }
} else {
    $_SESSION['error_message'] = "Invalid group selection.";
}

header("Location: join_group.php");
exit();
?>

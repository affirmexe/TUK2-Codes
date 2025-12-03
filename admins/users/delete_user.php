<?php
require_once '../../connection.php';

// Check if connection is successful
if (!$conn) {
    error_log("Database connection failed in delete_user.php");
    header("Location: ../admin_dashboard.php?error=Database+connection+failed");
    exit();
}

$user_id = $_GET['id'] ?? null;

// Validate user_id
if (!$user_id || !is_numeric($user_id) || $user_id <= 0) {
    header("Location: ../admin_dashboard.php?error=Invalid+user+ID");
    exit();
}

$user_id = (int) $user_id;

// Check if user exists before deletion
$check_stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows == 0) {
    $check_stmt->close();
    header("Location: ../admin_dashboard.php?error=User+not+found");
    exit();
}
$check_stmt->close();

// Disable foreign key checks to handle constraints
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Delete related records first using prepared statements
$delete_reviews = $conn->prepare("DELETE FROM reviews WHERE user_id = ?");
$delete_reviews->bind_param("i", $user_id);
$delete_reviews->execute();
$delete_reviews->close();

$delete_transactions = $conn->prepare("DELETE FROM transactions WHERE user_id = ?");
$delete_transactions->bind_param("i", $user_id);
$delete_transactions->execute();
$delete_transactions->close();

// Delete user
$stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
if (!$stmt) {
    error_log("Prepare statement failed: " . $conn->error);
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    header("Location: ../admin_dashboard.php?error=Error+preparing+delete+statement");
    exit();
}

$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $stmt->close();
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        header("Location: ../admin_dashboard.php?msg=User+deleted+successfully");
        exit();
    } else {
        $stmt->close();
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        header("Location: ../admin_dashboard.php?error=User+could+not+be+deleted");
        exit();
    }
} else {
    error_log("Delete execution failed: " . $stmt->error);
    $stmt->close();
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    header("Location: ../admin_dashboard.php?error=Error+deleting+user");
    exit();
}
?>

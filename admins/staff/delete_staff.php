<?php
session_start();
require_once '../../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$staff_id = $_GET['id'] ?? '';
if (empty($staff_id)) {
    header("Location: ../admin_dashboard.php");
    exit;
}

$confirm = $_GET['confirm'] ?? '';

if ($confirm == '1') {
    // Delete immediately
    error_log("GET request with confirm for delete_staff.php");
    error_log("Staff ID from GET: " . $staff_id);
    if (!is_numeric($staff_id)) {
        error_log("Invalid staff ID: " . $staff_id);
        header("Location: ../admin_dashboard.php?error=Invalid+staff+ID");
        exit;
    }
    $staff_id = (int) $staff_id;
    error_log("Validated staff ID: " . $staff_id);

    // Disable foreign key checks to handle constraints
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    // Delete related records first using prepared statements
    $delete_attendance = $conn->prepare("DELETE FROM attendance WHERE staff_id = ?");
    $delete_attendance->bind_param("i", $staff_id);
    $delete_attendance->execute();
    $delete_attendance->close();

    $delete_transactions = $conn->prepare("DELETE FROM transactions WHERE staff_id = ?");
    $delete_transactions->bind_param("i", $staff_id);
    $delete_transactions->execute();
    $delete_transactions->close();

    $stmt = $conn->prepare("DELETE FROM staffs WHERE staff_id = ?");
    $stmt->bind_param("i", $staff_id);
    if ($stmt->execute()) {
        $stmt->close();
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        error_log("Staff deleted successfully: " . $staff_id);
        header("Location: ../admin_dashboard.php?message=Staff deleted successfully");
        exit;
    } else {
        error_log("Failed to delete staff: " . $conn->error);
        $stmt->close();
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        header("Location: ../admin_dashboard.php?error=Failed+to+delete+staff");
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("POST request received for delete_staff.php");
    $staff_id = $_POST['id'] ?? '';
    error_log("Staff ID from POST: " . $staff_id);
    if (empty($staff_id) || !is_numeric($staff_id)) {
        $error = "Invalid staff ID.";
        error_log("Invalid staff ID: " . $staff_id);
    } else {
        $staff_id = (int) $staff_id;
        error_log("Validated staff ID: " . $staff_id);

        // Disable foreign key checks to handle constraints
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");

        // Delete related records first using prepared statements
        $delete_attendance = $conn->prepare("DELETE FROM attendance WHERE staff_id = ?");
        $delete_attendance->bind_param("i", $staff_id);
        $delete_attendance->execute();
        $delete_attendance->close();

        $delete_transactions = $conn->prepare("DELETE FROM transactions WHERE staff_id = ?");
        $delete_transactions->bind_param("i", $staff_id);
        $delete_transactions->execute();
        $delete_transactions->close();

        $stmt = $conn->prepare("DELETE FROM staffs WHERE staff_id = ?");
        $stmt->bind_param("i", $staff_id);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            error_log("Staff deleted successfully: " . $staff_id);
            header("Location: ../admin_dashboard.php?message=Staff deleted successfully");
            exit;
        } else {
            $error = "Failed to delete staff.";
            error_log("Failed to delete staff: " . $conn->error);
            $stmt->close();
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM staffs WHERE staff_id = ?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$staff) {
    header("Location: ../admin_dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Staff - TUK²</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Delete Staff</h1>
        <a href="../admin_dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <p>Are you sure you want to delete the staff member: <strong><?php echo htmlspecialchars($staff['name']); ?></strong>?</p>
        <form method="post" action="delete_staff.php">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($staff_id); ?>">
            <button type="submit" class="btn btn-danger">Delete Staff</button>
            <a href="../admin_dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>

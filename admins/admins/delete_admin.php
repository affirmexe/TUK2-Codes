<?php
session_start();
require_once '../../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$admin_id = $_GET['id'] ?? '';
if (empty($admin_id)) {
    header("Location: ../admin_dashboard.php");
    exit;
}

$confirm = $_GET['confirm'] ?? '';

if ($confirm == '1') {
    // Delete immediately
    error_log("GET request with confirm for delete_admin.php");
    error_log("Admin ID from GET: " . $admin_id);
    if (!is_numeric($admin_id)) {
        error_log("Invalid admin ID: " . $admin_id);
        header("Location: ../admin_dashboard.php?error=Invalid+admin+ID");
        exit;
    }
    $admin_id = (int) $admin_id;
    error_log("Validated admin ID: " . $admin_id);

    // Disable foreign key checks (though not necessary for admins, for consistency)
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    $stmt = $conn->prepare("DELETE FROM admins WHERE admin_id = ?");
    $stmt->bind_param("i", $admin_id);
    if ($stmt->execute()) {
        $stmt->close();
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        error_log("Admin deleted successfully: " . $admin_id);
        header("Location: ../admin_dashboard.php?message=Admin deleted successfully");
        exit;
    } else {
        error_log("Failed to delete admin: " . $conn->error);
        $stmt->close();
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        header("Location: ../admin_dashboard.php?error=Failed+to+delete+admin");
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("POST request received for delete_admin.php");
    $admin_id = $_POST['id'] ?? '';
    error_log("Admin ID from POST: " . $admin_id);
    if (empty($admin_id) || !is_numeric($admin_id)) {
        $error = "Invalid admin ID.";
        error_log("Invalid admin ID: " . $admin_id);
    } else {
        $admin_id = (int) $admin_id;
        error_log("Validated admin ID: " . $admin_id);

        // Disable foreign key checks (though not necessary for admins, for consistency)
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");

        $stmt = $conn->prepare("DELETE FROM admins WHERE admin_id = ?");
        $stmt->bind_param("i", $admin_id);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            error_log("Admin deleted successfully: " . $admin_id);
            header("Location: ../admin_dashboard.php?message=Admin deleted successfully");
            exit;
        } else {
            $error = "Failed to delete admin.";
            error_log("Failed to delete admin: " . $conn->error);
            $stmt->close();
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin) {
    header("Location: ../admin_dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Admin - TUK²</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Delete Admin</h1>
        <a href="../admin_dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <p>Are you sure you want to delete the admin user: <strong><?php echo htmlspecialchars($admin['name']); ?></strong>?</p>
        <form method="post" action="delete_admin.php">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($admin_id); ?>">
            <button type="submit" class="btn btn-danger">Delete Admin</button>
            <a href="../admin_dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>

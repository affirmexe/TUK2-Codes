<?php
session_start();
require_once '../../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$errors = [];
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $staff_id = $_POST['staff_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    if (empty($name) || empty($email) || empty($start_time) || empty($end_time)) {
        $errors[] = "All fields are required.";
    } else {
        $stmt = $conn->prepare("UPDATE staffs SET name = ?, email = ?, start_time = ?, end_time = ? WHERE staff_id = ?");
        $stmt->bind_param("ssssi", $name, $email, $start_time, $end_time, $staff_id);
        if ($stmt->execute()) {
            $success = "Staff updated successfully!";
        } else {
            $errors[] = "Failed to update staff.";
        }
        $stmt->close();
    }
}

$staff_id = $_GET['id'] ?? '';
if (empty($staff_id)) {
    header("Location: ../admin_dashboard.php");
    exit;
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
    <title>Edit Staff - TUK²</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Edit Staff</h1>
        <a href="../admin_dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="edit_staff.php">
            <input type="hidden" name="staff_id" value="<?php echo htmlspecialchars($staff['staff_id']); ?>">
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($staff['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($staff['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="start_time" class="form-label">Start Time</label>
                <input type="time" class="form-control" id="start_time" name="start_time" value="<?php echo htmlspecialchars($staff['start_time']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="end_time" class="form-label">End Time</label>
                <input type="time" class="form-control" id="end_time" name="end_time" value="<?php echo htmlspecialchars($staff['end_time']); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Staff</button>
        </form>
    </div>
</body>
</html>

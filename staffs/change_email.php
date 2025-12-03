<?php
session_start();
require_once '../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit;
}

$errors = [];
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'] ?? '';
    $new_email = trim($_POST['new_email'] ?? '');
    $confirm_email = trim($_POST['confirm_email'] ?? '');

    if (empty($current_password)) {
        $errors[] = "Current password is required.";
    }
    if (empty($new_email)) {
        $errors[] = "New email is required.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if ($new_email !== $confirm_email) {
        $errors[] = "Emails do not match.";
    }

    if (empty($errors)) {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM staffs WHERE staff_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $staff = $result->fetch_assoc();
        $stmt->close();

        if ($staff && password_verify($current_password, $staff['password'])) {
            // Check if new email is unique
            $stmt = $conn->prepare("SELECT staff_id FROM staffs WHERE email = ? AND staff_id != ?");
            $stmt->bind_param("si", $new_email, $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing_staff = $result->fetch_assoc();
            $stmt->close();

            if ($existing_staff) {
                $errors[] = "Email is already in use.";
            } else {
                // Update email
                $stmt = $conn->prepare("UPDATE staffs SET email = ? WHERE staff_id = ?");
                $stmt->bind_param("si", $new_email, $_SESSION['user_id']);
                if ($stmt->execute()) {
                    $_SESSION['email'] = $new_email;
                    $success = "Email updated successfully.";
                } else {
                    $errors[] = "Failed to update email.";
                }
                $stmt->close();
            }
        } else {
            $errors[] = "Current password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Change Email - TUK²</title>
    <!-- Assets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous" />
    <link rel="stylesheet" href="../shared_assets/style.css" />
</head>

<body>
    <div class="container mt-5" style="max-width: 400px;">
        <h2 class="mb-4">Change Email</h2>

        <?php if (!empty($errors)) : ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error) : ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)) : ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="change_email.php" novalidate>
            <div class="form-floating mb-3 position-relative">
                <input type="password" class="form-control" id="floatingCurrentPassword" name="current_password" placeholder="Current Password" required />
                <label for="floatingCurrentPassword">Current Password</label>
                <button type="button" class="btn btn-sm btn-outline-secondary position-absolute top-50 end-0 translate-middle-y me-2" onclick="togglePassword('floatingCurrentPassword', this)">Show</button>
            </div>

            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="floatingNewEmail" name="new_email" placeholder="New Email" value="<?= htmlspecialchars($new_email ?? '') ?>" required />
                <label for="floatingNewEmail">New Email</label>
            </div>

            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="floatingConfirmEmail" name="confirm_email" placeholder="Confirm New Email" value="<?= htmlspecialchars($confirm_email ?? '') ?>" required />
                <label for="floatingConfirmEmail">Confirm New Email</label>
            </div>

            <div class="text-center mb-3">
                <a href="staff_dashboard.php" class="text-decoration-none fw-bold">Back to Dashboard</a>
            </div>
            <button type="submit" class="btn btn-primary w-100">Update Email</button>
        </form>
    </div>

    <!-- Assets -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/d554eb4963.js" crossorigin="anonymous"></script>
    <script src="../shared_assets/script.js"></script>

    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                btn.textContent = "Hide";
            } else {
                input.type = "password";
                btn.textContent = "Show";
            }
        }
    </script>
</body>

</html>

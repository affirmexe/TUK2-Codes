<?php
require_once '../../connection.php';

$errors = [];
$success = '';
$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    header("Location: ../admin_dashboard.php");
    exit();
}

// Fetch user data
$stmt = $conn->prepare("SELECT name, email, address, no_telp FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: ../admin_dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $no_telp = trim($_POST['no_telp'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    if (empty($address)) {
        $errors[] = "Address is required.";
    }
    if (empty($no_telp)) {
        $errors[] = "Telephone number is required.";
    }
    if (!empty($password) && $password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        // Check for duplicates excluding current user
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE (name = ? OR email = ? OR no_telp = ?) AND user_id != ?");
        $check_stmt->bind_param("ssss", $name, $email, $no_telp, $user_id);
        $check_stmt->execute();
        $check_stmt->bind_result($count);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($count > 0) {
            $errors[] = "Name, Email, or Telephone number already exists for another user.";
        } else {
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, address = ?, no_telp = ?, password = ? WHERE user_id = ?");
                $update_stmt->bind_param("sssssi", $name, $email, $address, $no_telp, $password_hash, $user_id);
            } else {
                $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, address = ?, no_telp = ? WHERE user_id = ?");
                $update_stmt->bind_param("ssssi", $name, $email, $address, $no_telp, $user_id);
            }

            if ($update_stmt->execute()) {
                $success = "User updated successfully.";
                // Refresh user data
                $user['name'] = $name;
                $user['email'] = $email;
                $user['address'] = $address;
                $user['no_telp'] = $no_telp;
            } else {
                $errors[] = "Error updating user: " . $update_stmt->error;
            }
            $update_stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit User - Admin Dashboard</title>
    <!-- Assets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../../shared_assets/style.css" />
</head>

<body>
    <div class="container mt-5" style="max-width: 600px;">
        <h2>Edit User</h2>
        <a href="../admin_dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>

        <?php if (!empty($errors)) : ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error) : ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success) : ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post" action="edit_user.php?id=<?= urlencode($user_id) ?>" novalidate>
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="floatingName" name="name" placeholder="Name" value="<?= htmlspecialchars($user['name']) ?>" required />
                <label for="floatingName">Name</label>
            </div>

            <div class="form-floating mb-3 position-relative">
                <input type="password" class="form-control" id="floatingPassword" name="password" placeholder="New Password (leave blank to keep current)" />
                <label for="floatingPassword">New Password (optional)</label>
            </div>

            <div class="form-floating mb-3 position-relative">
                <input type="password" class="form-control" id="floatingConfirmPassword" name="confirm_password" placeholder="Confirm New Password" />
                <label for="floatingConfirmPassword">Confirm New Password</label>
            </div>

            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="floatingEmail" name="email" placeholder="Email" value="<?= htmlspecialchars($user['email']) ?>" required />
                <label for="floatingEmail">Email</label>
            </div>

            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="floatingAddress" name="address" placeholder="Address" value="<?= htmlspecialchars($user['address']) ?>" required />
                <label for="floatingAddress">Address</label>
            </div>

            <div class="form-floating mb-3">
                <input type="tel" class="form-control" id="floatingNoTelp" name="no_telp" placeholder="Telephone Number" value="<?= htmlspecialchars($user['no_telp']) ?>" required />
                <label for="floatingNoTelp">Telephone Number</label>
            </div>

            <button type="submit" class="btn btn-primary w-100">Update User</button>
        </form>
    </div>

    <!-- Assets -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

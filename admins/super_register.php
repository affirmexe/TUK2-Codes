<?php
require_once '../connection.php';

$errors = [];
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $no_telp = trim($_POST['no_telp'] ?? '');
    $role = $_POST['role'] ?? '';

    // Basic validation
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    if (empty($role) || !in_array($role, ['user', 'staff', 'admin'])) {
        $errors[] = "Please select a valid role.";
    }

    // Make address and no_telp optional, no validation needed

    if (empty($errors)) {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Fix for no_telp: convert empty string to NULL to avoid SQL error
        $no_telp_db = !empty($no_telp) ? $no_telp : NULL;

        // Prepare insert based on role
        if ($role === 'user') {
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, address, no_telp) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $password_hash, $address, $no_telp_db);
        } elseif ($role === 'staff') {
            $role_db = 'staff';
            $stmt = $conn->prepare("INSERT INTO staffs (name, email, password, address, no_telp, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $email, $password_hash, $address, $no_telp_db, $role_db);
        } else { // admin
            $role_db = 'admin';
            $stmt = $conn->prepare("INSERT INTO admins (name, email, password, address, no_telp, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $email, $password_hash, $address, $no_telp_db, $role_db);
        }

        if ($stmt->execute()) {
            $success = "Account created successfully.";
            // Clear form values
            $name = $email = $address = $no_telp = $role = '';
        } else {
            $errors[] = "Error during account creation: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Super Register</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5" style="max-width: 600px;">
        <h2 class="mb-4">Super Register</h2>
        <p class="mb-4">This form is for creating admin, staff, or user accounts for internal access only.</p>

        <div class="mb-4">
            <a href="admin_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post" action="super_register.php" novalidate>
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="floatingName" name="name" placeholder="Name" value="<?= htmlspecialchars($name ?? '') ?>" required>
                <label for="floatingName">Name</label>
            </div>

            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="floatingEmail" name="email" placeholder="name@example.com" value="<?= htmlspecialchars($email ?? '') ?>" required>
                <label for="floatingEmail">Email address</label>
            </div>

            <div class="form-floating mb-3 position-relative">
                <input type="password" class="form-control" id="floatingPassword" name="password" placeholder="Password" required>
                <label for="floatingPassword">Password</label>
                <button type="button" class="btn btn-sm btn-outline-secondary position-absolute top-50 end-0 translate-middle-y me-2" onclick="togglePassword('floatingPassword', this)">Show</button>
            </div>

            <div class="form-floating mb-3 position-relative">
                <input type="password" class="form-control" id="floatingConfirmPassword" name="confirm_password" placeholder="Confirm Password" required>
                <label for="floatingConfirmPassword">Confirm Password</label>
                <button type="button" class="btn btn-sm btn-outline-secondary position-absolute top-50 end-0 translate-middle-y me-2" onclick="togglePassword('floatingConfirmPassword', this)">Show</button>
            </div>

            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="floatingAddress" name="address" placeholder="Address" value="<?= htmlspecialchars($address ?? '') ?>">
                <label for="floatingAddress">Address</label>
            </div>

            <div class="form-floating mb-3">
                <input type="tel" class="form-control" id="floatingNoTelp" name="no_telp" placeholder="Telephone Number" value="<?= htmlspecialchars($no_telp ?? '') ?>">
                <label for="floatingNoTelp">Telephone Number</label>
            </div>

            <div class="mb-3">
                <label for="roleSelect" class="form-label">Register as</label>
                <select class="form-select" id="roleSelect" name="role" required>
                    <option value="" disabled <?= empty($role) ? 'selected' : '' ?>>Select role</option>
                    <option value="user" <?= (isset($role) && $role === 'user') ? 'selected' : '' ?>>User</option>
                    <option value="staff" <?= (isset($role) && $role === 'staff') ? 'selected' : '' ?>>Staff</option>
                    <option value="admin" <?= (isset($role) && $role === 'admin') ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100">Create Account</button>
        </form>
    </div>

    <!-- Bootstrap JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
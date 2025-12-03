<?php
require_once 'connection.php';

$errors = [];
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

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
    $address = trim($_POST['address'] ?? '');
    if (empty($address)) {
        $errors[] = "Address is required.";
    }
    $no_telp = trim($_POST['no_telp'] ?? '');
    if (empty($no_telp)) {
        $errors[] = "Telephone number is required.";
    }

    if (empty($errors)) {
        // Check if name or email already exists
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE name = ? OR email = ? OR no_telp = ?");
        $check_stmt->bind_param("sss", $name, $email, $no_telp);
        $check_stmt->execute();
        $check_stmt->bind_result($count);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($count > 0) {
            $errors[] = "Name, Email, or Telephone number already exists. Please use a different one.";
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert into users table with all fields
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, address, no_telp) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $password_hash, $address, $no_telp);

            if ($stmt->execute()) {
                // Redirect to login.php after successful registration
                header("Location: login.php");
                exit();
            } else {
                $errors[] = "Error during account creation: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Register - TUK²</title>
    <!-- Assets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous" />
    <link rel="stylesheet" href="shared_assets/style.css" />
</head>

<body>
    <div class="container mt-5" style="max-width: 600px;">
        <h2 class="mb-4">Register</h2>
        <p>This form is for creating user accounts only.</p>
        <p>Already have an account? <a href="login.php" class="text-decoration-none fw-bold">Login here</a>.</p>

        <?php if (!empty($errors)) : ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error) : ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="register.php" novalidate>
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="floatingName" name="name" placeholder="Name" value="<?= htmlspecialchars($name ?? '') ?>" required />
                <label for="floatingName">Name</label>
            </div>

            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="floatingEmail" name="email" placeholder="name@example.com" value="<?= htmlspecialchars($email ?? '') ?>" required />
                <label for="floatingEmail">Email address</label>
            </div>

            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="floatingAddress" name="address" placeholder="Address" value="<?= htmlspecialchars($address ?? '') ?>" required />
                <label for="floatingAddress">Address</label>
            </div>

            <div class="form-floating mb-3">
                <input type="tel" class="form-control" id="floatingNoTelp" name="no_telp" placeholder="Telephone Number" value="<?= htmlspecialchars($no_telp ?? '') ?>" required />
                <label for="floatingNoTelp">Telephone Number</label>
            </div>

            <div class="form-floating mb-3 position-relative">
                <input type="password" class="form-control" id="floatingPassword" name="password" placeholder="Password" required />
                <label for="floatingPassword">Password</label>
                <button type="button" class="btn btn-sm btn-outline-secondary position-absolute top-50 end-0 translate-middle-y me-2" onclick="togglePassword('floatingPassword', this)">Show</button>
            </div>

            <div class="form-floating mb-3 position-relative">
                <input type="password" class="form-control" id="floatingConfirmPassword" name="confirm_password" placeholder="Confirm Password" required />
                <label for="floatingConfirmPassword">Confirm Password</label>
                <button type="button" class="btn btn-sm btn-outline-secondary position-absolute top-50 end-0 translate-middle-y me-2" onclick="togglePassword('floatingConfirmPassword', this)">Show</button>
            </div>

            <button type="submit" class="btn btn-primary w-100">Create User Account</button>
        </form>
    </div>

    <!-- Assets -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/d554eb4963.js" crossorigin="anonymous"></script>
    <script src="shared_assets/script.js"></script>

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

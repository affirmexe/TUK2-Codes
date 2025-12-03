<?php
session_start();
require_once 'connection.php';

$errors = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name_email = trim($_POST['name_email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name_email)) {
        $errors[] = "Name or Email is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        // Search in users table
        $stmt = $conn->prepare("SELECT user_id as id, name, email, password FROM users WHERE name = ? OR email = ?");
        $stmt->bind_param("ss", $name_email, $name_email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = 'user';
            header("Location: users/user_dashboard.php");
            exit;
        }

        // Search in staffs table
        $stmt = $conn->prepare("SELECT staff_id as id, name, email, password, role FROM staffs WHERE name = ? OR email = ?");
        $stmt->bind_param("ss", $name_email, $name_email);
        $stmt->execute();
        $result = $stmt->get_result();
        $staff = $result->fetch_assoc();
        $stmt->close();

        if ($staff && password_verify($password, $staff['password'])) {
            $_SESSION['user_id'] = $staff['id'];
            $_SESSION['name'] = $staff['name'];
            $_SESSION['email'] = $staff['email'];
            $_SESSION['role'] = $staff['role'];
            header("Location: staffs/staff_dashboard.php");
            exit;
        }

        // Search in admins table
        $stmt = $conn->prepare("SELECT admin_id as id, name, email, password, role FROM admins WHERE name = ? OR email = ?");
        $stmt->bind_param("ss", $name_email, $name_email);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['name'] = $admin['name'];
            $_SESSION['email'] = $admin['email'];
            $_SESSION['role'] = $admin['role'];
            header("Location: admins/admin_dashboard.php");
            exit;
        }

        $errors[] = "Invalid credentials. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - TUK²</title>
    <!-- Assets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous" />
    <link rel="stylesheet" href="shared_assets/style.css" />
</head>

<body>
    <div class="container mt-5" style="max-width: 400px;">
        <h2 class="mb-4">Login</h2>

        <?php if (!empty($errors)) : ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error) : ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php" novalidate>
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="floatingNameEmail" name="name_email" placeholder="Name or Email" value="<?= htmlspecialchars($name_email ?? '') ?>" required />
                <label for="floatingNameEmail">Name or Email</label>
            </div>

            <div class="form-floating mb-3 position-relative">
                <input type="password" class="form-control" id="floatingPassword" name="password" placeholder="Password" required />
                <label for="floatingPassword">Password</label>
                <button type="button" class="btn btn-sm btn-outline-secondary position-absolute top-50 end-0 translate-middle-y me-2" onclick="togglePassword('floatingPassword', this)">Show</button>
            </div>

            <div class="text-center mb-3">
                <a href="register.php" class="text-decoration-none fw-bold">Sign Up</a> | <a href="forgot_password.php" class="text-decoration-none fw-bold">Forgot Password?</a>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
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

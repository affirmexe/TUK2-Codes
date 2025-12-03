<?php
session_start();
require_once '../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

$errors = [];
$success = '';

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password)) {
        $errors[] = "Current password is required.";
    }
    if (empty($new_password)) {
        $errors[] = "New password is required.";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "New password must be at least 6 characters.";
    }
    if ($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match.";
    }

    if (empty($errors)) {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($current_password, $user['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
            if ($stmt->execute()) {
                $success = "Password updated successfully.";
            } else {
                $errors[] = "Failed to update password.";
            }
            $stmt->close();
        } else {
            $errors[] = "Current password is incorrect.";
        }
    }
}

// Handle quick review submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_quick_review'])) {
    $transaction_id = $_POST['quick_transaction_id'] ?? '';
    $rating = $_POST['quick_rating'] ?? '';
    $comment = $_POST['quick_comment'] ?? '';

    if (empty($transaction_id) || empty($rating)) {
        $errors[] = "Transaction and rating are required.";
    } else {
        // Check if user has already reviewed this transaction
        $stmt = $conn->prepare("SELECT review_id FROM reviews WHERE user_id = ? AND transaction_id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $transaction_id);
        $stmt->execute();
        $existing_review = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing_review) {
            $errors[] = "You have already submitted a review for this transaction.";
        } else {
            // Insert new review
            $stmt = $conn->prepare("INSERT INTO reviews (user_id, transaction_id, rating, comment) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $_SESSION['user_id'], $transaction_id, $rating, $comment);
            if ($stmt->execute()) {
                $success = "Review submitted successfully!";
            } else {
                $errors[] = "Failed to submit review.";
            }
            $stmt->close();
        }
    }
}

// Fetch recent completed transactions for quick review
$quick_transactions_result = $conn->query("
    SELECT t.transaction_id, t.entry_date, t.clothings_detail, t.total_price, t.completion_date,
           s.name as staff_name
    FROM transactions t
    JOIN staffs s ON t.staff_id = s.staff_id
    WHERE t.user_id = " . $_SESSION['user_id'] . "
    AND t.status = 1
    AND t.completion_date IS NOT NULL
    AND t.transaction_id NOT IN (SELECT transaction_id FROM reviews WHERE user_id = " . $_SESSION['user_id'] . ")
    ORDER BY t.completion_date DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>User Dashboard - TUK²</title>
    <!-- Assets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous" />
    <link rel="stylesheet" href="../shared_assets/style.css" />
</head>

<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>Welcome to User Dashboard</h1>
                <p>This is the user dashboard page.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="place_order.php" class="btn btn-primary">
                    <i class="fas fa-cart-plus"></i> Place New Order
                </a>
                <a href="history.php" class="btn btn-secondary">
                    <i class="fas fa-history"></i> Order History
                </a>
                <a href="finished_orders.php" class="btn btn-success">
                    <i class="fas fa-check-circle"></i> Finished Orders
                </a>
                <a href="reviews.php" class="btn btn-info">
                    <i class="fas fa-star"></i> My Reviews
                </a>
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="settingsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog"></i> Settings
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="settingsDropdown">
                        <li><a class="dropdown-item" href="reset_password.php">Reset Password</a></li>
                        <li><a class="dropdown-item" href="change_email.php">Change Email</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

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

        <!-- Quick Reviews Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Reviews</h5>
            </div>
            <div class="card-body">
                <?php if ($quick_transactions_result && $quick_transactions_result->num_rows > 0): ?>
                    <form method="post" action="user_dashboard.php">
                        <div class="mb-3">
                            <label for="quick_transaction_id" class="form-label">Select Recent Transaction</label>
                            <select class="form-select" id="quick_transaction_id" name="quick_transaction_id" required>
                                <option value="">Choose a completed transaction...</option>
                                <?php while ($transaction = $quick_transactions_result->fetch_assoc()): ?>
                                    <option value="<?php echo $transaction['transaction_id']; ?>">
                                        Transaction #<?php echo $transaction['transaction_id']; ?> -
                                        <?php echo htmlspecialchars($transaction['clothings_detail']); ?> -
                                        Staff: <?php echo htmlspecialchars($transaction['staff_name']); ?> -
                                        Completed: <?php echo date('M d, Y', strtotime($transaction['completion_date'])); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="quick_rating" class="form-label">Rating</label>
                            <select class="form-select" id="quick_rating" name="quick_rating" required>
                                <option value="">Select rating...</option>
                                <option value="5">⭐⭐⭐⭐⭐ (5 - Excellent)</option>
                                <option value="4">⭐⭐⭐⭐ (4 - Very Good)</option>
                                <option value="3">⭐⭐⭐ (3 - Good)</option>
                                <option value="2">⭐⭐ (2 - Fair)</option>
                                <option value="1">⭐ (1 - Poor)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="quick_comment" class="form-label">Comment (Optional)</label>
                            <textarea class="form-control" id="quick_comment" name="quick_comment" rows="3" placeholder="Quick comment..."></textarea>
                        </div>
                        <button type="submit" name="submit_quick_review" class="btn btn-primary">Submit Quick Review</button>
                    </form>
                <?php else: ?>
                    <p class="text-muted">No recent completed transactions available for quick review. Check the full reviews page for more options.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Assets -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/d554eb4963.js" crossorigin="anonymous"></script>
    <script src="../shared_assets/script.js"></script>

</body>

</html>
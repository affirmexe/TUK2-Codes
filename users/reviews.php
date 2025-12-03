<?php
session_start();
require_once '../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

$errors = [];
$success = '';

// Handle form submission for new reviews
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    $transaction_id = $_POST['transaction_id'] ?? '';
    $rating = $_POST['rating'] ?? '';
    $comment = $_POST['comment'] ?? '';

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

// Fetch user's completed transactions that can be reviewed
$transactions_result = $conn->query("
    SELECT t.transaction_id, t.entry_date, t.clothings_detail, t.total_price, t.completion_date,
           s.name as staff_name
    FROM transactions t
    JOIN staffs s ON t.staff_id = s.staff_id
    WHERE t.user_id = " . $_SESSION['user_id'] . "
    AND t.status = 1
    AND t.completion_date IS NOT NULL
    AND t.transaction_id NOT IN (SELECT transaction_id FROM reviews WHERE user_id = " . $_SESSION['user_id'] . ")
    ORDER BY t.completion_date DESC
");

// Fetch user's existing reviews
$reviews_result = $conn->query("
    SELECT r.review_id, r.rating, r.comment, r.created_at,
           t.transaction_id, t.entry_date, t.clothings_detail, t.total_price,
           s.name as staff_name
    FROM reviews r
    JOIN transactions t ON r.transaction_id = t.transaction_id
    JOIN staffs s ON t.staff_id = s.staff_id
    WHERE r.user_id = " . $_SESSION['user_id'] . "
    ORDER BY r.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My Reviews - TUK²</title>
    <!-- Assets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous" />
    <link rel="stylesheet" href="../shared_assets/style.css" />
    <style>
        .star-rating {
            color: #ffc107;
        }
        .review-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>My Reviews</h1>
                <p>View and submit reviews for completed laundry services.</p>
            </div>
            <div>
                <a href="user_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
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

        <!-- Submit New Review Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Submit New Review</h5>
            </div>
            <div class="card-body">
                <?php if ($transactions_result && $transactions_result->num_rows > 0): ?>
                    <form method="post" action="reviews.php">
                        <div class="mb-3">
                            <label for="transaction_id" class="form-label">Select Transaction</label>
                            <select class="form-select" id="transaction_id" name="transaction_id" required>
                                <option value="">Choose a completed transaction...</option>
                                <?php while ($transaction = $transactions_result->fetch_assoc()): ?>
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
                            <label for="rating" class="form-label">Rating</label>
                            <select class="form-select" id="rating" name="rating" required>
                                <option value="">Select rating...</option>
                                <option value="5">⭐⭐⭐⭐⭐ (5 - Excellent)</option>
                                <option value="4">⭐⭐⭐⭐ (4 - Very Good)</option>
                                <option value="3">⭐⭐⭐ (3 - Good)</option>
                                <option value="2">⭐⭐ (2 - Fair)</option>
                                <option value="1">⭐ (1 - Poor)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="comment" class="form-label">Comment (Optional)</label>
                            <textarea class="form-control" id="comment" name="comment" rows="4" placeholder="Share your experience with our laundry service..."></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
                    </form>
                <?php else: ?>
                    <p class="text-muted">No completed transactions available for review. Complete a laundry service first to submit a review.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Existing Reviews Section -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">My Reviews</h5>
            </div>
            <div class="card-body">
                <?php if ($reviews_result && $reviews_result->num_rows > 0): ?>
                    <?php while ($review = $reviews_result->fetch_assoc()): ?>
                        <div class="review-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6>Transaction #<?php echo htmlspecialchars($review['transaction_id']); ?></h6>
                                    <p class="text-muted mb-2">
                                        <?php echo htmlspecialchars($review['clothings_detail']); ?> -
                                        Staff: <?php echo htmlspecialchars($review['staff_name']); ?> -
                                        <?php echo date('M d, Y', strtotime($review['entry_date'])); ?>
                                    </p>
                                    <div class="mb-2">
                                        <span class="star-rating">
                                            <?php
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo $i <= $review['rating'] ? '★' : '☆';
                                            }
                                            ?>
                                        </span>
                                        <small class="text-muted">(<?php echo htmlspecialchars($review['rating']); ?>/5)</small>
                                    </div>
                                    <?php if (!empty($review['comment'])): ?>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No comment provided.</p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">
                                        <?php echo date('M d, Y H:i', strtotime($review['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted text-center">You haven't submitted any reviews yet.</p>
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

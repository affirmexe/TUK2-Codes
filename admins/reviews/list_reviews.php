<?php
session_start();
require_once '../../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

// Fetch all reviews with user and transaction details
$reviews_result = $conn->query("
    SELECT r.review_id, r.rating, r.comment, r.created_at,
           t.transaction_id, t.entry_date, t.clothings_detail, t.total_price, t.completion_date,
           u.name as user_name, u.email as user_email,
           s.name as staff_name
    FROM reviews r
    JOIN transactions t ON r.transaction_id = t.transaction_id
    JOIN users u ON r.user_id = u.user_id
    JOIN staffs s ON t.staff_id = s.staff_id
    ORDER BY r.created_at DESC
");

// Calculate average rating
$avg_rating_result = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews");
$avg_data = $avg_rating_result->fetch_assoc();
$average_rating = round($avg_data['avg_rating'] ?? 0, 1);
$total_reviews = $avg_data['total_reviews'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>All Reviews - Admin Dashboard</title>
    <!-- Assets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous" />
    <link rel="stylesheet" href="../../shared_assets/style.css" />
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
        .rating-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>All Reviews</h1>
                <p>View and manage customer reviews for laundry services.</p>
            </div>
            <div>
                <a href="../admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>

        <!-- Rating Summary -->
        <div class="rating-summary mb-4">
            <h2>Overall Rating</h2>
            <div class="mb-2">
                <span class="star-rating" style="font-size: 2rem;">
                    <?php
                    $full_stars = floor($average_rating);
                    $has_half_star = $average_rating - $full_stars >= 0.5;

                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $full_stars) {
                            echo '★';
                        } elseif ($i == $full_stars + 1 && $has_half_star) {
                            echo '½';
                        } else {
                            echo '☆';
                        }
                    }
                    ?>
                </span>
            </div>
            <h3><?php echo $average_rating; ?>/5</h3>
            <p>Based on <?php echo $total_reviews; ?> reviews</p>
        </div>

        <!-- Reviews List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Customer Reviews</h5>
            </div>
            <div class="card-body">
                <?php if ($reviews_result && $reviews_result->num_rows > 0): ?>
                    <?php while ($review = $reviews_result->fetch_assoc()): ?>
                        <div class="review-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <h6 class="mb-0 me-2">Review #<?php echo htmlspecialchars($review['review_id']); ?></h6>
                                        <span class="badge bg-primary">
                                            Transaction #<?php echo htmlspecialchars($review['transaction_id']); ?>
                                        </span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Customer:</strong> <?php echo htmlspecialchars($review['user_name']); ?> (<?php echo htmlspecialchars($review['user_email']); ?>)<br>
                                        <strong>Staff:</strong> <?php echo htmlspecialchars($review['staff_name']); ?><br>
                                        <strong>Service:</strong> <?php echo htmlspecialchars($review['clothings_detail']); ?><br>
                                        <strong>Amount:</strong> Rp <?php echo number_format($review['total_price']); ?><br>
                                        <strong>Completed:</strong> <?php echo date('M d, Y', strtotime($review['completion_date'])); ?>
                                    </div>
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
                                        <div class="alert alert-light">
                                            <strong>Comment:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No comment provided.</p>
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
                    <p class="text-muted text-center">No reviews have been submitted yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Assets -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/d554eb4963.js" crossorigin="anonymous"></script>
    <script src="../../shared_assets/script.js"></script>
</body>

</html>

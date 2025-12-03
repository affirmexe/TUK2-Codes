<?php
session_start();
require_once '../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch finished transactions (status=1 and completion_date not null)
$finished_transactions_result = $conn->query("
    SELECT t.transaction_id, t.entry_date, t.clothings_detail, t.total_price, t.completion_date,
           s.name as staff_name,
           (SELECT COUNT(*) FROM reviews r WHERE r.transaction_id = t.transaction_id AND r.user_id = $user_id) as review_count
    FROM transactions t
    LEFT JOIN staffs s ON t.staff_id = s.staff_id
    WHERE t.user_id = $user_id
    AND t.status = 1
    AND t.completion_date IS NOT NULL
    ORDER BY t.completion_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Finished Orders - TUK²</title>
    <!-- Assets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous" />
    <link rel="stylesheet" href="../shared_assets/style.css" />
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>Finished Orders</h1>
                <p>Your completed laundry orders. Submit reviews for orders without reviews yet.</p>
            </div>
            <div>
                <a href="user_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>

        <?php if ($finished_transactions_result && $finished_transactions_result->num_rows > 0): ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Transaction #</th>
                        <th>Entry Date</th>
                        <th>Clothings Detail</th>
                        <th>Total Price</th>
                        <th>Completion Date</th>
                        <th>Staff</th>
                        <th>Review</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $finished_transactions_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['transaction_id']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['entry_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['clothings_detail']); ?></td>
                            <td>Rp <?php echo number_format($row['total_price'], 0, ',', '.'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['completion_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['staff_name'] ?? '-'); ?></td>
                            <td>
                                <?php if ($row['review_count'] == 0): ?>
                                    <a href="reviews.php?transaction_id=<?php echo $row['transaction_id']; ?>" class="btn btn-sm btn-primary">Give Review</a>
                                <?php else: ?>
                                    <span class="text-success">Reviewed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted">No finished orders found.</p>
        <?php endif; ?>
    </div>

    <!-- Assets -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>

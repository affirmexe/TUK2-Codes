<?php
session_start();
require_once '../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit;
}

$staff_id = $_SESSION['user_id'];

// Fetch all transactions assigned to the logged-in staff ordered by entry_date descending
$transactions_result = $conn->query("
    SELECT t.transaction_id, t.entry_date, t.clothings_detail, t.total_price, t.status, t.completion_date,
           u.name as user_name
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.user_id
    WHERE t.staff_id = $staff_id
    ORDER BY t.entry_date DESC
");

function statusText($status)
{
    return $status == 1 ? 'Finished' : 'In Process';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Staff Order History - TUK²</title>
    <!-- Assets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous" />
    <link rel="stylesheet" href="../shared_assets/style.css" />
</head>

<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>Order History</h1>
                <p>All your assigned laundry orders, including in process and finished.</p>
            </div>
            <div>
                <a href="staff_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                <a href="orders.php" class="btn btn-primary ms-2">Manage Orders</a>
            </div>
        </div>

        <?php if ($transactions_result && $transactions_result->num_rows > 0): ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Transaction #</th>
                        <th>Entry Date</th>
                        <th>Clothings Detail</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Completion Date</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $transactions_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['transaction_id']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['entry_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['clothings_detail']); ?></td>
                            <td>Rp <?php echo number_format($row['total_price'], 0, ',', '.'); ?></td>
                            <td><?php echo statusText($row['status']); ?></td>
                            <td>
                                <?php
                                echo $row['completion_date'] ? date('M d, Y', strtotime($row['completion_date'])) : '-';
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['user_name'] ?? '-'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted">No orders found.</p>
        <?php endif; ?>
    </div>

    <!-- Assets -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>

</html>
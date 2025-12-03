<?php
session_start();
require_once '../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit;
}

$staff_id = $_SESSION['user_id'];

$message = '';
$message_type = '';

// Handle take over order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['take_over_order_id'])) {
        $order_id = intval($_POST['take_over_order_id']);

        // Check if order is already assigned
        $stmt = $conn->prepare("SELECT staff_id FROM transactions WHERE transaction_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result && $result['staff_id'] === null) {
            // Assign the current staff to the order
            $stmt = $conn->prepare("UPDATE transactions SET staff_id = ? WHERE transaction_id = ?");
            $stmt->bind_param("ii", $staff_id, $order_id);
            if ($stmt->execute()) {
                $message = "Order #$order_id successfully taken over.";
                $message_type = 'success';
            } else {
                $message = "Failed to take over the order.";
                $message_type = 'danger';
            }
            $stmt->close();
        } else {
            $message = "Order is already taken by another staff.";
            $message_type = 'warning';
        }
    } elseif (isset($_POST['update_status_order_id']) && isset($_POST['new_status'])) {
        $order_id = intval($_POST['update_status_order_id']);
        $new_status = intval($_POST['new_status']);

        if ($new_status === 1) { // Completed
            $stmt = $conn->prepare("UPDATE transactions SET status = ?, completion_date = NOW() WHERE transaction_id = ?");
            $stmt->bind_param("ii", $new_status, $order_id);
        } else {
            $stmt = $conn->prepare("UPDATE transactions SET status = ?, completion_date = NULL WHERE transaction_id = ?");
            $stmt->bind_param("ii", $new_status, $order_id);
        }

        if ($stmt->execute()) {
            $message = "Status for Order #$order_id successfully updated.";
            $message_type = 'success';
        } else {
            $message = "Failed to update status for Order #$order_id.";
            $message_type = 'danger';
        }
        $stmt->close();
    }
}

// Fetch orders with user info
$query = "
    SELECT t.transaction_id, t.entry_date, t.status, t.clothings_amount, t.clothings_detail, t.total_price, t.staff_id, u.name AS user_name
    FROM transactions t
    JOIN users u ON t.user_id = u.user_id
    ORDER BY t.entry_date DESC
";
$result = $conn->query($query);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Staff Orders - TUK²</title>
    <!-- Assets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous" />
    <link rel="stylesheet" href="../shared_assets/style.css" />
</head>

<body>
    <div class="container mt-5">
        <h1>Orders Management</h1>
        <a href="staff_dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>" role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Order ID</th>
                            <th>User Name</th>
                            <th>Entry Date</th>
                            <th>Weight (kg)</th>
                            <th>Details</th>
                            <th>Total Price (Rp)</th>
                            <th>Status</th>
                            <th>Assigned Staff</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['transaction_id']); ?></td>
                                <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['entry_date']); ?></td>
                                <td><?php echo htmlspecialchars($order['clothings_amount']); ?></td>
                                <td><?php echo htmlspecialchars($order['clothings_detail']); ?></td>
                                <td><?php echo number_format($order['total_price'], 0, ',', '.'); ?></td>
                                <td>
                                    <form method="post" action="orders.php" class="d-flex align-items-center m-0">
                                        <select name="new_status" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                                            <option value="0" <?php echo $order['status'] == 0 ? 'selected' : ''; ?>>Pending</option>
                                            <option value="1" <?php echo $order['status'] == 1 ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                        <input type="hidden" name="update_status_order_id" value="<?php echo $order['transaction_id']; ?>" />
                                        <noscript><button type="submit" class="btn btn-sm btn-primary">Update</button></noscript>
                                    </form>
                                </td>
                                <td>
                                    <?php
                                    if ($order['staff_id']) {
                                        // Fetch staff name by staff_id
                                        $stmt = $conn->prepare("SELECT name FROM staffs WHERE staff_id = ?");
                                        $stmt->bind_param("i", $order['staff_id']);
                                        $stmt->execute();
                                        $staff_name = $stmt->get_result()->fetch_assoc()['name'] ?? 'Unknown';
                                        $stmt->close();
                                        echo htmlspecialchars($staff_name);
                                    } else {
                                        echo "<em>Unassigned</em>";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($order['staff_id'] === null): ?>
                                        <form method="post" action="orders.php" class="m-0">
                                            <input type="hidden" name="take_over_order_id" value="<?php echo $order['transaction_id']; ?>" />
                                            <button type="submit" class="btn btn-sm btn-primary">Take Over</button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled>Taken</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No orders found.</p>
        <?php endif; ?>
    </div>

    <!-- Assets -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="../shared_assets/script.js"></script>
</body>

</html>
<?php
session_start();
require_once '../../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Staff List - Admin Dashboard</title>
    <!-- Assets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous" />
    <link rel="stylesheet" href="../../shared_assets/style.css" />
</head>

<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>Staff List</h1>
                <p>View and manage staff members.</p>
            </div>
            <div>
                <a href="../admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>

        <?php
        require_once '../../connection.php';

        // Fetch all staff with their latest attendance status
        $staff_result = $conn->query("
            SELECT s.staff_id, s.name, s.email, s.start_time, s.end_time,
                   COALESCE(a.status, 'Not checked in') as current_status,
                   a.check_in_time, a.check_out_time
            FROM staffs s
            LEFT JOIN attendance a ON s.staff_id = a.staff_id
                AND a.attendance_date = CURDATE()
            ORDER BY s.staff_id DESC
        ");
        ?>

        <table class="table table-striped table-bordered mt-3">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($staff_result && $staff_result->num_rows > 0) : ?>
                    <?php while ($staff = $staff_result->fetch_assoc()) : ?>
                        <tr>
                            <td><?= htmlspecialchars($staff['staff_id']) ?></td>
                            <td><?= htmlspecialchars($staff['name']) ?></td>
                            <td><?= htmlspecialchars($staff['email']) ?></td>
                            <td><?= htmlspecialchars($staff['start_time'] ?? '') ?></td>
                            <td><?= htmlspecialchars($staff['end_time'] ?? '') ?></td>
                            <td>
                                <span class="badge
                                    <?php
                                    if ($staff['current_status'] === 'present') echo 'bg-success';
                                    elseif ($staff['current_status'] === 'absent') echo 'bg-danger';
                                    elseif ($staff['current_status'] === 'partial') echo 'bg-warning';
                                    else echo 'bg-secondary';
                                    ?>">
                                    <?= htmlspecialchars(ucfirst($staff['current_status'])) ?>
                                </span>
                            </td>
                            <td>
                                <a href="edit_staff.php?id=<?= urlencode($staff['staff_id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="delete_staff.php?id=<?= urlencode($staff['staff_id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this staff?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7" class="text-center">No staff members found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Assets -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/d554eb4963.js" crossorigin="anonymous"></script>
    <script src="../../shared_assets/script.js"></script>
</body>

</html>

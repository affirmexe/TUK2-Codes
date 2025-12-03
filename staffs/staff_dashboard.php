<?php
session_start();
require_once '../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit;
}

// Get staff information
$stmt = $conn->prepare("SELECT * FROM staffs WHERE staff_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle check-in/check-out
$errors = [];
$success = '';
$today = date('Y-m-d');
$is_checked_in = false;
$is_checked_out = false;
$can_check_out = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'check_in') {
        // Check if already checked in today
        $stmt = $conn->prepare("SELECT * FROM attendance WHERE staff_id = ? AND attendance_date = ?");
        $stmt->bind_param("is", $_SESSION['user_id'], $today);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            $errors[] = "You have already checked in today.";
        } else {
            $check_in_time = date('Y-m-d H:i:s');
            $is_late = false;

            // Check if late (after 10:00 AM)
            $ten_am = date('Y-m-d') . ' 10:00:00';
            $is_late = strtotime($check_in_time) > strtotime($ten_am);

            $stmt = $conn->prepare("INSERT INTO attendance (staff_id, attendance_date, check_in_time, is_late) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $_SESSION['user_id'], $today, $check_in_time, $is_late);
            if ($stmt->execute()) {
                $success = "Check-in successful!" . ($is_late ? " (Late)" : "");
                $is_checked_in = true;
            } else {
                $errors[] = "Failed to check in.";
            }
            $stmt->close();
        }
    } elseif ($action === 'check_out') {
        // Check if checked in today
        $stmt = $conn->prepare("SELECT * FROM attendance WHERE staff_id = ? AND attendance_date = ?");
        $stmt->bind_param("is", $_SESSION['user_id'], $today);
        $stmt->execute();
        $attendance = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$attendance) {
            $errors[] = "You must check in first.";
        } elseif ($attendance['check_out_time']) {
            $errors[] = "You have already checked out today.";
        } else {
            $check_out_time = date('Y-m-d H:i:s');
            $can_check_out = true;

            // Check if before 17:00 (early check-out)
            $five_pm = date('Y-m-d') . ' 17:00:00';
            $is_early = strtotime($check_out_time) < strtotime($five_pm);

            if ($can_check_out) {
                // Calculate total hours
                $start_time = strtotime($attendance['check_in_time']);
                $end_time = strtotime($check_out_time);
                $total_hours = round(($end_time - $start_time) / 3600, 2);

                // Check if checked out within 10 minutes (early)
                $ten_minutes = 10 * 60; // 10 minutes in seconds
                $time_diff = $end_time - $start_time;
                $status = 'present';
                if ($time_diff < $ten_minutes) {
                    $status = 'partial'; // Izin pergi
                    $success = "Check-out successful! (Izin Pergi) Total hours: " . $total_hours . " hours";
                } else {
                    $success = "Check-out successful! Total hours: " . $total_hours . " hours";
                }

                $stmt = $conn->prepare("UPDATE attendance SET check_out_time = ?, total_hours = ?, status = ? WHERE attendance_id = ?");
                $stmt->bind_param("sdsi", $check_out_time, $total_hours, $status, $attendance['attendance_id']);
                if ($stmt->execute()) {
                    $is_checked_out = true;
                } else {
                    $errors[] = "Failed to check out.";
                }
                $stmt->close();
            }
        }
    }
}

// Get today's attendance status
$stmt = $conn->prepare("SELECT * FROM attendance WHERE staff_id = ? AND attendance_date = ?");
$stmt->bind_param("is", $_SESSION['user_id'], $today);
$stmt->execute();
$today_attendance = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($today_attendance) {
    $is_checked_in = true;
    $is_checked_out = !empty($today_attendance['check_out_time']);
    $can_check_out = !$is_checked_out;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Staff Dashboard - TUK²</title>
    <!-- Assets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous" />
    <link rel="stylesheet" href="../shared_assets/style.css" />
</head>

<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>Welcome to Staff Dashboard</h1>
                <p>This is the staff dashboard page.</p>
                <a href="orders.php" class="btn btn-primary mt-2">View Orders</a>
                <a href="order_history.php" class="btn btn-info mt-2 ms-2">Order History</a>
            </div>
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

        <!-- Attendance Section -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Today's Attendance</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Current Time:</strong> <span id="currentTime"><?php echo date('H:i:s'); ?></span>
                        </div>
                        <div class="mb-3">
                            <strong>Scheduled Hours:</strong>
                            <?php
                            if ($staff['start_time'] && $staff['end_time']) {
                                echo $staff['start_time'] . ' - ' . $staff['end_time'];
                            } else {
                                echo 'Not set';
                            }
                            ?>
                        </div>
                        <div class="mb-3">
                            <strong>Status:</strong>
                            <?php if ($is_checked_out): ?>
                                <span class="badge bg-success">Checked Out</span>
                            <?php elseif ($is_checked_in): ?>
                                <span class="badge bg-warning">Checked In</span>
                                <?php if ($today_attendance['is_late']): ?>
                                    <span class="badge bg-danger ms-2">Telat</span>
                                <?php else: ?>
                                    <span class="badge bg-success ms-2">Tepat Waktu</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-secondary">Absen/Izin</span>
                            <?php endif; ?>
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

                        <form method="post" action="staff_dashboard.php">
                            <?php if (!$is_checked_in): ?>
                                <button type="submit" name="action" value="check_in" class="btn btn-success me-2">
                                    <i class="fas fa-sign-in-alt"></i> Check In
                                </button>
                            <?php endif; ?>

                            <?php if ($is_checked_in && !$is_checked_out && $can_check_out): ?>
                                <button type="submit" name="action" value="check_out" class="btn btn-danger">
                                    <i class="fas fa-sign-out-alt"></i> Check Out
                                </button>
                            <?php elseif ($is_checked_in && !$is_checked_out): ?>
                                <button type="button" class="btn btn-secondary" disabled>
                                    <i class="fas fa-clock"></i> Check Out Not Available Yet
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Today's Summary</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($today_attendance): ?>
                            <div class="mb-2">
                                <strong>Check In Time:</strong> <?php echo date('H:i', strtotime($today_attendance['check_in_time'])); ?>
                            </div>
                            <?php if ($today_attendance['check_out_time']): ?>
                                <div class="mb-2">
                                    <strong>Check Out Time:</strong> <?php echo date('H:i', strtotime($today_attendance['check_out_time'])); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Total Hours:</strong> <?php echo $today_attendance['total_hours']; ?> hours
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted">No attendance record for today yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assets -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/d554eb4963.js" crossorigin="anonymous"></script>
    <script src="../shared_assets/script.js"></script>

    <script>
        // Real-time clock update
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-GB', {
                hour12: false
            });
            document.getElementById('currentTime').textContent = timeString;
        }

        // Update time every second
        setInterval(updateTime, 1000);

        // Initial call to set the time immediately
        updateTime();

        // Function to check if check-out is available
        function checkCheckoutAvailability() {
            const now = new Date();
            const currentTime = now.getHours() * 60 + now.getMinutes();

            const fivePm = 17 * 60; // 17:00 in minutes

            if (currentTime >= fivePm && <?php echo $is_checked_in && !$is_checked_out ? 'true' : 'false'; ?>) {
                location.reload(); // Refresh to enable check-out button
            }
        }

        // Check every minute if check-out is available
        setInterval(checkCheckoutAvailability, 60000);
    </script>
</body>

</html>
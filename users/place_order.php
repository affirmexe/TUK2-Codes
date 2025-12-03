<?php
session_start();
require_once '../connection.php';

// Check if user is logged in by verifying user_id in session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$weight = 0;
$total_price = 0;
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['weight']) && is_numeric($_POST['weight']) && $_POST['weight'] > 0) {
        $weight = floatval($_POST['weight']);
        $delivery_method = isset($_POST['delivery_method']) ? $_POST['delivery_method'] : 'self';
        $total_price = $weight * 500;

        if ($delivery_method === 'pickup') {
            $total_price += 5000;
        }

        // Prepare and execute insert query to transactions table
        $clothings_detail = isset($_POST['clothings_detail']) ? trim($_POST['clothings_detail']) : '';
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, entry_date, status, clothings_amount, clothings_detail, total_price) VALUES (?, NOW(), 0, ?, ?, ?)");
        $stmt->bind_param("iisd", $user_id, $weight, $clothings_detail, $total_price);

        if ($stmt->execute()) {
            header("Location: user_dashboard.php");
            exit();
        } else {
            $message = "Error placing order: " . $stmt->error;
            $message_type = 'danger';
        }

        $stmt->close();
    } else {
        $message = "Please enter a valid weight greater than 0.";
        $message_type = 'warning';
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Place Order - TUK²</title>
    <!-- Assets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous" />
    <link rel="stylesheet" href="../shared_assets/style.css" />
</head>

<body>
    <div class="container mt-5">
        <h1 class="mb-4">Place Order</h1>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>


<form method="POST" action="place_order.php" class="needs-validation" novalidate>
    <div class="mb-3">
        <label for="weight" class="form-label">Enter weight of clothes (kg)</label>
        <input type="number" step="0.01" min="0.01" class="form-control" id="weight" name="weight" value="<?php echo htmlspecialchars($weight); ?>" required>
        <div class="invalid-feedback">
            Please enter a valid weight greater than 0.
        </div>
        <div class="form-text text-muted">
            Price is Rp 500 per kilo.
        </div>
    </div>

    <div class="mb-3">
        <label for="clothings_detail" class="form-label">Clothings Detail</label>
        <textarea class="form-control" id="clothings_detail" name="clothings_detail" rows="3" placeholder="Describe your clothings"><?php echo isset($_POST['clothings_detail']) ? htmlspecialchars($_POST['clothings_detail']) : ''; ?></textarea>
    </div>

    <div class="mb-3">
        <label class="form-label">Delivery Method</label>
        <div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="delivery_method" id="delivery_self" value="self" <?php echo (isset($_POST['delivery_method']) && $_POST['delivery_method'] === 'self') || !isset($_POST['delivery_method']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="delivery_self">Go to Place Myself (No additional cost)</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="delivery_method" id="delivery_pickup" value="pickup" <?php echo isset($_POST['delivery_method']) && $_POST['delivery_method'] === 'pickup' ? 'checked' : ''; ?>>
                <label class="form-check-label" for="delivery_pickup">Pickup (Add Rp 5,000)</label>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Place Order</button>
    <a href="user_dashboard.php" class="btn btn-secondary ms-2">Cancel</a>
</form>
    </div>

    <!-- Assets -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="../shared_assets/script.js"></script>
    <script>
        // Bootstrap custom form validation
        (() => {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')

            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>

</html>

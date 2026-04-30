<?php
require_once 'header.php';
require_once 'dp.php';

if (!isset($_SESSION['user_id'])) {
   header("Location: login.php");
   exit();
}

$orderId = $_GET['id'] ?? '';

// Get order
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = :order_id");
$stmt->execute([':order_id' => $orderId]);
$order = $stmt->fetch();

if (!$order || $order['client_id'] !== $_SESSION['user_id']) {
    $_SESSION['error'] = 'Order not found or access denied.';
    header("Location: my-orders.php");
    exit();
}

if ($order['status'] !== 'Pending') {
    $_SESSION['error'] = 'Only pending orders can be cancelled.';
    header("Location: order-details.php?id=" . $orderId);
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = $_POST['cancellation_reason'] ?? '';
    $confirmed = isset($_POST['confirm_cancellation']);
    
    if (!$confirmed) {
        $errors['confirm'] = 'You must confirm the cancellation.';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                UPDATE orders SET 
                    status = 'Cancelled'
                WHERE order_id = :order_id
            ");
            $stmt->bindvalue(':order_id', $orderId);
            $stmt->execute();
            
            $_SESSION['success'] = 'Order cancelled successfully. Refund will be processed.';
            header("Location: my-orders.php");
        } catch (xception $e) {
            $conn->rollBack();
            $errors['general'] = 'Cancellation failed. Please try again.';
        }
    }
}
?>

<section class="container">
        <link rel="stylesheet" href="css/style.css">
    <section class="form-container" style="max-width: 700px;">
        <h1 class="heading-primary">Cancel Order</h1>
        
        <section class="message-warning">
            <strong>Warning:</strong> This action cannot be undone. The order will be cancelled and a refund will be processed.
        </section>
        
        <section class="card" style="margin: 20px 0;">
            <h3>Order #<?php echo $order['order_id']; ?></h3>
            <p style="margin: 10px 0;"><strong>Total Amount:</strong> $<?php echo number_format($order['price'], 2); ?></p>
            <p style="margin: 10px 0;"><strong>Order Date:</strong> <?php echo $order['order_date']; ?></p>
        </section>
        
        <?php if (isset($errors['general'])): ?>
            <section class="message-error"><?php echo $errors['general']; ?></section>
        <?php endif; ?>
        
        <form method="POST">
            <section class="form-group">
                <label class="form-label">Cancellation Reason (Optional)</label>
                <textarea name="cancellation_reason" class="form-input" rows="4" 
                          placeholder="Please provide a reason for cancellation..."><?php echo isset($_POST['cancellation_reason']) ? htmlspecialchars($_POST['cancellation_reason']) : ''; ?></textarea>
            </section>
            
            <section class="form-group">
                <label>
                    <input type="checkbox" name="confirm_cancellation" required>
                    I understand this order will be cancelled and cannot be reversed
                </label>
                <?php if (isset($errors['confirm'])): ?>
                    <section class="form-error"><?php echo $errors['confirm']; ?></section>
                <?php endif; ?>
            </section>
            
            <section class="form-actions">
                <button type="submit" class="btn btn-danger" style="flex: 1;">Cancel Order</button>
                <a href="order-details.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">Go Back</a>
            </section>
        </form>
    </section>
</section>

<?php require_once 'footer.php'; ?>
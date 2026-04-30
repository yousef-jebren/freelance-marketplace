<?php
require_once 'header.php';

$userSql = "select * from users where user_id = :user_id";
$stmt = $conn->prepare($userSql);
$stmt->bindvalue(':user_id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch();

if ($user['role'] !== 'Client') {
    $_SESSION['error'] = 'Access denied.';
      header("Location:login.php");
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

if ($order['status'] !== 'Delivered') {
    $_SESSION['error'] = 'Only delivered orders can be marked as completed.';
    header("Location: order-details.php?id=" . $orderId);
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirmed = isset($_POST['confirm_completion']);
    
    if (!$confirmed) {
        $errors['confirm'] = 'You must confirm the completion.';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("UPDATE orders SET status = 'Completed', completion_date = NOW() WHERE order_id = :order_id");
            $stmt->execute([':order_id' => $orderId]);
            
            $_SESSION['success'] = 'Order marked as completed!';
            header("Location: order-details.php?id=" . $orderId);
        } catch (Exception $e) {
            $conn->rollBack();
            $errors['general'] = 'Completion failed. Please try again.';
        }
    }
}
?>

<section class="container">
        <link rel="stylesheet" href="css/style.css">
    <section class="form-container" style="max-width: 700px;">
        <h1 class="heading-primary">Complete Order</h1>
        
        <section class="message-info">
            Are you sure you want to mark this order as completed? This action cannot be undone.
        </section>
        
        <section class="card" style="margin: 20px 0;">
            <h3>Order #<?php echo $order['order_id']; ?></h3>
            <p style="margin: 10px 0;"><strong>Total Amount:</strong> $<?php echo number_format($order['price'], 2); ?></p>
            <p style="margin: 10px 0;"><strong>Delivery Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        </section>
        
        <?php if (isset($errors['general'])): ?>
            <section class="message-error"><?php echo $errors['general']; ?></section>
        <?php endif; ?>
        
        <form method="POST">
            <section class="form-group">
                <label>
                    <input type="checkbox" name="confirm_completion" required>
                    I confirm that I have received the delivery and am satisfied with the work
                </label>
                <?php if (isset($errors['confirm'])): ?>
                    <section class="form-error"><?php echo $errors['confirm']; ?></section>
                <?php endif; ?>
            </section>
            
            <section class="form-actions">
                <button type="submit" class="btn btn-success" style="flex: 1;">Mark as Completed</button>
                <a href="order-details.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">Go Back</a>
            </section>
        </form>
    </section>
</section>

<?php require_once 'footer.php'; ?>
<?php
require_once 'header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get order IDs from session
$orderIds = $_SESSION['order_ids'] ?? [];

if (empty($orderIds)) {
    header("Location: browse-services.php");
}

// Get order details
$placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
$stmt = $conn->prepare("
    SELECT o.*, s.title as service_title, u.first_name as f_name, u.last_name as l_name , u.user_id as freelancer_id
    FROM orders o
    JOIN services s ON o.service_id = s.service_id
    JOIN users u ON o.freelancer_id = u.user_id
    WHERE o.order_id IN ($placeholders)
");
$stmt->execute($orderIds);
$orders = $stmt->fetchAll();


$totalAmount = array_sum(array_column($orders, 'price'));

// Clear order IDs from session
unset($_SESSION['order_ids']);
?>



<section class="container">
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="css/success-page.css">
    <section class="success-banner">
        <section class="success-icon">✓</section>
        <h1 style="font-size: 32px; margin-bottom: 10px;">Orders Placed Successfully!</h1>
        <p style="font-size: 18px;">You have placed <?php echo count($orders); ?> order<?php echo count($orders) != 1 ? 's' : ''; ?></p>
        <p style="font-size: 24px; font-weight: bold; margin-top: 15px;">
            Total: $<?php echo number_format($totalAmount, 2); ?>
        </p>
    </section>
    
    <section class="order-cards">
        <?php foreach ($orders as $order): ?>
            <?php $fullName = $order['f_name']. " ". $order['l_name']; ?>
            <section class="order-card">
                <section style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                    <section>
                        <p style="color: #007BFF; font-weight: bold; font-size: 18px;">
                            Order #<?php echo $order['order_id']; ?>
                        </p>
                        <h3 style="font-size: 16px; font-weight: bold; margin: 10px 0;">
                            <?php echo htmlspecialchars($order['service_title']); ?>
                        </h3>
                        <p style="font-size: 14px; color: #6C757D;">
                            Freelancer: 
                            <a href="profile.php?id=<?php echo $order['freelancer_id']; ?>">
                                <?php echo htmlspecialchars($fullName); ?>
                            </a>
                        </p>
                    </section>
                    <span class="badge status-pending">Pending</span>
                </section>
                
                <section style="display: flex; justify-content: space-between; margin-top: 15px; padding-top: 15px; border-top: 1px solid #DEE2E6;">
                    <section>
                        <p style="font-size: 18px; font-weight: bold;">
                            $<?php echo number_format($order['price'] , 2); ?>
                        </p>
                        <p style="font-size: 14px; color: #6C757D;">
                            Expected delivery: <?php echo $order['expected_delivery']; ?>
                        </p>
                    </section>
                    <a href="order-details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-primary" >
                        View Order Details
                    </a>
                </section>
            </section>
        <?php endforeach; ?>
    </section>
    
    <section class="action-buttons">
        <a href="my-orders.php" class="btn btn-primary" style="font-size: 16px; padding: 12px 30px;">
            View All Orders
        </a>
        <a href="browse-services.php" class="btn btn-secondary" style="font-size: 16px; padding: 12px 30px;">
            Browse More Services
        </a>
    </section>
</section>

<?php require_once 'footer.php'; ?>
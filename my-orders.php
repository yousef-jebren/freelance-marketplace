<?php
require_once 'header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

$userId = $_SESSION['user_id'];
$statusFilter = $_GET['status'] ?? 'all';

    $userSql = "Select * from users where user_id = :userID";
    $stmt = $conn->prepare($userSql);
    $stmt->bindvalue('userID', $userId);
    $stmt->execute();
    $user = $stmt->fetch();

// Build query based on role
if ($user['role'] === 'Client') {
    $sql = "SELECT o.*, s.title as service_title, s.image_1, 
            u.first_name as f_name, u.last_name as l_name ,u.user_id as freelancer_id
            FROM orders o
            JOIN services s ON o.service_id = s.service_id
            JOIN users u ON o.freelancer_id = u.user_id
            WHERE o.client_id = :user_id";
} else {
    $sql = "SELECT o.*, s.title as service_title, s.image_1,
            u.first_name as f_name, u.last_name as l_name ,u.user_id as client_id
            FROM orders o
            JOIN services s ON o.service_id = s.service_id
            JOIN users u ON o.client_id = u.user_id
            WHERE o.freelancer_id = :user_id";
}

// Add status filter
if ($statusFilter !== 'all') {
    $sql .= " AND o.status = :status";
}

$sql .= " ORDER BY o.order_date DESC";

$stmt = $conn->prepare($sql);
$params = [':user_id' => $userId];
if ($statusFilter !== 'all') {
    $params[':status'] = $statusFilter;
}
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<section class="container">
        <link rel="stylesheet" href="css/style.css">
    <h1 class="heading-primary">My Orders</h1>
    
    <!-- Status Filter -->
    <section class="card" style="margin-bottom: 30px;">
        <form method="GET" style="display: flex; gap: 15px; align-items: center;">
            <label class="form-label" style="margin: 0;">Filter by Status:</label>
            <select name="status" class="form-select" style="width: 250px;" onchange="this.form.submit()">
                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Orders</option>
                <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="In Progress" <?php echo $statusFilter === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                <option value="Delivered" <?php echo $statusFilter === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="Completed" <?php echo $statusFilter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="Revision Requested" <?php echo $statusFilter === 'Revision Requested' ? 'selected' : ''; ?>>Revision Requested</option>
                <option value="Cancelled" <?php echo $statusFilter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </form>
    </section>
    
    <?php if (empty($orders)): ?>
        <section class="message-info" style="text-align: center; padding: 40px;">
            <p style="font-size: 18px;">No orders found.</p>
            <?php if ($user['role'] === 'Client'): ?>
                <a href="browse-services.php" class="btn btn-primary" style="margin-top: 20px;">Browse Services</a>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <section class="card">

            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Service</th>
                        <th><?php echo $user['role'] ==='Client' ? 'Freelancer' : 'Client'; ?></th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Order Date</th>
                        <th>Expected Delivery</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                                    <?php $full_name = $order['f_name']. " ". $order['l_name']; ?>
                        <tr>
                            <td>
                                <a href="order-details.php?id=<?php echo $order['order_id']; ?>" style="color: #007BFF; font-weight: 600;">
                                    #<?php echo $order['order_id']; ?>
                                </a>
                            </td>
                            <td>
                                <section style="display: flex; align-items: center; gap: 10px;">
                                    <img src="<?php echo $order['image_1']; ?>" 
                                         style="width: 60px; height: 45px; object-fit: cover; border-radius: 4px;">
                                    <span><?php echo htmlspecialchars($order['service_title']); ?></span>
                                </section>
                            </td>
                            <td>
                                <?php if ($user['role'] === 'Client'): ?>
                                    <a href="profile.php?id=<?php echo $order['freelancer_id']; ?>">
                                        <?php echo htmlspecialchars($full_name); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="profile.php?id=<?php echo $order['client_id']; ?>">
                                        <?php echo htmlspecialchars($full_name); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: bold; color: #28A745;">
                                $<?php echo number_format($order['price'], 2); ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $order['status']; ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td><?php echo $order['order_date']; ?></td>
                            <td><?php echo $order['expected_delivery']; ?></td>
                            <td>
                                <a href="order-details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-primary btn-sm">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
</section>

<?php require_once 'footer.php'; ?>
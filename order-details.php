<?php
require_once 'header.php';
require_once 'dp.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$orderId = $_GET['id'] ?? '';
if (empty($orderId)) {
    $_SESSION['error'] = 'Order not found.';
    header("Location: my-orders.php");
    exit();
}

// Get order details
$orderSql = "SELECT o.*, s.title as service_title, s.category, s.delivery_time, s.revisions_included,
           freelancer.first_name as freelancerF_name, freelancer.last_name as freelancerL_name ,freelancer.user_id as freelancer_id,
           freelancer.profile_photo as freelancer_photo,
           client.first_name as clientF_name, client.last_name as clientL_name , client.user_id as client_id,
           client.profile_photo as client_photo
    FROM orders o
    JOIN services s ON o.service_id = s.service_id
    JOIN users freelancer ON o.freelancer_id = freelancer.user_id
    JOIN users client ON o.client_id = client.user_id
    WHERE o.order_id = :order_id";
$stmt = $conn->prepare($orderSql);
$stmt->execute([':order_id' => $orderId]);
$order = $stmt->fetch();
$ClientName = $order['clientF_name']. " ". $order['clientL_name'];
$freelancerName = $order['freelancerF_name']. " ". $order['freelancerL_name'];


if (!$order) {
    $_SESSION['error'] = 'Order not found.';
    header("Location: my-orders.php");
    exit();
}

// Check authorization
$isClient = ($_SESSION['user_id'] === $order['client_id']);
$isFreelancer = ($_SESSION['user_id'] === $order['freelancer_id']);

if (!$isClient && !$isFreelancer) {
    $_SESSION['error'] = 'Access denied.';
    header("Location: my-orders.php");
    exit();
}

// Get requirement files
$stmt = $conn->prepare("
    SELECT * FROM file_attachments 
    WHERE order_id = :order_id AND file_type = 'requirement'
    ORDER BY upload_timestamp
");
$stmt->execute([':order_id' => $orderId]);
$requirementFiles = $stmt->fetchAll();

// Get delivery files
$stmt = $conn->prepare("
    SELECT * FROM file_attachments 
    WHERE order_id = :order_id AND file_type = 'deliverable'
    ORDER BY upload_timestamp
");
$stmt->execute([':order_id' => $orderId]);
$deliveryFiles = $stmt->fetchAll();

// Get revision history
$stmt = $conn->prepare("
    SELECT * FROM revision_requests 
    WHERE order_id = :order_id 
    ORDER BY request_date DESC
");
$stmt->execute([':order_id' => $orderId]);
$revisions = $stmt->fetchAll();

// Calculate revision stats
$totalRequests = count($revisions);
$acceptedRequests = count(array_filter($revisions, fn($r) => $r['request_status'] === 'Accepted'));
$rejectedRequests = count(array_filter($revisions, fn($r) => $r['request_status'] === 'Rejected'));
$pendingRequests = count(array_filter($revisions, fn($r) => $r['request_status'] === 'New'));

// Check if revisions are available
$revisionsUsed = $acceptedRequests + $rejectedRequests;
$revisionsRemaining = $order['revisions_included'] == 999 ? 'Unlimited' : max(0, $order['revisions_included'] - $revisionsUsed);

// Get file type icon styling
function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    $icons = [
        'pdf' => ['color' => '#DC3545', 'bg' => '#F8D7DA', 'text' => 'PDF'],
        'doc' => ['color' => '#007BFF', 'bg' => '#D1ECF1', 'text' => 'DOC'],
        'docx' => ['color' => '#007BFF', 'bg' => '#D1ECF1', 'text' => 'DOC'],
        'jpg' => ['color' => '#28A745', 'bg' => '#D4EDDA', 'text' => 'IMG'],
        'jpeg' => ['color' => '#28A745', 'bg' => '#D4EDDA', 'text' => 'IMG'],
        'png' => ['color' => '#28A745', 'bg' => '#D4EDDA', 'text' => 'IMG'],
        'gif' => ['color' => '#28A745', 'bg' => '#D4EDDA', 'text' => 'IMG'],
        'zip' => ['color' => '#6F42C1', 'bg' => '#E2D9F3', 'text' => 'ZIP'],
        'rar' => ['color' => '#6F42C1', 'bg' => '#E2D9F3', 'text' => 'ZIP']
    ];
    
    return $icons[$ext] ?? ['color' => '#6C757D', 'bg' => '#E9ECEF', 'text' => 'FILE'];
}
?>



<section class="container">
            <link rel="stylesheet" href="css/style.css">
            <link rel="stylesheet" href="css/order-details.css">

    
    <h1 class="heading-primary">Order Details</h1>
    
    <section class="order-layout">
        <section class="order-main">
            <!-- Order Information -->
            <section class="card" style="margin-bottom: 20px;">
                <section style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                    <section>
                        <h2 style="color: #007BFF; font-size: 20px; margin-bottom: 10px;">Order #<?php echo $order['order_id']; ?></h2>
                        <h3 style="font-size: 18px; margin-bottom: 5px;"><?php echo htmlspecialchars($order['service_title']); ?></h3>
                        <p style="color: #6C757D; font-size: 14px;"><?php echo htmlspecialchars($order['category']); ?></p>
                    </section>
                    <span class="badge <?php echo $order['status']; ?>">
                        <?php echo $order['status']; ?>
                    </span>
                </section>
                
                <section style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #DEE2E6;">
                    <section>
                        <p style="font-size: 14px; color: #6C757D;">Order Date</p>
                        <p style="font-weight: 600;"><?php echo $order['order_date']; ?></p>
                    </section>
                    <section>
                        <p style="font-size: 14px; color: #6C757D;">Expected Delivery</p>
                        <p style="font-weight: 600;"><?php echo $order['expected_delivery']; ?></p>
                    </section>
                  <?php  $deliveryDate = date('Y-m-d',strtotime($order['order_date'] . ' +' . $order['delivery_time'] . ' days')); ?>

                    <?php if ($deliveryDate): ?>
                        <section>
                            <p style="font-size: 14px; color: #6C757D;">Actual Delivery</p>
                            <p style="font-weight: 600;"><?php echo $deliveryDate; ?></p>
                        </section>
                    <?php endif; ?>
                    <section>
                        <p style="font-size: 14px; color: #6C757D;">Total Amount</p>
                        <p style="font-weight: 600; color: #28A745; font-size: 18px;">$<?php echo number_format($order['price'], 2); ?></p>
                    </section>
                </section>
            </section>
            
            <!-- Service Requirements -->
            <section class="card" style="margin-bottom: 20px;">
                <h3 class="heading-tertiary">Service Requirements</h3>
                <p style="margin: 15px 0; line-height: 1.6;"><?php echo htmlspecialchars($order['requirements']); ?></p>
                
                
                <?php if (!empty($requirementFiles)): ?>
                    <h4 style="margin-top: 20px; font-size: 16px;">Requirement Files</h4>
                    <?php foreach ($requirementFiles as $file): 
                        $icon = getFileIcon($file['original_filename']);
                    ?>
                        <section class="file-item">
                            <section class="file-icon" style="background: <?php echo $icon['bg']; ?>; color: <?php echo $icon['color']; ?>; border-color: <?php echo $icon['color']; ?>;">
                                <?php echo $icon['text']; ?>
                            </section>
                            <section class="file-info">
                                <a href="<?php echo $file['file_path']; ?>" download class="file-name">
                                    <?php echo htmlspecialchars($file['original_filename']); ?>
                                </a>
                                <section class="file-size"><?php echo $file['file_size']; ?></section>
                                <section class="file-date"><?php echo $file['upload_timestamp']; ?></section>
                            </section>
                        </section>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
            
            <!-- Delivery Files -->
            <?php if (!empty($deliveryFiles)): ?>
                <section class="card" style="margin-bottom: 20px;">
                    <h3 class="heading-tertiary">Delivery Files</h3>
                    <?php foreach ($deliveryFiles as $file): 
                        $icon = getFileIcon($file['original_filename']);
                    ?>
                        <section class="file-item">
                            <section class="file-icon" style="background: <?php echo $icon['bg']; ?>; color: <?php echo $icon['color']; ?>; border-color: <?php echo $icon['color']; ?>;">
                                <?php echo $icon['text']; ?>
                            </section>
                            <section class="file-info">
                                <a href="<?php echo $file['file_path']; ?>" download class="file-name">
                                    <?php echo htmlspecialchars($file['original_filename']); ?>
                                </a>
                                <section class="file-size"><?php echo $file['file_size']; ?></section>
                                <section class="file-date"><?php echo $file['upload_timestamp']; ?></section>
                            </section>
                        </section>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
            
            <!-- Revision History -->
            <?php if (!empty($revisions)): ?>
                <section class="card" style="margin-bottom: 20px;">
                    <h3 class="heading-tertiary">Revision History</h3>
                    
                    <section style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; padding: 20px; background: #F8F9FA; border-radius: 8px;">
                        <section style="text-align: center;">
                            <section style="font-size: 24px; font-weight: bold;"><?php echo $totalRequests; ?></section>
                            <section style="font-size: 12px; color: #6C757D;">Total Requests</section>
                        </section>
                        <section style="text-align: center;">
                            <section style="font-size: 24px; font-weight: bold; color: #28A745;"><?php echo $acceptedRequests; ?></section>
                            <section style="font-size: 12px; color: #6C757D;">Accepted</section>
                        </section>
                        <section style="text-align: center;">
                            <section style="font-size: 24px; font-weight: bold; color: #DC3545;"><?php echo $rejectedRequests; ?></section>
                            <section style="font-size: 12px; color: #6C757D;">Rejected</section>
                        </section>
                        <section style="text-align: center;">
                            <section style="font-size: 24px; font-weight: bold; color: #FFC107;"><?php echo $pendingRequests; ?></section>
                            <section style="font-size: 12px; color: #6C757D;">Pending</section>
                        </section>
                    </section>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Request #</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Response</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($revisions as $index => $revision): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $revision['request_date']; ?></td>
                                    <td><?php echo htmlspecialchars($revision['revision_notes']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $revision['request_status']; ?>">
                                            <?php echo $revision['request_status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($revision['freelancer_response']): ?>
                                            <?php echo htmlspecialchars($revision['freelancer_response']); ?>
                                            <br>
                                            <small style="color: #6C757D;"><?php echo $revision['response_date']; ?></small>
                                        <?php else: ?>
                                            <span style="color: #6C757D;">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            <?php endif; ?>
        </section>
        
        <!-- Sidebar -->
        <section class="order-sidebar">
            <!-- Contact Card -->
            <section class="card" style="margin-bottom: 20px;">
                <h3 class="heading-tertiary"><?php echo $isClient ? 'Freelancer' : 'Client'; ?></h3>
                <section style="display: flex; align-items: center; gap: 15px; margin-top: 15px;">
                    <img src="<?php echo ($isClient ? $order['freelancer_photo'] : $order['client_photo']); ?>" 
                         style="width: 60px; height: 60px; border-radius: 50%;">
                    <section>
                        <a href="profile.php?id=<?php echo $isClient ? $order['freelancer_id'] : $order['client_id']; ?>" 
                           style="font-weight: 600; font-size: 16px;">
                            <?php echo htmlspecialchars($isClient ? $freelancerName: $ClientName); ?>
                        </a>
                    </section>
                </section>
            </section>
            
            <!-- Actions Card -->
            <section class="card">
                <h3 class="heading-tertiary">Actions</h3>
                
                <?php if ($order['status'] === 'Pending'): ?>
                    <?php if ($isClient): ?>
                        <a href="cancel-order.php?id=<?php echo $orderId; ?>" class="btn btn-danger" style="width: 100%; margin-top: 10px;">
                            Cancel Order
                        </a>
                    <?php elseif ($isFreelancer): ?>
                        <a href="order-details.php?id=<?php echo $orderId; ?>&action=start" 
                           class="btn btn-success" style="width: 100%; margin-top: 10px;"
                           onclick="return confirm('Start working on this order?')">
                            Start Working
                        </a>
                    <?php endif; ?>
                    
                <?php elseif ($order['status'] === 'In Progress'): ?>
                    <?php if ($isFreelancer): ?>
                        <a href="uploads-delivery.php?id=<?php echo $orderId; ?>" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                            Upload Delivery
                        </a>
                    <?php endif; ?>
                    
                <?php elseif ($order['status'] === 'Delivered'): ?>
                    <?php if ($isClient): ?>
                        <a href="complete-order.php?id=<?php echo $orderId; ?>" class="btn btn-success" style="width: 100%; margin-top: 10px;">
                            Mark as Completed
                        </a>
                        <?php if ($revisionsRemaining !== 0): ?>
                            <a href="request.php?id=<?php echo $orderId; ?>" class="btn btn-secondary" style="width: 100%; margin-top: 10px;">
                                Request Revision
                            </a>
                            <p style="font-size: 13px; color: #6C757D; margin-top: 10px; text-align: center;">
                                Revisions remaining: <?php echo $revisionsRemaining; ?>
                            </p>
                        <?php else: ?>
                            <p style="font-size: 13px; color: #DC3545; margin-top: 10px; text-align: center;">
                                All revisions used
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                <?php elseif ($order['status'] === 'Revision Requested'): ?>
                    <?php if ($isFreelancer): ?>
                        <a href="response-revision.php?id=<?php echo $orderId; ?>" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                            Respond to Revision
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </section>
    </section>
</section>

<?php 
// Handle quick action for starting work
if (isset($_GET['action']) && $_GET['action'] === 'start' && $isFreelancer && $order['status'] === 'Pending') {
    $stmt = $conn->prepare("UPDATE orders SET status = 'In Progress' WHERE order_id = :order_id");
    $stmt->execute([':order_id' => $orderId]);
    $_SESSION['success'] = 'Order status updated to In Progress.';
    header('Location: order-details.php?id=' . $orderId);
    exit();
}

require_once 'footer.php'; 
?>
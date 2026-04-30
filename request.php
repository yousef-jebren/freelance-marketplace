<?php
require_once 'header.php';

$userSql = "select * from users where user_id = :user_id";
$stmt = $conn->prepare($userSql);
$stmt->bindvalue(':user_id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch();

if ($user['role'] !== 'Client') {
    $_SESSION['error'] = 'Access denied.';
      header("Location: login.php");
      exit();
}

$orderId = $_GET['id'] ?? '';

// Get order
$stmt = $conn->prepare("
    SELECT o.*, s.revisions_included 
    FROM orders o 
    JOIN services s ON o.service_id = s.service_id 
    WHERE o.order_id = :order_id
");
$stmt->execute([':order_id' => $orderId]);
$order = $stmt->fetch();

if (!$order || $order['client_id'] !== $_SESSION['user_id']) {
    $_SESSION['error'] = 'Order not found or access denied.';
    redirect('my-orders.php');
}

if ($order['status'] !== 'Delivered') {
    $_SESSION['error'] = 'Can only request revisions for delivered orders.';
     header("order-details.php?id=' . $orderId");
      exit();
}

// Check revision availability
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM revision_requests 
    WHERE order_id = :order_id AND request_status IN ('Accepted', 'Rejected')
");
$stmt->execute([':order_id' => $orderId]);
$usedRevisions = $stmt->fetchColumn();

$revisionsRemaining = $order['revisions_included'] == 999 ? 'Unlimited' : max(0, $order['revisions_included'] - $usedRevisions);

if ($revisionsRemaining === 0) {
    $_SESSION['error'] = 'You have used all revision requests for this order.';
        header("Location: order-details.php?id=' . $orderId");
      exit();
      }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = $_POST['revision_description'] ?? '';
    $confirmed = isset($_POST['confirm_revision']);
    
    if (empty($description) || strlen($description) < 50 || strlen($description) > 500) {
        $errors['description'] = 'Revision description must be 50-500 characters.';
    }
    
    if (!$confirmed) {
        $errors['confirm'] = 'You must confirm that this counts toward your revision limit.';
    }
    
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Insert revision request
            $stmt = $conn->prepare("
                INSERT INTO revision_requests (order_id, revision_notes, request_status, request_date)
                VALUES (:order_id, :description, 'Pending', NOW())
            ");
            $stmt->execute([
                ':order_id' => $orderId,
                ':description' => $description
            ]);
            
            // Update order status
            $stmt = $conn->prepare("UPDATE orders SET status = 'Revision Requested' WHERE order_id = :order_id");
            $stmt->execute([':order_id' => $orderId]);
            
            $conn->commit();
            
            $_SESSION['success'] = 'Revision request submitted successfully!';
            header("Location: order-details.php?id=' . $orderId");
            exit();   

        } catch (Exception $e) {
            $conn->rollBack();
            die($e->getmessage());
            //$errors['general'] = 'Request failed. Please try again.';
        }
    }
}
?>

<section class="container">
        <link rel="stylesheet" href="css/style.css">
    <section class="form-container" style="max-width: 800px;">
        <h1 class="heading-primary">Request Revision</h1>
        <p style="color: #6C757D; margin-bottom: 20px;">Order #<?php echo $order['order_id']; ?></p>
        
        <section class="message-info">
            <strong>Revisions Remaining:</strong> <?php echo $revisionsRemaining; ?>
            <br>
            Both accepted and rejected revision requests count toward your limit.
        </section>
        
        <?php if (isset($errors['general'])): ?>
            <section class="message-error"><?php echo $errors['general']; ?></section>
        <?php endif; ?>
        
        <form method="POST">
            <section class="form-group">
                <label class="form-label">Revision Description *</label>
                <textarea name="revision_description" class="form-input <?php echo isset($errors['description']) ? 'error' : ''; ?>" 
                          rows="6" required placeholder="Describe what needs to be revised (50-500 characters)..."><?php echo isset($_POST['revision_description']) ? htmlspecialchars($_POST['revision_description']) : ''; ?></textarea>
                <?php if (isset($errors['description'])): ?>
                    <section class="form-error"><?php echo $errors['description']; ?></section>
                <?php endif; ?>
            </section>
            
            <section class="form-group">
                <label>
                    <input type="checkbox" name="confirm_revision" required>
                    I understand this request counts toward my revision limit
                </label>
                <?php if (isset($errors['confirm'])): ?>
                    <section class="form-error"><?php echo $errors['confirm']; ?></section>
                <?php endif; ?>
            </section>
            
            <section class="form-actions">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Submit Request</button>
                <a href="order-details.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">Cancel</a>
            </section>
        </form>
    </section>
</section>

<?php require_once 'footer.php'; ?>
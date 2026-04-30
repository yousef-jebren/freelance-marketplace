<?php
require_once 'header.php';

$userSql = "select * from users where user_id = :user_id";
$stmt = $conn->prepare($userSql);
$stmt->bindvalue(':user_id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch();

if ($user['role'] !== 'Freelancer') {
    $_SESSION['error'] = 'Access denied.';
      header("Location:login.php");
      exit();
}

$orderId = $_GET['id'] ?? '';

// Get order and latest revision
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = :order_id");
$stmt->execute([':order_id' => $orderId]);
$order = $stmt->fetch();

if (!$order || $order['freelancer_id'] !== $_SESSION['user_id']) {
    $_SESSION['error'] = 'Order not found or access denied.';
    header("Location: my-orders.php");
    exit();
}

if ($order['status'] !== 'Revision Requested') {
    $_SESSION['error'] = 'No pending revision request for this order.';
    header("Location: order-details.php?id=" . $orderId);
    exit();
}

// Get latest revision request
$stmt = $conn->prepare("
    SELECT * FROM revision_requests 
    WHERE order_id = :order_id AND request_status = 'Pending' 
    ORDER BY request_date DESC 
    LIMIT 1
");
$stmt->execute([':order_id' => $orderId]);
$revision = $stmt->fetch();

if (!$revision) {
    $_SESSION['error'] = 'No pending revision request found.';
    header("Location: order-details.php?id=" . $orderId);
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'accept') {
        // Accept and upload revised work
        $uploadedFiles = [];
        
        if (empty($_FILES['revised_files']['name'][0])) {
            $errors['files'] = 'Please upload revised files.';
        } else {
            foreach ($_FILES['revised_files']['name'] as $key => $filename) {
                if (!empty($filename)) {
                    $file = [
                        'name' => $_FILES['revised_files']['name'][$key],
                        'type' => $_FILES['revised_files']['type'][$key],
                        'tmp_name' => $_FILES['revised_files']['tmp_name'][$key],
                        'error' => $_FILES['revised_files']['error'][$key],
                        'size' => $_FILES['revised_files']['size'][$key]
                    ];
                    
                    if ($file['size'] <= 50 * 1024 * 1024 && $file['error'] === UPLOAD_ERR_OK) {
                        $uploadedFiles[] = $file;
                    }
                }
            }
        }
        
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // Upload revised files
                $uploadDir = "uploads/orders/$orderId/deliverables/";

                if (!is_dir($uploadDir)) {
                     mkdir($uploadDir, 0755, true);
                }

                foreach ($uploadedFiles as $file) {
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $filename = 'revised_' . uniqid() . '.' . $extension;
                    $filepath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $sql = "INSERT INTO file_attachments (
                            order_id, file_path, original_filename, file_size, file_type, upload_timestamp
                        ) VALUES (
                            :order_id, :file_path, :original_filename, :file_size, 'deliverable', NOW()
                        )";
                        
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([
                            ':order_id' => $orderId,
                            ':file_path' => $filepath,
                            ':original_filename' => $file['name'],
                            ':file_size' => $file['size'],
                        ]);
                    }
                }
                
                // Update revision request
                $stmt = $conn->prepare("
                    UPDATE revision_requests SET 
                        request_status = 'Accepted',
                        freelancer_response = 'Revision accepted and uploaded',
                        response_date = NOW()
                    WHERE revision_id = :revision_id
                ");
                $stmt->execute([':revision_id' => $revision['revision_id']]);
                
                // Update order
                $stmt = $conn->prepare("UPDATE orders SET status = 'Delivered' WHERE order_id = :order_id");
                $stmt->execute([':order_id' => $orderId]);
                
                $conn->commit();
                
                $_SESSION['success'] = 'Revision accepted and uploaded!';
                header("Location: order-details.php?id=" . $orderId);
                exit();
                
            } catch (Exception $e) {
                $conn->rollBack();
                $errors['general'] = 'Upload failed. Please try again.';
            }
        }
        
    } elseif ($action === 'reject') {
        // Reject revision
        $rejectionReason = sanitize($_POST['rejection_reason'] ?? '');
        
        if (empty($rejectionReason) || strlen($rejectionReason) < 50 || strlen($rejectionReason) > 500) {
            $errors['rejection_reason'] = 'Rejection reason must be 50-500 characters.';
        }
        
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // Update revision request
                $stmt = $conn->prepare("
                    UPDATE revision_requests SET 
                        request_status = 'Rejected',
                        freelancer_response = :reason,
                        response_date = NOW()
                    WHERE revision_id = :revision_id
                ");
                $stmt->execute([
                    ':reason' => $rejectionReason,
                    ':revision_id' => $revision['revision_id']
                ]);
                
                // Update order
                $stmt = $conn->prepare("UPDATE orders SET status = 'Delivered' WHERE order_id = :order_id");
                $stmt->execute([':order_id' => $orderId]);
                
                $conn->commit();
                
                $_SESSION['success'] = 'Revision request rejected.';
                header("Location: order-details.php?id=" . $orderId);
                exit();
                
            } catch (Exception $e) {
                $conn->rollBack();
                $errors['general'] = 'Rejection failed. Please try again.';
            }
        }
    }
}
?>

<section class="container">
        <link rel="stylesheet" href="css/style.css">
    <section class="form-container" style="max-width: 800px;">
        <h1 class="heading-primary">Respond to Revision Request</h1>
        <p style="color: #6C757D; margin-bottom: 20px;">Order #<?php echo $order['order_id']; ?></p>
        
        <section class="card" style="margin-bottom: 20px;">
            <h3 class="heading-tertiary">Client's Revision Request</h3>
            <p style="margin: 15px 0; line-height: 1.6;">
                <?php echo htmlspecialchars($revision['revision_notes']); ?>
            </p>
            <p style="font-size: 13px; color: #6C757D;">
                Requested on: <?php echo $revision['request_date']; ?>
            </p>
        </section>
        
        <?php if (isset($errors['general'])): ?>
            <section class="message-error"><?php echo $errors['general']; ?></section>
        <?php endif; ?>
        
        <!-- Accept Form -->
        <section class="card" style="margin-bottom: 20px;">
            <h3 class="heading-tertiary">Accept & Upload Revised Work</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="accept">
                
                <section class="form-group">
                    <label class="form-label">Upload Revised Files *</label>
                    <input type="file" name="revised_files[]" class="form-input <?php echo isset($errors['files']) ? 'error' : ''; ?>" 
                           multiple required>
                    <p style="font-size: 13px; color: #6C757D; margin-top: 5px;">
                        Upload 1-5 files, max 50MB each
                    </p>
                    <?php if (isset($errors['files'])): ?>
                        <section class="form-error"><?php echo $errors['files']; ?></section>
                    <?php endif; ?>
                </section>
                
                <button type="submit" class="btn btn-success" style="width: 100%;">Accept & Upload Revision</button>
            </form>
        </section>
        
        <!-- Reject Form -->
        <section class="card">
            <h3 class="heading-tertiary">Reject Revision Request</h3>
            <form method="POST">
                <input type="hidden" name="action" value="reject">
                
                <section class="form-group">
                    <label class="form-label">Rejection Reason *</label>
                    <textarea name="rejection_reason" class="form-input <?php echo isset($errors['rejection_reason']) ? 'error' : ''; ?>" 
                              rows="5" required placeholder="Explain why this revision is out of scope or unreasonable (50-500 characters)..."><?php echo isset($_POST['rejection_reason']) ? htmlspecialchars($_POST['rejection_reason']) : ''; ?></textarea>
                    <?php if (isset($errors['rejection_reason'])): ?>
                        <section class="form-error"><?php echo $errors['rejection_reason']; ?></section>
                    <?php endif; ?>
                </section>
                
                <section style="display: flex; gap: 15px;">
                    <button type="submit" class="btn btn-danger" style="flex: 1;">Reject Request</button>
                    <a href="order-details.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">Cancel</a>
                </section>
            </form>
        </section>
    </section>
</section>

<?php require_once 'footer.php'; ?>
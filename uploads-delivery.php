<?php
require_once 'header.php';
require_once 'dp.php';

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

// Get order
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = :order_id");
$stmt->bindvalue(':order_id', $orderId);
$stmt->execute();
$order = $stmt->fetch();

if (!$order || $order['freelancer_id'] !== $_SESSION['user_id']) {
    $_SESSION['error'] = 'Order not found or access denied.';
      header("Location: my-orders.php");
    exit();
}

if (!in_array($order['status'], ['In Progress', 'Revision Requested'])) {
    $_SESSION['error'] = 'Cannot upload delivery for this order status.';
    header("Location: order-details.php?id=" . $orderId);
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deliveryMessage = $_POST['delivery_message'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    // Validation
    if (empty($deliveryMessage) || strlen($deliveryMessage) < 50 || strlen($deliveryMessage) > 500) {
        $errors['delivery_message'] = 'Delivery message must be 50-500 characters.';
    }
    
    // Handle file uploads
    $uploadedFiles = [];
    if (empty($_FILES['delivery_files']['name'][0])) {
        $errors['files'] = 'At least one delivery file is required.';
    } else {
        $fileCount = 0;
        foreach ($_FILES['delivery_files']['name'] as $key => $filename) {
            if (!empty($filename)) {
                $fileCount++;
                if ($fileCount > 5) {
                    $errors['files'] = 'Maximum 5 files allowed.';
                    break;
                }
                
                $file = [
                    'name' => $_FILES['delivery_files']['name'][$key],
                    'type' => $_FILES['delivery_files']['type'][$key],
                    'tmp_name' => $_FILES['delivery_files']['tmp_name'][$key],
                    'error' => $_FILES['delivery_files']['error'][$key],
                    'size' => $_FILES['delivery_files']['size'][$key]
                ];
                
                if ($file['size'] > 50 * 1024 * 1024) {
                    $errors['files'] = 'Each file must be under 50MB.';
                    break;
                }
                
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $uploadedFiles[] = $file;
                }
            }
        }
        
        if ($fileCount === 0) {
            $errors['files'] = 'At least one file is required.';
        }
    }
    
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Create delivery directory
            $uploadDir = "uploads/orders/$orderId/deliverables/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Upload files
            foreach ($uploadedFiles as $file) {
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $filename = uniqid() . '.' . $extension;
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
                        ':file_size' => $file['size']
                    ]);
                }
            }
            
            // Update order status
            $stmt = $conn->prepare("
                UPDATE orders SET 
                    status = 'Delivered',
                    deliverable_notes = :notes
                WHERE order_id = :order_id
            ");
            $stmt->execute([
                ':notes' => $notes,
                ':order_id' => $orderId
            ]);
            
            $conn->commit();
            
            $_SESSION['success'] = 'Delivery uploaded successfully!';
            header("Location: order-details.php?id=" . $orderId);
            exit();
            
        } catch (Exception $e) {
            $conn->rollBack();
            die($e->getmessage());
            //$errors['general'] = 'Upload failed. Please try again.';
        }
    }
}
?>

<section class="container">
        <link rel="stylesheet" href="css/style.css">
    <section class="form-container" style="max-width: 800px;">
        <h1 class="heading-primary">Upload Delivery</h1>
        <p style="color: #6C757D; margin-bottom: 20px;">Order #<?php echo $order['order_id']; ?></p>
        
        <?php if (isset($errors['general'])): ?>
            <section class="message-error"><?php echo $errors['general']; ?></section>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <section class="form-group">
                <label class="form-label">Delivery Message *</label>
                <textarea name="delivery_message" class="form-input <?php echo isset($errors['delivery_message']) ? 'error' : ''; ?>" 
                          rows="5" required placeholder="Describe what you've delivered (50-500 characters)..."><?php echo isset($_POST['delivery_message']) ? htmlspecialchars($_POST['delivery_message']) : ''; ?></textarea>
                <?php if (isset($errors['delivery_message'])): ?>
                    <section class="form-error"><?php echo $errors['delivery_message']; ?></section>
                <?php endif; ?>
            </section>
            
            <section class="form-group">
                <label class="form-label">Delivery Files * (1-5 files, max 50MB each)</label>
                <input type="file" name="delivery_files[]" class="form-input <?php echo isset($errors['files']) ? 'error' : ''; ?>" 
                       multiple required>
                <?php if (isset($errors['files'])): ?>
                    <section class="form-error"><?php echo $errors['files']; ?></section>
                <?php endif; ?>
            </section>
            
            <section class="form-group">
                <label class="form-label">Additional Notes (Optional)</label>
                <textarea name="notes" class="form-input" rows="3" 
                          placeholder="Any additional information for the client..."><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
            </section>
            
            <section class="form-actions">
                <button type="submit" class="btn btn-success" style="flex: 1;">Upload Delivery</button>
                <a href="order-details.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">Cancel</a>
            </section>
        </form>
    </section>
</section>

<?php require_once 'footer.php'; ?>
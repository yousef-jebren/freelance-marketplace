<?php
require_once 'Service.php';
require_once 'header.php';

// Check if user is logged in as client
if (!$_SESSION['user_id']) {
    header("Location: login.php?error=Please login to checkout.");
    exit();
    }

// Check if cart is empty
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header("Location: cart.php?error=Your cart is empty");
    exit();
}

// Validate all services are still active
foreach ($cart as $key => $service) {
    $statusSql = "SELECT status FROM services WHERE service_id = :id";
    $stmt = $conn->prepare($statusSql);
    $stmt->execute([':id' => $service->getServiceId()]);
    $status = $stmt->fetchColumn();
    
    if ($status !== 'Active') {
        unset($_SESSION['cart'][$key]);
        $_SESSION['warning'] = "Service '" . $service->getTitle() . "' is no longer available and has been removed.";
    }
}
$cart = array_values($_SESSION['cart'] ?? []);

if (empty($cart)) {
    header("Location: cart.php");
    exit();
}

// Determine current step
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$step = max(1, min(3, $step));
$paymentData = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step1_submit'])) {
        // Validate Step 1 - Service Requirements
        $errors = [];
        $requirements = [];
        
        foreach ($cart as $index => $service) {
            $serviceId = $service->getServiceId();
            $req = $_POST["requirements_$index"] ?? '';
            $instructions = $_POST["instructions_$index"] ?? '';
            $deadline = $_POST["deadline_$index"] ?? '';
            
            if (empty($req) || strlen($req) < 50 || strlen($req) > 1000) {
                $errors[$index] = 'Service requirements must be 50-1000 characters.';
            }
            
            if (!empty($instructions) && strlen($instructions) > 500) {
                $errors[$index] = 'Special instructions max 500 characters.';
            }
            
            if (!empty($deadline)) {
                $minDate = date('Y-m-d', strtotime("+{$service->getDeliveryTime()} days"));
                if ($deadline < $minDate) {
                    $errors[$index] = 'Deadline must be at least ' . $service->getDeliveryTime() . ' days from today.';
                }
            }
            
            // Handle file uploads
            $uploadedFiles = [];
            if (isset($_FILES["files_$index"])) {
                foreach ($_FILES["files_$index"]['name'] as $key => $filename) {
                    if (!empty($filename)) {
                        $file = [
                            'name' => $_FILES["files_$index"]['name'][$key],
                            'type' => $_FILES["files_$index"]['type'][$key],
                            'tmp_name' => $_FILES["files_$index"]['tmp_name'][$key],
                            'error' => $_FILES["files_$index"]['error'][$key],
                            'size' => $_FILES["files_$index"]['size'][$key]
                        ];
                        
                        $allowedTypes = ['pdf', 'doc', 'docx', 'txt', 'zip', 'jpg', 'png'];
                        $validation = validateFileUpload($file, $allowedTypes, 10 * 1024 * 1024);
                        
                        if ($validation === true) {
                            $uploadedFiles[] = $file;
                        } else {
                            $errors[$index] = $validation;
                        }
                    }
                }
                
                if (count($uploadedFiles) > 3) {
                    $errors[$index] = 'Maximum 3 files per service.';
                }
            }
            
            $requirements[$index] = [
                'service_id' => $serviceId,
                'requirements' => $req,
                'instructions' => $instructions,
                'deadline' => $deadline,
                'files' => $uploadedFiles
            ];
        }
        
        if (empty($errors)) {
            $_SESSION['checkout_step1'] = $requirements;
            header("Location: checkout.php?step=2");
            exit();
        }
    } elseif (isset($_POST['step2_submit'])) {
        // Validate Step 2 - Payment Information
        $errors = [];
        $paymentData = [
            'payment_method' => $_POST['payment_method'] ?? '',
            'address_line1' => $_POST['address_line1'] ?? '',
            'address_line2' => $_POST['address_line2'] ?? '',
            'city' => $_POST['city'] ?? '',
            'state' => $_POST['state'] ?? '',
            'postal_code' => $_POST['postal_code'] ?? '',
            'country' => $_POST['country'] ?? ''
        ];
        
        if ($paymentData['payment_method'] === 'credit_card') {
            $cardNumber = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
            $cardName = $_POST['card_name'] ?? '';
            $expiryDate = $_POST['expiry_date'] ?? '';
            $cvv = $_POST['cvv'] ?? '';
            
            if (!preg_match('/^\d{16}$/', $cardNumber)) {
                $errors['card_number'] = 'Card number must be 16 digits.';
            }
            
            if (empty($cardName) || !preg_match('/^[a-zA-Z\s]{2,100}$/', $cardName)) {
                $errors['card_name'] = 'Cardholder name is required (2-100 characters).';
            }
            
            if (!preg_match('/^\d{3}$/', $cvv)) {
                $errors['cvv'] = 'CVV must be 3 digits.';
            }
            
            // Don't store sensitive card data
            $paymentData['card_last4'] = substr($cardNumber, -4);
        }
        
        if (empty($paymentData['address_line1'])) {
            $errors['address'] = 'Address is required.';
        }
        
        if (empty($errors)) {
            $_SESSION['checkout_step2'] = $paymentData;
            header("Location: checkout.php?step=3");
            exit();
        }
    } elseif (isset($_POST['place_order'])) {
        // Process Order
        if (!isset($_POST['terms_agreement'])) {
            $_SESSION['error'] = 'You must agree to terms and conditions.';
            header("Location: checkout.php?step=3");
            exit();
        }
        
        try {
            $conn->beginTransaction();
            
            $transactionId = 'TXN' . time() . rand(1000, 9999);
            $requirements = $_SESSION['checkout_step1'] ?? [];
            $orderIds = [];
            
            foreach ($cart as $index => $service) {
                $orderId = random_int(1000000000,9999999999);
                $serviceId = $service->getServiceId();
                $freelancerId = $service->getFreelancerId();
                $price = $service->getPrice();
                $serviceFee = $service->calculateServiceFee();
                $total = $service->getTotalWithFee();
                $expectedDelivery = date('Y-m-d', strtotime("+{$service->getDeliveryTime()} days"));
                
                $req = $requirements[$index] ?? [];
                
                $sql = "INSERT INTO orders (order_id,service_id,client_id,freelancer_id,service_title,price,delivery_time,requirements,status,   payment_method,    order_date,    expected_delivery) VALUES (
    :order_id, :service_id, :client_id, :freelancer_id,:title,
    :price,:delivery_time, :requirements,'Pending',:payment_method ,NOW(), :expected_delivery
)";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':order_id' => $orderId,
                    ':service_id' => $serviceId,
                    ':client_id' => $_SESSION['user_id'],
                    ':freelancer_id' => $freelancerId,
                    ':title' => $service->getTitle(),                   
                    ':price' => $total,
                    ':delivery_time' => $service->getDeliveryTime(),
                    ':requirements' => $req['requirements'] ?? '',
                    ':payment_method' => 'credit_card',
                    ':expected_delivery' => $expectedDelivery,
                ]);
                
                // Handle requirement files
                if (!empty($req['files'])) {
                    $uploadDir = "uploads/orders/$orderId/requirements/";
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    foreach ($req['files'] as $file) {
                        $extension = getFileExtension($file['name']);
                        $filename = uniqid() . '.' . $extension;
                        $filepath = $uploadDir . $filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $filepath)) {
                            $fileSql = "INSERT INTO file_attachments (
                                order_id, file_path, original_filename, file_size,file_type, upload_timestamp
                            ) VALUES (
                                :order_id, :file_path, :original_filename, :file_size, 'requirement', NOW()
                            )";
                            
                            $fileStmt = $conn->prepare($fileSql);
                            $fileStmt->execute([
                                ':order_id' => $orderId,
                                ':file_path' => $filepath,
                                ':original_filename' => $file['name'],
                                ':file_size' => $file['size']
                            ]);
                        }
                    }
                }
                
                $orderIds[] = $orderId;
            }
            
            $conn->commit();
            
            // Clear cart and checkout session
            unset($_SESSION['cart']);
            unset($_SESSION['checkout_step1']);
            unset($_SESSION['checkout_step2']);
            
            // Store order IDs for success page
            $_SESSION['order_ids'] = $orderIds;
            
            header("Location: order-success.php");
            exit();
            
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error'] = 'Order placement failed. Please try again.';
        }
    }
}

$totals = 0;
$subtotal = 0;
    foreach ($cart as $service) {
        $subtotal += $service->getPrice();
    }
    
    $serviceFee = $subtotal * 0.05;
    $total = $subtotal + $serviceFee;

    $countries = [];
    $countriesSql = "select DISTINCT country from users";
    $stmt = $conn->prepare($countriesSql);
    $stmt->execute();
    $countries = $stmt->fetch();
?>





<section class="container">
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="css/steps.css">
    <h1 class="heading-primary">Checkout</h1>
    
    <!-- Progress Indicator -->
    <section class="step-indicator">
        <section class="step-item <?php echo $step > 1 ? 'step-completed' : ($step == 1 ? 'step-active' : 'step-inactive'); ?>">
            <section class="step-icon"><?php echo $step > 1 ? '' : '1'; ?></section>
            <span>Service Requirements</span>
        </section>
        <section class="step-item <?php echo $step > 2 ? 'step-completed' : ($step == 2 ? 'step-active' : 'step-inactive'); ?>">
            <section class="step-icon"><?php echo $step > 2 ? '' : '2'; ?></section>
            <span>Payment Information</span>
        </section>
        <section class="step-item <?php echo $step == 3 ? 'step-active' : 'step-inactive'; ?>">
            <section class="step-icon">3</section>
            <span>Review & Confirm</span>
        </section>
    </section>
    
    <?php if ($step == 1): ?>
        <!-- Step 1: Service Requirements -->
        <form method="POST">
            <?php foreach ($cart as $index => $service): ?>
                <section class="card" style="margin-bottom: 20px;">
                    <h3><?php echo htmlspecialchars($service->getTitle()); ?></h3>
                    <p style="color: #6C757D;">Freelancer: <?php echo htmlspecialchars($service->getFreelancerName()); ?></p>
                    <p style="color: #28A745; font-weight: bold;"><?php echo $service->getFormattedPrice(); ?></p>
                    
                    <section class="form-group">
                        <label class="form-label">Service Requirements *</label>
                        <textarea name="requirements_<?php echo $index; ?>" class="form-input" rows="5" required
                                  placeholder="Describe your requirements in detail (50-1000 characters)..."><?php echo isset($_POST["requirements_$index"]) ? htmlspecialchars($_POST["requirements_$index"]) : ''; ?></textarea>
                    </section>
                    
                    <section class="form-group">
                        <label class="form-label">Special Instructions (Optional)</label>
                        <textarea name="instructions_<?php echo $index; ?>" class="form-input" rows="3"
                                  placeholder="Any special instructions (max 500 characters)..."><?php echo isset($_POST["instructions_$index"]) ? htmlspecialchars($_POST["instructions_$index"]) : ''; ?></textarea>
                    </section>
                    
                    <section class="form-group">
                        <label class="form-label">Preferred Deadline (Optional)</label>
                        <input type="date" name="deadline_<?php echo $index; ?>" class="form-input"
                               min="<?php echo date('Y-m-d', strtotime("+{$service->getDeliveryTime()} days")); ?>"
                               value="<?php echo isset($_POST["deadline_$index"]) ? $_POST["deadline_$index"] : ''; ?>">
                    </section>
                    
                    <section class="form-group">
                        <label class="form-label">Requirement Files (Optional, max 3 files, 10MB each)</label>
                        <input type="file" name="files_<?php echo $index; ?>[]" class="form-input" multiple accept=".pdf,.doc,.docx,.txt,.zip,.jpg,.png">
                    </section>
                </section>
            <?php endforeach; ?>
            
            <section class="form-actions">
                <button type="submit" name="step1_submit" class="btn btn-primary">Continue to Payment</button>
                <a href="cart.php" class="btn btn-secondary">Edit Cart</a>
            </section>
        </form>
        
    <?php elseif ($step == 2): ?>
        <!-- Step 2: Payment Information -->
        <form method="POST">
            <section class="card">
                <h2 class="heading-secondary">Payment Method</h2>
                
                <section class="form-group">
                    <label>
                        <input type="radio" name="payment_method" value="credit_card" checked required>
                        Credit Card
                    </label>
                </section>
                
                <section class="form-group">
                    <label class="form-label">Card Number *</label>
                    <input type="text" name="card_number" class="form-input" placeholder="1234 5678 9012 3456" required>
                </section>
                
                <section class="form-group">
                    <label class="form-label">Cardholder Name *</label>
                    <input type="text" name="card_name" class="form-input" required>
                </section>
                
                <section style="display: flex; gap: 15px;">
                    <section class="form-group" style="flex: 1;">
                        <label class="form-label">Expiration Date *</label>
                        <input type="text" name="expiry_date" class="form-input" placeholder="MM/YY" required>
                    </section>
                    <section class="form-group" style="flex: 1;">
                        <label class="form-label">CVV *</label>
                        <input type="text" name="cvv" class="form-input" placeholder="123" maxlength="3" required>
                    </section>
                </section>
                
                <h3 class="heading-tertiary" style="margin-top: 30px;">Billing Address</h3>
                
                <section class="form-group">
                    <label class="form-label">Address Line 1 *</label>
                    <input type="text" name="address_line1" class="form-input" required>
                </section>
                
                <section class="form-group">
                    <label class="form-label">Address Line 2</label>
                    <input type="text" name="address_line2" class="form-input">
                </section>
                
                <section style="display: flex; gap: 15px;">
                    <section class="form-group" style="flex: 1;">
                        <label class="form-label">City *</label>
                        <input type="text" name="city" class="form-input" required>
                    </section>
                    <section class="form-group" style="flex: 1;">
                        <label class="form-label">State/Province *</label>
                        <input type="text" name="state" class="form-input" required>
                    </section>
                </section>
                
                <section style="display: flex; gap: 15px;">
                    <section class="form-group" style="flex: 1;">
                        <label class="form-label">Postal Code *</label>
                        <input type="text" name="postal_code" class="form-input" required>
                    </section>
                    <section class="form-group" style="flex: 1;">
                        <label class="form-label">Country *</label>
                        
                        <select name="country" class="form-select" required>
                            <option value="">Select Country</option>
                            <?php foreach ($countries as $country): ?>
                                <option value="<?php echo $country; ?>"><?php echo $country; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </section>
                </section>
            </section>
            
            <section class="form-actions">
                <button type="submit" name="step2_submit" class="btn btn-primary">Continue to Review</button>
                <a href="checkout.php?step=1" class="btn btn-secondary">Back</a>
            </section>
        </form>
        
    <?php elseif ($step == 3): ?>
        <!-- Step 3: Review & Confirm -->
        <section class="checkout-layout">
            <section class="checkout-main">
                <form method="POST">
                    <section class="card">
                        <h2 class="heading-secondary">Review Your Order</h2>
                        <p style="font-size: 18px; color: #6C757D; margin-bottom: 20px;">
                            You will place <?php echo count($cart); ?> order<?php echo count($cart) != 1 ? 's' : ''; ?>
                        </p>
                        
                        <section class="form-group">
                            <label>
                                <input type="checkbox" name="terms_agreement" required>
                                I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and 
                                <a href="privacy.php" target="_blank">Privacy Policy</a>
                            </label>
                        </section>
                        
                        <button type="submit" name="place_order" class="btn btn-success" style="width: 100%; font-size: 18px; padding: 15px;">
                            Place Order
                        </button>
                    </section>
                </form>
            </section>
            
            <section class="checkout-sidebar">
                <section class="card" style="position: sticky; top: 20px;">
                    <h3 class="heading-tertiary">Order Summary</h3>
                    <p style="margin-bottom: 15px;">You will place <?php echo count($cart); ?> orders</p>
                    
                    <?php foreach ($cart as $service): ?>
                        <section style="border-bottom: 1px solid #DEE2E6; padding: 15px 0;">
                            <p style="font-weight: 600;"><?php echo htmlspecialchars($service->getTitle()); ?></p>
                            <p style="font-size: 14px; color: #6C757D;"><?php echo htmlspecialchars($service->getFreelancerName()); ?></p>
                            <p style="font-weight: bold; color: #28A745;"><?php echo $service->getFormattedTotal(); ?></p>
                        </section>
                    <?php endforeach; ?>
                    
                    <section style="margin-top: 20px;">
                        <section style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </section>
                        <section style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Service Fee:</span>
                            <span>$<?php echo number_format($serviceFee, 2); ?></span>
                        </section>
                        <hr>
                        <section style="display: flex; justify-content: space-between; font-size: 18px; font-weight: bold;">
                            <span>Total:</span>
                            <span style="color: #28A745;">$<?php echo number_format($total, 2); ?></span>
                        </section>
                    </section>
                </section>
            </section>
        </section>
    <?php endif; ?>
</section>

<?php require_once 'footer.php'; ?>
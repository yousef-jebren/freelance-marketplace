<?php
require_once 'Service.php';
session_start();

if (!isset($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$serviceId = $_GET['service_id'] ?? null;

if (!$serviceId) {
    header("Location: cart.php");
    exit();
}

// Loop through cart and remove service
foreach ($_SESSION['cart'] as $index => $service) {
    if ($service->getServiceId() == $serviceId) {
        unset($_SESSION['cart'][$index]);
        break;
    }
}

// Re-index array
$_SESSION['cart'] = array_values($_SESSION['cart']);


// Redirect back to cart
header("Location: cart.php?success=service-removed");
exit();

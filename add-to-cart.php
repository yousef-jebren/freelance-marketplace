<?php
require_once 'service.php';
session_start();
require_once 'dp.php';

$userID = $_SESSION['user_id'] ?? null;
if (!$userID) {
    header("Location: login.php?error=must-login");
    exit();
}

$ServiceID = $_GET['service_id'];
if (!isset($ServiceID)) {
    header("Location: service-detalis.php");
    exit();
}   

// Check if the service exists
$serviceSql = "SELECT * FROM services WHERE service_id = :serviceID AND status = 'Active'";
$serviceStmt = $conn->prepare($serviceSql);
$serviceStmt->bindValue(':serviceID', $ServiceID);
$serviceStmt->execute();
$serviceTaken = $serviceStmt->fetch();
if (!$serviceTaken) {
    header("Location: service-detalis.php?error=service-not-found");
    exit();
}
$freelanceSql = "SELECT * FROM users WHERE user_id = :userID";
$freelanceStmt = $conn->prepare($freelanceSql);
$freelanceStmt->bindValue(':userID', $serviceTaken['freelancer_id']);
$freelanceStmt->execute();
$freelancer = $freelanceStmt->fetch();

// get data
$title = $serviceTaken['title'];
$price = $serviceTaken['price'];
$freelancerID = $serviceTaken['freelancer_id'];

$category = $serviceTaken['category'];
$subcategory = $serviceTaken['subcategory'];
$deliveryTime = $serviceTaken['delivery_time'];
$revisionsIncluded = $serviceTaken['revisions_included'];
$image_1 = $serviceTaken['image_1'];
$time = date('Y-m-d H:i:s');
// Insert into cart table
$serviceAdded = [
    'service_id' => $ServiceID,
    'title' => $title,
    'price' => $price,
    'freelancer_id' => $freelancerID,
    'freelancer_name' => $freelancer['first_name'] . ' ' . $freelancer['last_name'],
    'category' => $category,
    'subcategory' => $subcategory,
    'delivery_time' => $deliveryTime,
    'revisions_included' => $revisionsIncluded,
    'image_1' => $image_1,
    'added_timestamp' => $time
];
$service = new Service($serviceAdded);

if(!isset($_SESSION['cart'])){
    $_SESSION['cart'] = [];
}

foreach ($_SESSION['cart'] as $item) {
    if ($item->getServiceId() == $ServiceID) {
        header("Location: service-detalis.php?service_id=$ServiceID&error=service-already-in-cart");
        exit();
    }
}


$_SESSION['cart'][] = $service;
header("Location: service-detalis.php?service_id=$ServiceID&success=Add-to-cart");
exit();
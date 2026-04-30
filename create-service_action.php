<?php 
session_start();
require_once 'dp.php';


$userId = $_SESSION['user_id'];
$createDate = date('Y-m-d H:i:s');
$status = "Active";
$featured = "No";



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step1_submit'])) {

    $serviceID = rand(1000000000, 9999999999);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM services WHERE service_id = ?");
    do {
        $stmt->execute([$serviceID]);
        $exists = $stmt->fetchColumn();
        if ($exists) {
            $serviceID = rand(1000000000, 9999999999);
        }
    } while ($exists);

    $_SESSION['service_id'] = $serviceID;


    $title = $_POST['title'];
    $category = $_POST['category'];
    $subcategory = $_POST['subcategory'];
    $description = $_POST['description'];
    $delivery_time = $_POST['delivery_time'];
    $price = $_POST['price'];
    $revision_included = $_POST['revisions'];

    // Validate data 
    if (empty($title) || empty($category) || empty($subcategory) || empty($description) || empty($delivery_time) || empty($price) || !is_numeric($delivery_time) || !is_numeric($price) || !is_numeric($revision_included)) {
        header("Location: create-service.php?step=1&error=please_fill_all_fields");
        exit();
    }
    // Store data in session
    $_SESSION['service_data'] = [
        'title' => $title,
        'category' => $category,
        'subcategory' => $subcategory,
        'description' => $description,
        'delivery_time' => $delivery_time,
        'price' => $price,
        'revisions' => $revision_included
    ];



    // Redirect to step 2
    header("Location: create-service.php?step=2");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step2_submit'])) {

    if (!isset($_SESSION['service_id'])) {
        header("Location: create-service.php?step=1&error=session_expired");
        exit();
    }
    $serviceID = $_SESSION['service_id'];

    $image1Name = "";
    if (isset($_FILES['image1']) && $_FILES['image1']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['image1']['name'], PATHINFO_EXTENSION));
        $image1Name = $_FILES['image1']['name'] . "." . $ext;
        if (!is_dir('uploads/services/' . $serviceID)) {
            mkdir('uploads/services/' . $serviceID, 0755, true);
        }
        move_uploaded_file(
            $_FILES['image1']['tmp_name'],
            "uploads/services/" . $serviceID . "/" . $image1Name
        );
    } else {
        header("Location: create-service.php?step=2&error=image1_required");
        exit();
    }

    $image2Name = "";
    if (isset($_FILES['image2']) && $_FILES['image2']['error'] === UPLOAD_ERR_OK) {
        $exe = strtolower(pathinfo($_FILES['image2']['name'], PATHINFO_EXTENSION));
        $image2Name = $_FILES['image2']['name'] . "." . $exe;
        move_uploaded_file($_FILES['image2']['tmp_name'], 'uploads/services/' . $serviceID . '/' . $image2Name);
    }

    

    $image3Name = "";
    if (isset($_FILES['image3']) && $_FILES['image3']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image3']['name'], PATHINFO_EXTENSION));
        $image3Name = $_FILES['image3']['name'] . "." . $ext;
        move_uploaded_file($_FILES['image3']['tmp_name'], 'uploads/services/' . $serviceID . '/' . $image3Name);
    }

    $_SESSION['service_data']['image1'] = $image1Name;
    $_SESSION['service_data']['image2'] = $image2Name;  
    $_SESSION['service_data']['image3'] = $image3Name;



    // Redirect to step 3
    header("Location: create-service.php?step=3");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step3_submit'])) {
   if (!isset($_SESSION['service_id'], $_SESSION['service_data'])) {
        header("Location: create-service.php?step=1&error=session_expired");
        exit();
    }
    $serviceID = $_SESSION['service_id'];
    $data = $_SESSION['service_data'];

    $sql = "INSERT INTO services 
    (service_id, freelancer_id, title, category, subcategory, description, price, delivery_time, revisions_included, image_1, image_2, image_3, status, featured, created_date)
    VALUES 
    (:serviceID, :userId, :title, :category, :subcategory, :description, :price, :delivery_time, :revision_included, :image1, :image2, :image3, :status, :featured, NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':serviceID', $serviceID);
    $stmt->bindValue(':userId', $userId);
    $stmt->bindValue(':title', $data['title']);
    $stmt->bindValue(':category', $data['category']);
    $stmt->bindValue(':subcategory', $data['subcategory']);
    $stmt->bindValue(':description', $data['description']);
    $stmt->bindValue(':delivery_time', $data['delivery_time']);
    $stmt->bindValue(':price', $data['price']);
    $stmt->bindValue(':revision_included', $data['revisions']);
    $stmt->bindValue(':image1', "uploads/services/" . $serviceID . "/" . $data['image1']);
    $stmt->bindValue(':image2', "uploads/services/" . $serviceID . "/" . $data['image2']);
    $stmt->bindValue(':image3', "uploads/services/" . $serviceID . "/" . $data['image3']);
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':featured', $featured);
    $stmt->execute();

    // Clear session data
    unset($_SESSION['service_data']);
    unset($_SESSION['service_id']);

    // Redirect to service list or confirmation page
    header("Location: My-services.php?success=service_created");
    exit();
}


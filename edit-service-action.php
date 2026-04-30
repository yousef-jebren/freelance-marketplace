<?php 
session_start();
require_once 'dp.php';


if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit();
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

    if (!isset($_POST['service_id'])) {
    header('Location: My-services.php');
    exit();
}

$serviceID = $_POST['service_id'];

// validate data 

if (empty($serviceID)) {
    header("Location: edit-service.php?error=invalid_service");
    exit();
}

    $status = $_POST['status'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $delivery_time = $_POST['delivery_time'];
    $revision_included = $_POST['revisions'];
    $category = $_POST['category'];
    $subcategory = $_POST['subcategory'];
    $featured = isset($_POST['featured']) ? 'Yes' : 'No';

    if(empty($title) || empty($description) || empty($price) || empty($delivery_time) || empty($revision_included) || empty($category) || empty($subcategory) || !is_numeric($price) || !is_numeric($delivery_time) || !is_numeric($revision_included)) {
        header("Location: edit-service.php?error=please_fill_all_fields&service_id=$serviceID");
        exit();
    }
    if($status == "Inactive" ){
        $featured = "No";
    }

    $feturedCheck = "SELECT COUNT(*) FROM services WHERE freelancer_id = :user_id AND featured = 'Yes' AND service_id != :serviceID";

        $stmt = $conn->prepare($feturedCheck);
        $stmt->bindValue(':user_id', $_SESSION['user_id']);
        $stmt->bindValue(':serviceID', $serviceID);
        $stmt->execute();
        $featuredCount = $stmt->fetchColumn();

if ($featured === "Yes" && $featuredCount >= 3) {
    header("Location: edit-service.php?error=featured_limit_reached&service_id=$serviceID");
    exit();
}


   
       $imageName = "";

        
     if (isset($_FILES['image1']) && $_FILES['image1']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['image1']['name'], PATHINFO_EXTENSION));
        $imageName = $_FILES['image1']['name'] . "." . $ext;
        if (!is_dir('uploads/services/' . $serviceID)) {
            mkdir('uploads/services/' . $serviceID, 0755, true);
        }
        move_uploaded_file(
            $_FILES['image1']['tmp_name'],
            "uploads/services/" . $serviceID . "/" . $imageName
        );
        $imagePath = "uploads/services/" . $serviceID . "/" . $imageName;
        $updateImageSql = "UPDATE services SET image_1 = :imagePath WHERE service_id = :serviceID";
        $stmt = $conn->prepare($updateImageSql);
        $stmt->bindValue(':imagePath', $imagePath);
        $stmt->bindValue(':serviceID', $serviceID);
        $stmt->execute();
    } else {
// Update others data 
        $updateSql = "UPDATE services SET title = :title, description = :description, price = :price, delivery_time = :delivery_time, revisions_included = :revision_included, category = :category, subcategory = :subcategory, status = :status, featured = :featured WHERE service_id = :serviceID";
        $stmt = $conn->prepare($updateSql);
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':description', $description);
        $stmt->bindValue(':price', $price);
        $stmt->bindValue(':delivery_time', $delivery_time);
        $stmt->bindValue(':revision_included', $revision_included);
        $stmt->bindValue(':category', $category);
        $stmt->bindValue(':subcategory', $subcategory);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':featured', $featured);
        $stmt->bindValue(':serviceID', $serviceID);
        $stmt->execute();
    }

    // Redirect to My-services.php with success message
    header("Location: My-services.php?success=service_updated");
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch existing service data
    $serviceIDFROMGET = $_GET['service_id'];
    $serviceSql = "SELECT * FROM services WHERE service_id = :serviceID";
    $stmt = $conn->prepare($serviceSql);
    $stmt->bindValue(':serviceID', $serviceIDFROMGET);
    $stmt->execute();
    $service = $stmt->fetch();

     // Toggle status
    if(empty($serviceIDFROMGET)) {
        header("Location: My-services.php?error=invalid_service");
        exit();
    }
    if($service['status'] == "Active") {
    $sql = "UPDATE services SET status = 'Inactive', featured = 'No' WHERE service_id = :serviceID";
    } else {
        $sql = "UPDATE services SET status = 'Active' WHERE service_id = :serviceID";
    }
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':serviceID', $serviceIDFROMGET);
    $stmt->execute();

    header("Location: My-services.php?success=status_updated");
    exit();
    
}




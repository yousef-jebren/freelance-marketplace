<?php 
include 'header.php';
require_once 'dp.php';

$userID = null;
if(isset($_SESSION['user_id'])){
    $userID = $_SESSION['user_id'];
}

    

$sql = "";
$freelance = "select * FROM users WHERE user_id = :userID";
$stmt = $conn->prepare($freelance);
$stmt->bindValue(':userID', $userID);
$stmt->execute();
$user = $stmt->fetch();
if ($user && $user['role'] === 'Freelancer') {
    $sql = "SELECT * FROM services WHERE freelancer_id = :userID OR status = 'Active'";
} else {
    $sql = "SELECT * FROM services WHERE status = 'Active'";
}

    
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search_submit'])) {
    $searchTerm = trim($_GET['search'] ?? '');
    $category   = trim($_GET['category'] ?? '');
    $sortBy     = trim($_GET['sort'] ?? '');

    if ($searchTerm !== '') {
        $sql .= " AND (title LIKE :searchTerm OR description LIKE :searchTerm)";
    }

    if ($category !== '') {
        $sql .= " AND category = :category";
    }

    if ($sortBy !== '') {
        switch ($sortBy) {
            case 'price_asc':
                $sql .= " ORDER BY price ASC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY price DESC";
                break;
            case 'newest':
                $sql .= " ORDER BY created_date DESC";
                break;
            case 'oldest':
                $sql .= " ORDER BY created_date ASC";
                break;
        }
    }
}

$stmt = $conn->prepare($sql);

if (!empty($searchTerm)) {
    $stmt->bindValue(':searchTerm', "%$searchTerm%");
}

if (!empty($category)) {
    $stmt->bindValue(':category', $category);
}
if ($user && $user['role'] === 'Freelancer') {
    $stmt->bindValue(':userID', $userID);
}

$stmt->execute();
$services = $stmt->fetchAll();

$cats = "SELECT DISTINCT category FROM services WHERE status = 'Active'";
$stmt = $conn->prepare($cats);
$stmt->execute();
$categories = $stmt->fetchAll();


$recntlyViewedServices = [];

if (!isset($_COOKIE['recent_services'])) {
    return;
}

$serviceIds = array_filter(explode(',', $_COOKIE['recent_services']));
if (empty($serviceIds)) {
    return;
}

$placeholders = implode(',', array_fill(0, count($serviceIds), '?'));

if ($user && $user['role'] === 'Freelancer') {
    $recentSql = "
        SELECT * FROM services
        WHERE service_id IN ($placeholders)
        AND (status = 'Active' OR freelancer_id = ?)
        ORDER BY FIELD(service_id, $placeholders)
    ";
} else {
    $recentSql = "
        SELECT * FROM services
        WHERE service_id IN ($placeholders)
        AND status = 'Active'
        ORDER BY FIELD(service_id, $placeholders)
    ";
}

$recentStmt = $conn->prepare($recentSql);
$i = 1;

$serviceIds = array_reverse($serviceIds);

// IN (...)
foreach ($serviceIds as $id) {
    $recentStmt->bindValue($i++, $id, PDO::PARAM_INT);
}

// freelancer_id
if ($user && $user['role'] === 'Freelancer') {
    $recentStmt->bindValue($i++, $userID, PDO::PARAM_INT);
}


// FIELD(...)
foreach ($serviceIds as $id) {
    $recentStmt->bindValue($i++, $id, PDO::PARAM_INT);
}

$recentStmt->execute();
$recntlyViewedServices = $recentStmt->fetchAll();










?>

  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/cards.css">
        <h1 class="heading-primary" style="margin: 0;">Browse Services</h1>
            <section class="card">
                <form method="GET" action="browse-services.php" style="display: flex; gap: 200px; align-items: end; ">
                    <section class="form-group">
                    <label class="form-label" >Search</label>   
                    <input type="text" name="search" placeholder="Search for services..." style="width: 100%;  padding: 10px;  border: 1px solid #DEE2E6;border-radius: 4px;font-size: 14px;">
                    </section>
                    <section class="form-group">
                    <label class="form-label">Category:</label>
                    <select name="category" id="category" style="width: 100%;  padding: 10px;  border: 1px solid #DEE2E6;border-radius: 4px;font-size: 14px;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['category']); ?>"><?php echo htmlspecialchars($category['category']); ?></option>
                        <?php endforeach; ?>
                        
    
                    </select>
                        </section>
                        <section class="form-group">
                    <label class="form-label">Sort by:</label>
                    <select name="sort" id="sort" style="width: 100%;  padding: 10px;  border: 1px solid #DEE2E6;border-radius: 4px;font-size: 14px;">
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                        <option value="newest">Newest</option>
                        <option value="oldest">Oldest</option>
                    </select>
                        </section>
                       <section class="form-group"> 
                    <button type="submit" name="search_submit" class="btn btn-primary">Apply filter</button>
                    <?php if(isset($_GET['search_submit'])): ?>
                        <a href="browse-services.php" class="btn btn-secondary" style="margin-left: 10px;">Clear filter</a>
                    <?php endif; ?>
                          </section>
                </form>
                </section>

    <?php if (!empty($recntlyViewedServices)): ?>
        <h2>Recently Viewed Services</h2>
        <section class="cards">
            <?php foreach ($recntlyViewedServices as $service): ?>
                <section class="card-service">
                     <?php if(isset($service['featured']) && $service['featured'] == 'Yes'): ?>
                <span class="featured-badge">Featured</span>
            <?php endif; ?>
                <a href="service-detalis.php?service_id=<?php echo $service['service_id']; ?>">
                    <img src="<?php echo htmlspecialchars($service['image_1']); ?>" alt="Service Image" >
                </a>    
                    <section class="card-content">
                    <h3 class="card-title"><a href="service-detalis.php?service_id=<?php echo $service['service_id']; ?>"><?php echo htmlspecialchars($service['title']); ?></a></h3>
                    <article class="freelancer-info">
                    <?php
                            $freelancerName = "Select * FROM users WHERE user_id = :freelancerID";
                                $stmt = $conn->prepare($freelancerName);
                                $stmt->bindValue(':freelancerID', $service['freelancer_id']);
                                $stmt->execute();
                                $nameResult = $stmt->fetch();
                    ?>
                        <?php $src = "uploads/profiles/" . htmlspecialchars($service['freelancer_id']) . "/" . htmlspecialchars($nameResult['profile_photo'] ?? " "); ?>
                        <img src="<?php echo $src; ?>" alt="Freelancer Icon" >
                        <span>By Freelancer : <?php
                            if ($nameResult) {
                                echo htmlspecialchars($nameResult['first_name'] . ' ' . $nameResult['last_name']);
                            } else {
                                echo "Unknown";
                            }
                         ?></span>
                        </article>
                    <p class="card-category"><?php echo htmlspecialchars($service['category']); ?></p>
                    <p class="card-price">$<?php echo number_format($service['price'], 2); ?></p>
                    </section>
                </section>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <h2> All Services</h2>
    <section class="cards">
        <?php foreach ($services as $service): ?>
            <section class="card-service">
                 <?php if(isset($service['featured']) && $service['featured'] == 'Yes'): ?>
            <span class="featured-badge">Featured</span>
        <?php endif; ?>
            <a href="service-detalis.php?service_id=<?php echo $service['service_id']; ?>">
                <img src="<?php echo htmlspecialchars($service['image_1']); ?>" alt="Service Image" >
            </a>    
                <section class="card-content">
                <h3 class="card-title"><a href="service-detalis.php?service_id=<?php echo $service['service_id']; ?>"><?php echo htmlspecialchars($service['title']); ?></a></h3>
                <article class="freelancer-info">
                <?php
                     $freelancerName = "Select * FROM users WHERE user_id = :freelancerID";
                        $stmt = $conn->prepare($freelancerName);
                        $stmt->bindValue(':freelancerID', $service['freelancer_id']);
                        $stmt->execute();
                        $nameResult = $stmt->fetch();
                ?>
                    <?php $src = "uploads/profiles/" . htmlspecialchars($service['freelancer_id']) . "/" . htmlspecialchars($nameResult['profile_photo'] ?? " "); ?>
                    <img src="<?php echo $src; ?>" alt="Freelancer Icon" >
                    <span>By Freelancer : <?php
                        if ($nameResult) {
                            echo htmlspecialchars($nameResult['first_name'] . ' ' . $nameResult['last_name']);
                        } else {
                            echo "Unknown";
                        }
                     ?></span>
                    </article>
                <p class="card-category"><?php echo htmlspecialchars($service['category']); ?></p>
                <p class="card-price">$<?php echo number_format($service['price'], 2); ?></p>
                </section>
                
        </section>
       
        <?php endforeach; ?>
    </section>

    <?php include 'footer.php'; ?>


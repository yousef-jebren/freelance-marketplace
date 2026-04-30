<?php
require_once 'header.php';
require_once 'dp.php';

// Get featured services
$featuredStmt = $conn->prepare("
    SELECT s.*, u.first_name as freelancerF_name, u.last_name as freelancerL_name 
    FROM services s 
    JOIN users u ON s.freelancer_id = u.user_id 
    WHERE s.status = 'Active' AND s.featured = 'Yes' 
    ORDER BY s.created_date DESC 
    LIMIT 6
");
$featuredStmt->execute();
$featuredServices = $featuredStmt->fetchAll();

// Get recent services
$recentStmt = $conn->prepare("
    SELECT s.*, u.first_name as freelancerF_name, u.last_name as freelancerL_name 
    FROM services s 
    JOIN users u ON s.freelancer_id = u.user_id 
    WHERE s.status = 'Active' 
    ORDER BY s.created_date DESC 
    LIMIT 9
");
$recentStmt->execute();
$recentServices = $recentStmt->fetchAll();
?>
 
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/cards.css">
  
  <h1 class="heading-primary text-center">Welcome to Freelance Marketplace</h1>
    <p class="text-center" style="font-size: 18px; color: #6C757D; margin-bottom: 40px;">
        Connect with talented freelancers or offer your services to clients worldwide
    </p>

        <h2> Featured Services</h2>
    <section class="cards">
        <?php foreach ($featuredServices as $service): ?>
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


    <h2 class="heading-secondary" style="margin-top: 60px;">Recent Services</h2>
        <section class="cards">
            <?php foreach ($recentServices as $service): ?>
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
<section style="text-align: center; margin-top: 40px;">
        <a href="browse-services.php" class="btn btn-primary" style="font-size: 18px; padding: 15px 40px;">
            Browse All Services
        </a>
    </section>

    
<?php require_once 'footer.php'; ?>
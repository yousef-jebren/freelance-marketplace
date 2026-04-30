<?php
include 'Service.php';
include 'header.php';
require_once 'dp.php';

$userID = $_SESSION['user_id'];
if(!$userID){
    header("Location: login.php");
    exit();
}
$userSql = "select * from users where user_id = :userID";
$stmt = $conn->prepare($userSql);
$stmt->bindvalue(':userID', $userID);
$stmt->execute();
$user = $stmt->fetch();

if(!$user){
    header("Location: login.php");
    exit();
}

if($user['role'] !== 'Client'){
    header("Location: login.php?error=please-login-as-Client");
    exit();
}

  $subTotal = 0;
 $serviceInCart = $_SESSION['cart'] ?? [];   

             foreach($serviceInCart as $service): 
                $subTotal += $service->getPrice();
             endforeach;  


         $recentServices = [];

if (isset($_COOKIE['recent_services'])) {
    $ids = explode(',', $_COOKIE['recent_services']);
    $ids = array_slice(array_reverse($ids), 0, 4); // آخر 4

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $sql = "
        SELECT * FROM services 
        WHERE service_id IN ($placeholders)
        AND status = 'Active'
    ";

    $stmt = $conn->prepare($sql);

    foreach ($ids as $i => $id) {
        $stmt->bindValue($i + 1, $id);
    }

    $stmt->execute();
    $recentServices = $stmt->fetchAll();
}
    
        


?>

<!DOCTYPE html>
 <html>
    <head>
          <link rel="stylesheet" href="css/style.css">
          <link rel="stylesheet" href="css/cart.css">
          <link rel="stylesheet" href="css/cards.css">
</head>
<body>
    <h1> Shopping cart </h1>
    <?php if(isset($_GET['error'])): ?>
        <p class="error" >Error: <?php echo $_GET['error'] ?> </p>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['warnings'])): ?>
        <p class="warnings"> <?php echo $_SESSION['warnings'] ?> </p>
    <?php endif; ?>        

    <?php if (empty($serviceInCart)): ?>
    <section class="empty-cart">
        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#6C757D" stroke-width="2" style="margin-bottom: 20px;">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
        <h2 style="font-size: 24px">Your cart is empty</h2>

        <a href="browse-services.php" class="btn btn-primary">
            Browse Services
        </a>
    </section>
            <?php if (!empty($recentServices)): ?>
    <section class="recently-viewed">
        <h3>Recently Viewed Services</h3>

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
    </section>
<?php endif; ?>

    <?php endif; ?>
    <?php if(!empty($serviceInCart)): ?>
    <section class="cart-contanier">
        <section class="left-cont">
            <h2> Main area </h2>
          <table>
             <thead>
                    <tr>
                        <th>Image</th>
                        <th>Service Title</th>
                        <th>Freelancer</th>
                        <th>Category</th>
                        <th>Delivery time</th>
                        <th>Revision</th>
                        <th>Price</th>
                        <th>Remove</th>
                    </tr>
                </thead>
            <tbody>
                <?php foreach ($serviceInCart as $service): ?>
                    <tr>
                            <td>
                                <img src="<?php echo $service->getImage1(); ?>" 
                                     style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px;">
                            </td>
                            <td>
                                    <a href="service-detalis.php?service_id=<?php echo $service->getServiceId(); ?>"><?php echo htmlspecialchars($service->getTitle()); ?></a>
                            </td>
                            <td> <a href="My-services.php?user_id=<?php echo $service->getFreelancerId(); ?>" ><?php echo $service->getFreelancerName(); ?> </a> </td>
                            <td><?php echo htmlspecialchars($service->getCategory()); ?></td>
                             <td>
                                <?php echo htmlspecialchars($service->getDeliveryTime()); ?> days
                            </td>
                            <td>
                                <?php if ($service->getRevisionsIncluded() === 999): ?>
                                    Unlimited
                                <?php else: ?>
                                    <?php echo htmlspecialchars($service->getRevisionsIncluded()); ?>
                                <?php endif; ?>
                            </td>

                            <td style="font-weight: bold; color: #28A745;">$<?php echo $service->getFormattedPrice(); ?></td>
                           
                            <td>
                                <a href="remove-service.php?service_id=<?php echo $service->getServiceId(); ?>" class="btn btn-danger">Remove</a>
                            </td>
                        </tr> 

                    <?php endforeach; ?>
                </tbody>
             </table>

    </section>

    <aside class="right-cont">
        <h2> Order Summary </h2>
        <hr/>
        <p> sub total: <?php echo $subTotal;  ?> </p>
        <p> Service Fee(5%) : <?php echo 0.05 * $subTotal; ?> </p>
        <hr/>
        <p style="font: bold;"><strong> Total: <?php echo $subTotal + ($subTotal * 0.05); ?></strong> </p>
        <br/>
        <a href="checkout.php" style="width: 100%;" class="btn btn-primary" > Procces Order </a>

    </aside>

</section>
<?php endif; ?>
</body>
</html>
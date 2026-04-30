<?php
include 'header.php';
require_once 'dp.php';



 $ServiceID = $_GET['service_id'];
 if(!isset($ServiceID)){
    header("Location: browse-services.php");
    exit();
    }
    $user = null;
    if(isset($_SESSION['user_id'])){
        $userID = $_SESSION['user_id'];
        $userSql = "SELECT * FROM users WHERE user_id = :userID";
        $userStmt = $conn->prepare($userSql);
        $userStmt->bindValue(':userID', $userID);
        $userStmt->execute();
        $user = $userStmt->fetch();
    } else {
        $userID = null;
    }
    $sql = "SELECT * FROM services WHERE service_id = :serviceID";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':serviceID', $ServiceID);
    $stmt->execute();
    $service = $stmt->fetch();
    if (!$service) {
        header("Location: browse-services.php");
        exit();
    }
    

    $freelancerID = $service['freelancer_id'];
    $userSql = "SELECT * FROM users WHERE user_id = :freelancerID";
    $userStmt = $conn->prepare($userSql);
    $userStmt->bindValue(':freelancerID', $freelancerID);
    $userStmt->execute();
    $freelancer = $userStmt->fetch();


    $currentServiceId = $ServiceID; // أو $service['service_id']

    if (isset($_COOKIE['recent_services'])) {

        $services = explode(',', $_COOKIE['recent_services']);

        $services = array_diff($services, [$currentServiceId]);

    } else {
         $services = [];
    }

        $services[] = $currentServiceId;

    if (count($services) > 4) {
        array_shift($services);
    }

    $cookieValue = implode(',', $services);

        setcookie(
            'recent_services',
            $cookieValue,
            time() + (30 * 24 * 60 * 60),'/'
);


    
    ?>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/serviceDeatils.css">
    <?php if (
    $service['status'] === 'Inactive' &&
    $user['role'] === 'Freelancer' &&
    $service['freelancer_id'] == $userID
): ?>
    <p class="inactive-notice">
         This service is currently inactive and not visible to clients.
    </p>
<?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <p class="error">
            Error: <?php echo htmlspecialchars($_GET['error']); ?>
        </p>
    <?php endif; ?>
    <?php if (isset($_GET['success'])): ?>
        <p class="success">
            Success: <?php echo htmlspecialchars($_GET['success']); ?>
        </p>
        <?php endif; ?>
    <section class="service-details">
        <section class="left-panel">
            <section class="gallery">

    <section class="main-images">
        <img id="img1" src="<?= htmlspecialchars($service['image_1'] ?: " ") ?>" class="image">
    </section>

    <section class="thumbnails">
        <?php if($service['image_1']): ?>
            <a href="#img1"><img src="<?= htmlspecialchars($service['image_1']) ?>"></a>
        <?php endif; ?>

        <?php if($service['image_2']): ?>
            <a href="#img2"><img src="<?= htmlspecialchars($service['image_2']) ?>"></a>
        <?php endif; ?>

        <?php if($service['image_3']): ?>
            <a href="#img3"><img src="<?= htmlspecialchars($service['image_3']) ?>"></a>
        <?php endif; ?>
    </section>

</section>



            <section class="service-info">
                <h2 style="font-size: 28px; margin-bottom: 15px;"><?php echo htmlspecialchars($service['title']); ?></h2>
                <p class="category-badge"><strong>Category:</strong> <?php echo htmlspecialchars($service['category']); ?> &gt; <?php echo htmlspecialchars($service['subcategory']); ?></p>
                <h3>Freelancer Information</h3>
                <section class="freelancer-info">
                        <img src="uploads/profiles/<?php echo htmlspecialchars($service['freelancer_id']); ?>/<?php echo htmlspecialchars($freelancer['profile_photo'] ? $freelancer['profile_photo'] : ""); ?>" alt="Freelancer Profile Picture" class="freelancer-profile-pic">
                        <article class="freelancer-details"> 
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($freelancer['first_name'] . ' ' . $freelancer['last_name']); ?></p>
                        <p style="color:gray"><small>Member Since:</small> <?php echo date('F Y', strtotime($freelancer['registration_date'])); ?></p>
                        <a href="profile.php?user_id=<?php echo htmlspecialchars($service['freelancer_id']); ?>" >View Profile</a>
                        </article>
                </section>
                <section class="service-description">
                    <h3>About This Service</h3>
                    <p><?php echo htmlspecialchars($service['description']); ?></p>
                </section>
                </section>
        </section>
        <section class="right-panel">
                <h3 style="color: gray;"><small>starting at</small></h3>
                <p class="service-price">$<?php echo number_format($service['price'], 2); ?></p>
                <p class="service-delivery-time"><strong>Delivery Time:</strong> <?php echo htmlspecialchars($service['delivery_time']); ?> days</p>
                <p class="service-revision-limit"><strong>Revision:</strong> <?php echo htmlspecialchars($service['revisions_included']); ?></p>
                <?php if ($user && $service['freelancer_id'] !== $user['user_id']): ?>
                    <article style="display: flex; gap: 10px; flex-direction: column;">
                    <a href="add-to-cart.php?service_id=<?php echo $service['service_id']; ?>"  class="btn btn-primary">Add to cart</a>
                    <a href="add-to-cart.php?id=<?php echo $service['service_id']; ?>&order_now=1" class="btn btn-success" >
                                Order Now
                            </a>                   
                         </article>
                <?php endif; ?>
                <?php if (!$user): ?>
                    <p><a href="login.php" class="btn btn-primary">login to order</a></p>
                <?php endif; ?>
                <?php if ($user && $user['role'] === 'Freelancer' && $user['user_id'] === $freelancerID): ?>
                    <a href="edit-service.php?service_id=<?php echo $service['service_id']; ?>" class="btn btn-primary" >Edit Service</a>
                <?php endif; ?>
            </section>

            <?php include 'footer.php';

            

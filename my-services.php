<?php
  require_once 'dp.php';
  include 'header.php'; 

    $user_id = $_SESSION['user_id'];
   
    if (!isset($user_id)) {
        header('Location: login.php');
        exit();
    }

  $sql = "SELECT * FROM services Where freelancer_id = :user_id";
  $stmt = $conn->prepare($sql);
  $stmt->bindValue(':user_id', $user_id);
  $stmt->execute();
  $services = $stmt->fetchAll();

  $s1 = "SELECT * FROM services Where freelancer_id = :user_id AND status = 'Active'";
    $stmt1 = $conn->prepare($s1);
    $stmt1->bindValue(':user_id', $user_id);
    $stmt1->execute();
    $activeCount = $stmt1->rowCount();

    $s2 = "SELECT * FROM services Where freelancer_id = :user_id AND Featured = 'Yes'";
    $stmt2 = $conn->prepare($s2);
    $stmt2->bindValue(':user_id', $user_id);
    $stmt2->execute();
    $featuredCount = $stmt2->rowCount();

    $s3 = "SELECT * FROM orders Where freelancer_id = :user_id AND status = 'Completed'";
    $stmt3 = $conn->prepare($s3);
    $stmt3->bindValue(':user_id', $user_id);
    $stmt3->execute();
    $completedOrders = $stmt3->fetchAll();

?>

<!DOCTYPE html>
<html>
<head>
  <link rel="stylesheet" href="css/style.css">
  <title>Main Page</title>
</head>
<body>

    <section style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 class="heading-primary" style="margin: 0;">My Services</h1>
        <?php if (isset($_GET['success'])) {
            echo '<p class="success">Success: ' . htmlspecialchars($_GET['success']) . '</p>';
        } ?>
        <?php if(isset($_GET['error'])) {
            echo '<p class="error">Error: ' . htmlspecialchars($_GET['error']) . '</p>';
        } ?>
        <a href="create-service.php" class="btn btn-success">Create New Service</a>
    </section>

    <section class="card" style="margin-bottom: 30px;">
        <section class="stats-container" style="grid-template-columns: repeat(4, 1fr);">
            <section class="stat-card">
                <section class="stat-number"><?php echo count($services); ?></section>
                <section class="stat-label">Total Services</section>
            </section>
            <section class="stat-card">
                <section class="stat-label">Active Services</section>
                <section class="stat-number" style="color: #28A745;"><?php echo $activeCount; ?></section>
            </section>
            <section class="stat-card">
                <section class="stat-number" style="color: <?php echo $featuredCount >= 3 ? '#FFD700' : '#007BFF'; ?>;">
                    <?php echo $featuredCount; ?>/3
                </section>
                <section class="stat-label">Featured Services</section>
                
            </section>
            <section class="stat-card">
                <section class="stat-number" style="color: #17A2B8;"><?php echo count($completedOrders); ?></section>
                <section class="stat-label">Completed Orders</section>
            </section>
        </section>
    </section>

 <section class="card">


            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Service Title</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Created Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <td>
                                <img src="<?php echo $service['image_1']; ?>" 
                                     style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px;">
                            </td>
                            <td>
                                    <a href="service-details.php?service_id=<?php echo $service['service_id']; ?>"><?php echo htmlspecialchars($service['title']); ?></a>
                            </td>
                            <td><?php echo htmlspecialchars($service['category']); ?></td>
                            <td style="font-weight: bold; color: #28A745;">$<?php echo number_format($service['price'], 2); ?></td>
                            <td>
                                <span class="badge <?php echo $service['status']; ?>">
                                    <?php echo $service['status']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($service['featured'] === 'Yes'): ?>
                                    <span class="featured-indicator">★ Featured</span>
                                <?php else: ?>
                                    <span style="color: #6C757D;">No</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $service['created_date']; ?></td>

                            <td>
                                <a href="edit-service.php?service_id=<?php echo $service['service_id']; ?>" class="btn btn-primary">Edit</a>
                                <a href="edit-service-action.php?service_id=<?php echo $service['service_id']; ?>" class="btn btn-primary" ><?php if ($service['status'] === 'Active') { echo 'Deactivate'; } else { echo 'Activate'; } ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php include 'footer.php'; ?>



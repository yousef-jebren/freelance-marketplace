<?php
require_once "dp.php";
include 'header.php'; 

/* ---------- AUTH CHECK ---------- */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];



/* ---------- FETCH USER ---------- */
$stmt = $conn->prepare(
    "SELECT
        user_id,
        first_name,
        last_name,
        email,
        phone_number,
        country,
        city,
        role,
        bio,
        profile_photo,
        registration_date
     FROM users
     WHERE user_id = :id"
);
$stmt->bindValue(':id', $userId, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: login.php");
    exit;
}

$isFreelancer = ($user['role'] === "Freelancer");

/* ---------- FREELANCER STATISTICS ---------- */
if ($isFreelancer) {

    $s1 = "SELECT COUNT(*) FROM services WHERE freelancer_id = :id";
    $stmt = $conn->prepare($s1);
    $stmt->bindValue(':id', $userId);
    $stmt->execute();
    $totalServices = $stmt->fetchColumn();

    $s2 = "SELECT COUNT(*) FROM services
           WHERE freelancer_id = :id AND status = 'Active'";
    $stmt = $conn->prepare($s2);
    $stmt->bindValue(':id', $userId);
    $stmt->execute();
    $activeServices = $stmt->fetchColumn();

    $s3 = "SELECT COUNT(*) FROM orders
           WHERE freelancer_id = :id AND status = 'Completed'";
    $stmt = $conn->prepare($s3);
    $stmt->bindValue(':id', $userId);
    $stmt->execute();
    $completedOrders = $stmt->fetchColumn();

    $s4 = "SELECT COUNT(*) FROM services
           WHERE featured = 'Yes' AND freelancer_id = :id";
    $stmt = $conn->prepare($s4);
    $stmt->bindValue(':id', $userId);
    $stmt->execute();
    $featuredServices = $stmt->fetchColumn();
}
?>
<section class="container">
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="css/profilePage.css">
    <h1 class="heading-primary">My Profile</h1>
    <?php if(isset($_GET['error'])): ?>
        <h2 class="error"> <?php echo $_GET['error'] ?> </h2>
    <?php endif ?>    
    <?php if(isset($_GET['success'])): ?>
        <h2 class="success"> <?php echo $_GET['success'] ?> </h2>
    <?php endif ?> 
    <section class="profile-layout">
        <!-- Left Column -->
        <section class="profile-left">
            <section class="profile-card-full">
                <img src="uploads/profiles/<?php echo $user['user_id']; ?>/<?php echo $user['profile_photo']; ?>" class="profile-photo-large" alt="Profile">
                <form method="POST" action="profileAction.php" enctype="multipart/form-data" style="display : flex; padding: 5px;">
                     <input type="file" name="image" class="form-input" accept=".jpg,.jpeg,.png">
                     <button type="submit" class=" btn btn-secondry" name="just-image">Upload</button>
                </form>

                <h2 style="margin-bottom: 5px;"><?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>
                <span class="role-badge role-badge-<?php echo strtolower($user['role']); ?>">
                    <?php echo $user['role']; ?>
                </span>
                <p style="color: #6C757D; font-size: 14px;"><?php echo htmlspecialchars($user['email']); ?></p>
                <p style="color: #6C757D; font-size: 13px; margin-top: 15px;">
                    Member since <?php echo date('M Y', strtotime($user['registration_date'])); ?>
                </p>
            </section>
            
            <?php if ($_SESSION['role'] === "Freelancer"): ?>
                <section class="card">
                    <h3 class="heading-tertiary">Statistics</h3>
                    <section class="statss-container">
                        <section class="stat-card">
                            <section  class="stat-value"><?php echo $totalServices; ?></section>
                            <section class="stat-label">Total Services</section>
                        </section>
                        <section class="stat-card">
                            <section style="color: #28A745;" class="stat-value"><?php echo $activeServices; ?></section>
                            <section class="stat-label">Active Services</section>
                        </section>
                      
                        <section class="stat-card">
                            <section class="stat-value"><?php echo $completedOrders; ?></section>
                            <section class="stat-label">Completed Orders</section>
                        </section>

                        <section class="stat-card">
                            <section <?php if($featuredServices < 3) echo 'style="color: #FFD700;"'; ?> class="stat-value"><?php echo $featuredServices; ?>/3</section>
                            <section class="stat-label">featured services</section>
                        </section>
                    </section>
                </section>
            <?php endif; ?>
        </section>
        
        <!-- Right Column -->
        <section class="profile-right">
            <section class="card">
                <h2 class="heading-secondary">Edit Profile</h2>
                <?php if (isset($_GET['error'])): ?>
                    <p class="error-message">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </p>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" action="profileAction.php">
                    <h3 class="heading-tertiary">Account Information</h3>
                    
                    <section class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </section>
                    
                    <section class="form-group">
                        <label class="form-label">Current Password (required to change password)</label>
                        <input type="password" name="current_password" class="form-input">
                       
                    </section>
                    
                    <section class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-input">
                        
                    </section>
                    
                    <section class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-input">
                    </section>
                    
                    <h3 class="heading-tertiary" style="margin-top: 30px;">Personal Information</h3>
                    
                    <section class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" class="form-input" 
                               value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                      
                    </section>

                     <section class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" class="form-input" 
                               value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                      
                    </section>
                    
                    <section class="form-group">
                        <label class="form-label">Phone Number *</label>
                        <input type="tel" name="phone_number" class="form-input" 
                               value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
                    </section>
                    
                     <section class="form-group">
                        <label class="form-label">Country *</label>
                        <input type="text" name="country" class="form-input" 
                               value="<?php echo htmlspecialchars($user['country']); ?>" required>
                        
                    </section>
                    
                    
                    <section class="form-group">
                        <label class="form-label">City *</label>
                        <input type="text" name="city" class="form-input " 
                               value="<?php echo htmlspecialchars($user['city']); ?>" required>
                        
                    </section>
                    
                    <section class="form-group">
                        <label class="form-label">Profile Photo </label>
                        <input type="file" name="profile_photo" class="form-input" accept=".jpg,.jpeg,.png">
                        
                    </section>
                            
                        <section class="form-group">
                            <label class="form-label">Bio / About </label>
                            <textarea name="bio" class="form-input" rows="4"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                        </section>

                        <?php if ($isFreelancer): ?>
                            
                            <section class="form-group">
                                <label class="form-label">Skills (comma separated)</label>
                                <input type="text" name="skills" class="form-input" >
                                </section>
                            <section class="form-group
                                <label class="form-label"> Professional Title </label>
                                <input type="text" name="title" class="form-input" >
                            </section>

                            <section class="form-group">
                                <label class="form-label"> Year of Experience </label>
                                <input type="number" name="experience" class="form-input" min="0" max="50" >
                            </section>
                        <?php endif; ?>


                    
                    <section class="form-actions">
                        <button type="submit" class="btn btn-primary" name="save-changes" style="flex: 1;">Save Changes</button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </section>
                </form>
            </section>
        </section>
    </section>
</section>

<?php include 'footer.php'; ?>





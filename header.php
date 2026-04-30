<?php
session_start();
require_once 'dp.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Freelance Marketplace</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <section class="wrapper">
        <header class="header">
            <section class="header-logo">
                <a href="main.php" style="color: #007BFF; text-decoration: none;">Freelance Marketplace
                </a>
            </section>
            
            <section class="header-search">
                <form action="browse-services.php" method="GET">
                    <input type="text" name="search" placeholder="Search services..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </form>
            </section>
            
            <section class="header-auth">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="profile-card <?php echo htmlspecialchars($_SESSION['role'] ?? ''); ?>">
                        <img src="uploads/profiles/<?php echo $_SESSION['user_id']; ?>/<?php echo $_SESSION['profile_photo'] ?? ''; ?>" alt="Profile">
                        <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    </a>
                
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Client'): ?>
                        <a href="cart.php" class="cart-icon ">
                              <svg class="cart-icon" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#6C757D" stroke-width="2" style="margin-bottom: 20px; ">
                                 <circle cx="9" cy="21" r="1"></circle>
                                 <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            <?php if (isset($_SESSION['cart'])): ?>
                                <span class="badge-warning"><?php echo count($_SESSION['cart']); ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                    
                    
                    <a href="logout.php" class="btn btn-secondary">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">Login</a>
                    <a href="register.php" class="btn btn-secondary">Sign Up</a>
                <?php endif; ?>
            </section>
        </header>
        
        <section class="content-wrapper">
            <nav class="navigation">
                <?php
                $currentPage = basename($_SERVER['PHP_SELF']);
                
                
                if (isset($_SESSION['user_id'])) {
                    if (isset($_SESSION['role']) && $_SESSION['role'] === 'Client') {
                        $navLinks = [
                            'main.php' => 'Home',
                            'browse-services.php' => 'Browse Services',
                            'cart.php' => 'Shopping Cart',
                            'my-orders.php' => 'My Orders',
                            'profile.php' => 'My Profile'
                        ];
                    } else {
                        $navLinks = [
                            'main.php' => 'Home',
                            'browse-services.php' => 'Browse Services',
                            'my-services.php' => 'My Services',
                            'my-orders.php' => 'My Orders',
                            'profile.php' => 'My Profile'
                        ];
                    }
                } else {
                    $navLinks = [
                        'main.php' => 'Home',
                        'browse-services.php' => 'Browse Services',
                        'login.php' => 'Login',
                        'register.php' => 'Sign Up'
                    ];
                }
                
                foreach ($navLinks as $page => $label) {
                    $activeClass = ($currentPage === $page) ? 'nav-link-active' : '';
                    echo "<a href='$page' class='nav-link $activeClass'>$label</a>";
                }
                ?>
            </nav>
            
            <main class="main-content">
                <?php
                // Display session messages
                     
if (isset($_SESSION['success'])) {
    echo "<p class='success' >" . $_SESSION['success'] . "</p>";
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo "<p class='error'>" . $_SESSION['error'] . "</p>";
    unset($_SESSION['error']);
}

if (isset($_SESSION['warning'])) {
    echo "<p class='warning'>" . $_SESSION['warning'] . "</p>";
    unset($_SESSION['warning']);
}
?>
                
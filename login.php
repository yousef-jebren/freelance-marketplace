<?php
require_once 'header.php';
?>
        <link rel="stylesheet" href="css/style.css">
    <section class="form-container">
    <h1 class="heading-primary">Login to Your Account</h1>
    <?php if (isset($_GET['success'])): ?>
        <p style="color: green; text-align: center;">
            Registration successful! Please log in.
        </p>
        <?php elseif (isset($_GET['error'])): ?>
            <p style="color: red; text-align: center;">
                <?php echo $_GET['error']; ?>
            </p>
        <?php endif; ?>

    <form method="POST" action="login_action.php">
        <section class="form-group">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-input "  required>
           
        </section>
        
        <section class="form-group">
            <label class="form-label">Password *</label>
            <input type="password" name="password" class="form-input " required>
            
        </section>
        
        <section class="form-group">
            <label>
                <input type="checkbox" name="remember_me">
                Remember Me
            </label>
        </section>
        
        <section class="form-actions">
            <button type="submit" class="btn btn-primary" style="flex: 1;">Login</button>
        </section>
    </form>

    <?php if (isset($_GET['error'])): ?>
        <p class="error-message" style="text-align: center; color: red; margin-top: 10px;">
            Invalid email or password.
        </p>
    <?php endif; ?>
     
    <p style="text-align: center; margin-top: 20px;">
        <a href="#">Forgot password?</a>
    </p>
    
    <p style="text-align: center; margin-top: 10px;">
        Don't have an account? <a href="register.php">Sign up</a>
    </p>
</section>

<?php require_once 'footer.php'; ?>
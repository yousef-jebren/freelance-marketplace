<?php require_once 'header.php'; ?>
<section class="form-container">
        <link rel="stylesheet" href="css/style.css">

    <h1 class="heading-primary">Create Your Account</h1>
    
    <form method="POST" action="register_action.php">
        <?php if (isset($_GET['error'])): ?>
            <p style="color: red; text-align: center;"><?php echo $_GET['error']; ?></p>
        <?php endif; ?>
        <h2 class="heading-secondary">Personal Information</h2>
        
            <section class="form-group">
                <label class="form-label">First Name *</label>
                <input type="text" name="first_name" class="form-input" required>
            </section>
        
            <section class="form-group">
                <label class="form-label">Last Name *</label>
                <input type="text" name="last_name" class="form-input" required>
            </section>

            <section class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-input" required>
            </section>
        
            <section class="form-group">
                <label class="form-label">Phone Number *</label>
                <input type="tel" name="phone_number" class="form-input" placeholder="10 digits" required>
            </section>
        
        <section class="form-group">
            <label class="form-label">City *</label>
            <input type="text" name="city" class="form-input" required>
        </section>
        
        <h2 class="heading-secondary">Account Security</h2>
        
        <section class="form-group">
            <label class="form-label">Password *</label>
            <input type="password" name="password" class="form-input" required>
        </section>
        
        <section class="form-group">
            <label class="form-label">Confirm Password *</label>
            <input type="password" name="confirm_password" class="form-input" required>
        </section>
        
        <h2 class="heading-secondary">Account Type</h2>
        
        <section class="form-group">
            <label class="form-label">I want to *</label>
            <section>
                <label style="margin-right: 20px;">
                    <input type="radio" name="role" value="Client" required>
                    Hire freelancers (Client)
                </label>
                <label>
                    <input type="radio" name="role" value="Freelancer" required>
                    Offer services (Freelancer)
                </label>
            </section>
           
        </section>
        
        <section class="form-group">
            <label class="form-label"> Bio / About (Optional for client)</label>
            <textarea name="bio" class="form-input" rows="4" maxlength="500"></textarea>
           
        </section>
        
        <section class="form-group">
            <label>
                <input type="checkbox" name="age_verification" required>
                I am 18+ years old *
            </label>
           
        </section>
        
        <section class="form-actions">
            <button type="submit" class="btn btn-primary" style="flex: 1;">Create Account</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </section>
    </form>
    
    <p style="text-align: center; margin-top: 20px;">
        Already have an account? <a href="login.php">Login here</a>
    </p>
</section>

<?php require_once 'footer.php'; ?>
<?php
include 'header.php';
$user_id = $_SESSION['user_id'];
$service_id = $_GET['service_id'];
if (!isset($service_id)) {
    header('Location: my-services.php');
    exit();
}
if (!isset($user_id)) {
    header('Location: login.php');
    exit();
}

    $sql = "SELECT * FROM services WHERE service_id = :service_id AND freelancer_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':service_id', $service_id);
    $stmt->bindValue(':user_id', $user_id);
    $stmt->execute();
    $service = $stmt->fetch();
    if (!$service) {
        header('Location: my-services.php');
        exit();
}


?>
    <link rel="stylesheet" href="css/style.css">
    <h1 class="heading-primary">Update Service</h1>
    
    <section class="form-container" style="max-width: 800px;">
            <form method="POST" action="edit-service-action.php" enctype="multipart/form-data">
                <h2 class="heading-secondary">Basic Information</h2>
                <?php if (isset($_GET['error'])): ?>
                     <p class="error">
                     <?php echo $_GET['error'];  ?>
                     </p>
                <?php endif; ?>
                <?php if (isset($_GET['success'])): ?>
                     <p class="success">
                     <?php echo $_GET['success'];  ?>
                     </p>
                <?php endif; ?>

                <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>">
                
                <section class="form-group">
                    <label class="form-label">Service Title *</label>
                    <input type="text" name="title" class="form-input" value="<?php echo $service['title']; ?>" required>
                </section>
                
                <section class="form-group">
                    <label class="form-label">Category *</label>
                    <select name="category" class="form-select" value="<?php echo $service['category']; ?>" required>
                        <option value="">Select Category</option>
                        <option value="Graphics & Design" <?php if ($service['category'] == "Graphics & Design") echo "selected"; ?>>Graphics & Design</option>
                        <option value="Digital Marketing" <?php if ($service['category'] == "Digital Marketing") echo "selected"; ?>>Digital Marketing</option>
                        <option value="Writing & Translation" <?php if ($service['category'] == "Writing & Translation") echo "selected"; ?>>Writing & Translation</option>
                        <option value="Video & Animation" <?php if ($service['category'] == "Video & Animation") echo "selected"; ?>>Video & Animation</option>
                        <option value="Music & Audio" <?php if ($service['category'] == "Music & Audio") echo "selected"; ?>>Music & Audio</option>
                        <option value="Web Development" <?php if ($service['category'] == "Web Development") echo "selected"; ?>>Web Development</option>
                        <option value="Business Consulting" <?php if ($service['category'] == "Business Consulting") echo "selected"; ?>>Business Consulting</option>
                        <option value="Tutoring & Education" <?php if ($service['category'] == "Tutoring & Education") echo "selected"; ?>>Tutoring & Education</option>
                    </select>
                </section>
                
                <section class="form-group">
                    <label class="form-label">Subcategory *</label>
                    <select name="subcategory" class="form-select" value="<?php echo $service['subcategory']; ?>" required>
                        <option value="">Select Subcategory</option>
                        <optgroup label="Web Development"> 
                            <option value="Frontend Development" <?php if ($service['subcategory'] == "Frontend Development") echo "selected"; ?>>Frontend Development</option> 
                            <option value="Backend Development" <?php if ($service['subcategory'] == "Backend Development") echo "selected"; ?>>Backend Development</option> 
                            <option value="Full Stack Development" <?php if ($service['subcategory'] == "Full Stack Development") echo "selected"; ?>>Full Stack Development</option> 
                            <option value="WordPress Development" <?php if ($service['subcategory'] == "WordPress Development") echo "selected"; ?>>WordPress Development</option> 
                        </optgroup> 
                        <optgroup label="Graphic Design"> 
                            <option value="Logo Design" <?php if ($service['subcategory'] == "Logo Design") echo "selected"; ?>>Logo Design</option> 
                            <option value="Brand Identity" <?php if ($service['subcategory'] == "Brand Identity") echo "selected"; ?>>Brand Identity</option> 
                            <option value="Print Design" <?php if ($service['subcategory'] == "Print Design") echo "selected"; ?>>Print Design</option> 
                            <option value="Illustration" <?php if ($service['subcategory'] == "Illustration") echo "selected"; ?>>Illustration</option> 
                        </optgroup> 
                        <optgroup label="Digital Marketing"> 
                            <option value="SEO">SEO</option> 
                            <option value="Social Media Marketing" <?php if ($service['subcategory'] == "Social Media Marketing") echo "selected"; ?>>Social Media Marketing</option> 
                            <option value="Email Marketing" <?php if ($service['subcategory'] == "Email Marketing") echo "selected"; ?>>Email Marketing</option> 
                            <option value="Content Marketing" <?php if ($service['subcategory'] == "Content Marketing") echo "selected"; ?>>Content Marketing</option>
                            <option value="PPC Advertising" <?php if ($service['subcategory'] == "PPC Advertising") echo "selected"; ?>>PPC Advertising</option> 
                        </optgroup>
                        <optgroup label="Music & Audio"> 
                            <option value="Voice Over" <?php if ($service['subcategory'] == "Voice Over") echo "selected"; ?>>Voice Over</option> 
                            <option value="Music Production" <?php if ($service['subcategory'] == "Music Production") echo "selected"; ?>>Music Production</option> 
                            <option value="Podcast Editing" <?php if ($service['subcategory'] == "Podcast Editing") echo "selected"; ?>>Podcast Editing</option> 
                            <option value="Sound Design" <?php if ($service['subcategory'] == "Sound Design") echo "selected"; ?>>Sound Design</option>
                        </optgroup>
                        <optgroup label="Writing & Translation"> 
                            <option value="Article & Blog Writing" <?php if ($service['subcategory'] == "Article & Blog Writing") echo "selected"; ?>>Article & Blog Writing</option> 
                            <option value="Copywriting" <?php if ($service['subcategory'] == "Copywriting") echo "selected"; ?>>Copywriting</option> 
                            <option value="Proofreading & Editing" <?php if ($service['subcategory'] == "Proofreading & Editing") echo "selected"; ?>>Proofreading & Editing</option> 
                            <option value="Translation" <?php if ($service['subcategory'] == "Translation") echo "selected"; ?>>Translation</option>
                        </optgroup>
                        <optgroup label="Video & Animation"> 
                            <option value="Video Editing" <?php if ($service['subcategory'] == "Video Editing") echo "selected"; ?>>Video Editing</option> 
                            <option value="Animation" <?php if ($service['subcategory'] == "Animation") echo "selected"; ?>>Animation</option> 
                            <option value="Explainer Videos" <?php if ($service['subcategory'] == "Explainer Videos") echo "selected"; ?>>Explainer Videos</option> 
                            <option value="Whiteboard Videos" <?php if ($service['subcategory'] == "Whiteboard Videos") echo "selected"; ?>>Whiteboard Videos</option>
                        </optgroup>
                        <optgroup label="Business Consulting">
                            <option value="Business Plans" <?php if ($service['subcategory'] == "Business Plans") echo "selected"; ?>>Business Plans</option> 
                            <option value="Market Research" <?php if ($service['subcategory'] == "Market Research") echo "selected"; ?>>Market Research</option> 
                            <option value="Financial Consulting" <?php if ($service['subcategory'] == "Financial Consulting") echo "selected"; ?>>Financial Consulting</option> 
                            <option value="Startup Consulting" <?php if ($service['subcategory'] == "Startup Consulting") echo "selected"; ?>>Startup Consulting</option>
                        </optgroup>
                        <optgroup label="Tutoring & Education">
                            <option value="Academic Tutoring" <?php if ($service['subcategory'] == "Academic Tutoring") echo "selected"; ?>>Academic Tutoring</option> 
                            <option value="Language Lessons" <?php if ($service['subcategory'] == "Language Lessons") echo "selected"; ?>>Language Lessons</option> 
                            <option value="Test Preparation" <?php if ($service['subcategory'] == "Test Preparation") echo "selected"; ?>>Test Preparation</option> 
                            <option value="Music Lessons" <?php if ($service['subcategory'] == "Music Lessons") echo "selected"; ?>>Music Lessons</option>
                        </optgroup>                                    
                    </select>
                </section>
                
                <section class="form-group">
                    <label class="form-label">Description *</label>
                    <textarea name="description" class="form-input " rows="8"  required><?php echo $service['description']; ?></textarea>
                </section>
                
                <section class="form-group">
                    <label class="form-label">Delivery Time (days) *</label>
                    <input type="number" name="delivery_time" class="form-input" min="1" max="90" value="<?php echo $service['delivery_time']; ?>" required>
                </section>
                
                <section class="form-group">
                    <label class="form-label">Revisions Included *</label>
                    <input type="number" name="revisions" class="form-input" min="0" max="999" value="<?php echo $service['revisions_included']; ?>" required>
                    
                </section>
                
                <section class="form-group">
                    <label class="form-label">Price (USD) *</label>
                    <input type="number" name="price" class="form-input" min="5" max="10000" value="<?php echo $service['price']; ?>" required>
                    
                </section>

                <section class="form-group">
                    <label class="form-label"> Image Status *</label>
                    <input type="radio" name="status" value="active" <?php if ($service['status'] == "Active") echo "checked"; ?>> Active
                    <input type="radio" name="status" value="inactive" <?php if ($service['status'] == "Inactive") echo "checked"; ?>> Inactive
                </section>

                <?php if ($service['status'] == "Active") { ?>
                <section class="form-group">
                    <label class="form-label"> Featured Status *</label>
                    <input type="checkbox" name="featured" value="yes" <?php if ($service['featured'] == "Yes") echo "checked"; ?>> Yes
                </section>

                <?php } ?>
                <section class="form-group">
                    <label class="form-label">Service Images (optional)</label>
                    <img src="<?php echo $service['image_1']; ?>" 
                         style="width: 120px; height: 90px; object-fit: cover; border-radius: 4px; margin-bottom: 10px;">
                    <input type="file" name="image1" class="form-input" accept="JPEG, JPG, PNG" maxsize="5MB">
                </section>

                
                
                <section class="form-actions">
                    <button type="submit" name="submit" class="btn btn-primary" style="flex: 1;">Save Changes</button>
                    <a href="my-services.php" class="btn btn-secondary">Cancel</a>
                </section>
            </form>



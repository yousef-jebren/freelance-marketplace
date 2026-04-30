<?php 
include "header.php"; 
 $step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
?>

<section class="container">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/steps.css">

<h1 class="heading-primary">Create New Service</h1>
    
    
    <!-- Progress Indicator -->
    <section class="step-indicator">
        <section class="step-item <?php echo $step > 1 ? 'step-completed' : ($step == 1 ? 'step-active' : 'step-inactive'); ?>">
            <section class="step-icon"><?php echo $step > 1 ? '' : '1'; ?></section>
            <span>Basic Information</span>
        </section>
        <section class="step-item <?php echo $step > 2 ? 'step-completed' : ($step == 2 ? 'step-active' : 'step-inactive'); ?>">
            <section class="step-icon"><?php echo $step > 2 ? '' : '2'; ?></section>
            <span>Upload Images</span>
        </section>
        <section class="step-item <?php echo $step == 3 ? 'step-active' : 'step-inactive'; ?>">
            <section class="step-icon">3</section>
            <span>Review & Publish</span>
        </section>
    </section>
    
    <section class="form-container" style="max-width: 800px;">
        <?php if ($step == 1): ?>
            <!-- Step 1: Basic Information -->
            <form method="POST" action="create-service_action.php" name="step1_form">
                <h2 class="heading-secondary">Basic Information</h2>
                <?php if (isset($_GET['error'])): ?>
                     <p style="color: red; text-align: center; margin-bottom: 20px;">
                     <?php echo $_GET['error'];  ?>
                     </p>
                <?php endif; ?>
                
                <section class="form-group">
                    <label class="form-label">Service Title *</label>
                    <input type="text" name="title" class="form-input" required>
                </section>
                
                <section class="form-group">
                    <label class="form-label">Category *</label>
                    <select name="category" class="form-select" required>
                        <option value="">Select Category</option>
                        <option value="Graphics & Design">Graphics & Design</option>
                        <option value="Digital Marketing">Digital Marketing</option>
                        <option value="Writing & Translation">Writing & Translation</option>
                        <option value="Video & Animation">Video & Animation</option>
                        <option value="Music & Audio">Music & Audio</option>
                        <option value="Web Development">Web Development</option>
                        <option value="Business Consulting">Business Consulting</option>
                        <option value="Tutoring & Education">Tutoring & Education</option>
                    </select>
                </section>
                
                <section class="form-group">
                    <label class="form-label">Subcategory *</label>
                    <select name="subcategory" class="form-select" required>
                        <option value="">Select Subcategory</option>
                        <optgroup label="Web Development"> 
                            <option>Frontend Development</option> 
                            <option>Backend Development</option> 
                            <option>Full Stack Development</option> 
                            <option>WordPress Development</option> 
                        </optgroup> 
                        <optgroup label="Graphic Design"> 
                            <option>Logo Design</option> 
                            <option>Brand Identity</option> 
                            <option>Print Design</option> 
                            <option>Illustration</option> 
                        </optgroup> 
                        <optgroup label="Digital Marketing"> 
                            <option>SEO</option> 
                            <option>Social Media Marketing</option> 
                            <option>Email Marketing</option> 
                            <option>Content Marketing</option>
                            <option>PPC Advertising</option> 
                        </optgroup>
                        <optgroup label="Music & Audio"> 
                            <option>Voice Over</option> 
                            <option>Music Production</option> 
                            <option>Podcast Editing</option> 
                            <option>Sound Design</option>
                        </optgroup>
                        <optgroup label="Writing & Translation"> 
                            <option>Article & Blog Writing</option> 
                            <option>Copywriting</option> 
                            <option>Proofreading & Editing</option> 
                            <option>Translation</option>
                        </optgroup>
                        <optgroup label="Video & Animation"> 
                            <option>Video Editing</option> 
                            <option>Animation</option> 
                            <option>Explainer Videos</option> 
                            <option>Whiteboard Videos</option>
                        </optgroup>
                        <optgroup label="Business Consulting">
                            <option>Business Plans</option> 
                            <option>Market Research</option> 
                            <option>Financial Consulting</option> 
                            <option>Startup Consulting</option>
                        </optgroup>
                        <optgroup label="Tutoring & Education">
                            <option>Academic Tutoring</option> 
                            <option>Language Lessons</option> 
                            <option>Test Preparation</option> 
                            <option>Music Lessons</option>
                        </optgroup>                                    
                    </select>
                </section>
                
                <section class="form-group">
                    <label class="form-label">Description *</label>
                    <textarea name="description" class="form-input " rows="8" placeholder="Describe your service in detail (100-2000 characters)" required></textarea>
                </section>
                
                <section class="form-group">
                    <label class="form-label">Delivery Time (days) *</label>
                    <input type="number" name="delivery_time" class="form-input" min="1" max="90" required>
                </section>
                
                <section class="form-group">
                    <label class="form-label">Revisions Included *</label>
                    <input type="number" name="revisions" class="form-input" min="0" max="999"  required>
                    
                </section>
                
                <section class="form-group">
                    <label class="form-label">Price (USD) *</label>
                    <input type="number" name="price" class="form-input" min="5" max="10000" placeholder="$5 - $10,000" required>
                    
                </section>
                
                <section class="form-actions">
                    <button type="submit" name="step1_submit" class="btn btn-primary" style="flex: 1;">Continue to Images</button>
                    <a href="my-services.php" class="btn btn-secondary">Cancel</a>
                </section>
            </form>
            
        <?php elseif ($step == 2): ?>
            <!-- Step 2: Upload Images -->
            <form method="POST" action="create-service_action.php" enctype="multipart/form-data">
                <?php if (isset($_GET['error'])): ?>
                    <p style="color: red; text-align: center; margin-bottom: 20px;">
                <?php echo $_GET['error'];  ?>       
        </p>   
                <?php endif; ?>
            <h2 class="heading-secondary">Upload Images</h2>
                
                <p style="color: #6C757D; margin-bottom: 20px;">
                    Upload 1-3 images 
                </p>
                
                
                <section class="form-group">
                    <label class="form-label">Service Images *</label>

                    <label> Image one (required) </label>
                    <input type="file" name="image1" class="form-input" accept=".jpg,.jpeg,.png" required>

                    <label> Image two (optional) </label>
                    <input type="file" name="image2" class="form-input" accept=".jpg,.jpeg,.png" >
                    <label> Image three (optional) </label>
                    <input type="file" name="image3" class="form-input" accept=".jpg,.jpeg,.png" >

                    <p style="font-size: 13px; color: #6C757D; margin-top: 5px;">
                        The first image will be the main image by default
                    </p>
                </section>
                
                <section class="form-actions">
                    <button type="submit" name="step2_submit" class="btn btn-primary" style="flex: 1;">Publish</button>
                    <a href="create-service.php?step=1" class="btn btn-secondary">Back</a>
                </section>
            </form>
            
        <?php elseif ($step == 3): ?>
            <!-- Step 3: Review & Publish -->
            <?php 
                $step1Data = $_SESSION['service_data'] ?? [];
             ?>
            <h2 class="heading-secondary">Review Your Service</h2>
            
            <section class="card" style="margin-bottom: 20px;">
                <h3 class="heading-tertiary"><?php echo htmlspecialchars($step1Data['title'] ?? ''); ?></h3>
                <p style="color: #6C757D; margin: 10px 0;">
                    <?php echo htmlspecialchars($step1Data['category'] ?? ''); ?> &gt; <?php echo htmlspecialchars($step1Data['subcategory'] ?? ''); ?>
                </p>
                <p style="margin: 15px 0;"><?php echo nl2br(htmlspecialchars($step1Data['description'] ?? '')); ?></p>
                
                <section style="display: flex; gap: 30px; margin-top: 20px;">
                    <section>
                        <strong>Price:</strong> $<?php echo number_format($step1Data['price'] ?? 0, 2); ?>
                    </section>
                    <section>
                        <strong>Delivery:</strong> <?php echo $step1Data['delivery_time'] ?? 0; ?> days
                    </section>
                    <section>
                        <strong>Revisions:</strong> <?php echo $step1Data['revisions'] == 999 ? 'Unlimited' : $step1Data['revisions']; ?>
                    </section>
                </section>
            </section>
            
            <form method="POST" action="create-service_action.php">
                <section class="form-actions">
                    <button type="submit" name="step3_submit" class="btn btn-success" style="flex: 1; font-size: 18px;">
                        Publish Service
                    </button>
                    <a href="create-service.php?step=2" class="btn btn-secondary">Back</a>
                </section>
            </form>



        <?php endif; ?>
                
    </section>
</section>

<?php require_once 'footer.php'; ?>
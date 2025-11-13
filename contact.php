<?php
ob_start();
require_once "config/db.php";

$success = $error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if(empty($name) || empty($email) || empty($message)) {
        $error = "All fields are required.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Insert message into DB
        $stmt = $pdo->prepare("INSERT INTO contacts (name, email, message, created_at) VALUES (:name, :email, :message, NOW())");
        $stmt->execute([
            'name'=>$name,
            'email'=>$email,
            'message'=>$message
        ]);
        $success = "Thank you! Your message has been sent successfully.";
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-3 text-center text-primary fw-bold">Contact Us</h2>
            <p class="lead text-center mb-4">We’re here to help! Send us your queries, feedback, or partnership requests.</p>

            <!-- Display messages -->
            <?php if($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <!-- Contact Form -->
            <form id="contactForm" method="post" class="card shadow p-4 mb-5 border-primary bg-light">
                <div class="mb-3">
                    <label for="name" class="form-label"><i class="fas fa-user me-2"></i>Your Name</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter your name" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label"><i class="fas fa-envelope me-2"></i>Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="mb-3">
                    <label for="message" class="form-label"><i class="fas fa-comment-alt me-2"></i>Message</label>
                    <textarea class="form-control" id="message" name="message" rows="5" placeholder="Write your message..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100 shadow-sm">Send Message</button>
            </form>

            <!-- Contact Info + Social Media -->
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card border-info shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title text-info">Contact Information</h5>
                            <p><i class="fas fa-envelope me-2"></i>Email: <a href="mailto:support@osp-platform.com">support@osp-platform.com</a></p>
                            <p><i class="fas fa-phone me-2"></i>Phone: +91-9876543210</p>
                            <p><i class="fas fa-map-marker-alt me-2"></i>Address: 123 Main Street, Your City, Country</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-success shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title text-success">Follow Us</h5>
                            <div class="d-flex gap-3 mt-3">
                                <a href="#" class="text-info fs-4"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="text-info fs-4"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="text-info fs-4"><i class="fab fa-instagram"></i></a>
                                <a href="#" class="text-info fs-4"><i class="fab fa-linkedin"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
?>

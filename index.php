<?php
ob_start();
require_once "config/db.php";

// Fetch popular categories (limit 4)
$categoriesStmt = $pdo->query("SELECT id, category_name, image FROM categories LIMIT 4");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch featured services (limit 6)
$servicesStmt = $pdo->query("
    SELECT s.id, s.title, s.description, s.image, c.category_name
    FROM services s
    JOIN categories c ON s.category_id = c.id
    JOIN providers p ON s.provider_id = p.id
    WHERE s.status='active' AND p.status='active' AND p.plan_expiry >= CURDATE()
    ORDER BY s.id DESC LIMIT 6
");
$featuredServices = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
/* Fix blurry and uneven carousel images */
.slider-img {
    height: 600px;
    object-fit: cover; /* ensures consistent cropping and quality */
    object-position: center;
    filter: brightness(0.85); /* optional, makes text pop */
}

/* Make text more readable */
.carousel-caption {
    text-shadow: 0 2px 6px rgba(0,0,0,0.6);
}

.text-shadow {
    text-shadow: 0 2px 6px rgba(0,0,0,0.7);
}
</style>
<!-- Hero Slider -->
<section id="heroSlider" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner" style="max-height: 600px; overflow: hidden;">
        <div class="carousel-item active">
            <img src="assets/img/services/plumbing.jpg" class="d-block w-100 slider-img" alt="Slide 1">
            <div class="carousel-caption d-none d-md-block">
                <h1 class="display-4 fw-bold text-white text-shadow">Find the Best Services Near You</h1>
                <p class="fs-5">From home cleaning to tutoring, discover trusted providers.</p>
                <a href="./visitor/register.php" class="btn btn-warning btn-lg mt-2">Get Started</a>
            </div>
        </div>
        <div class="carousel-item">
            <img src="assets/img//services/cleaning.jpg" class="d-block w-100 slider-img" alt="Slide 2">
            <div class="carousel-caption d-none d-md-block">
                <h1 class="display-4 fw-bold text-white text-shadow">Trusted Professionals</h1>
                <p class="fs-5">Verified providers delivering quality services.</p>
                <a href="view_services.php" class="btn btn-primary btn-lg mt-2">Explore Services</a>
            </div>
        </div>
        <div class="carousel-item">
            <img src="assets/img//services/tutoring.jpg" class="d-block w-100 slider-img" alt="Slide 3">
            <div class="carousel-caption d-none d-md-block">
                <h1 class="display-4 fw-bold text-white text-shadow">Easy Booking & Reviews</h1>
                <p class="fs-5">Book your desired service and leave feedback easily.</p>
                <a href="view_services.php" class="btn btn-success btn-lg mt-2">Book Now</a>
            </div>
        </div>
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#heroSlider" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroSlider" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</section>


<!-- Search Bar -->
<section class="py-5 bg-light">
    <div class="container">
        <form id="homeSearch" class="d-flex shadow p-3 bg-white rounded">
            <input id="searchQuery" type="text" class="form-control me-2" placeholder="Search services..." required>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>
</section>

<!-- Popular Categories -->
<section class="py-5">
    <div class="container">
        <h3 class="mb-4 text-center">Popular Categories</h3>
        <div class="row g-4 justify-content-center">
            <?php foreach($categories as $cat): ?>
                <div class="col-md-3">
                    <div class="card h-100 text-center shadow-sm card-hover">
                        <img src="<?= htmlspecialchars($cat['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($cat['category_name']) ?>" style="height:200px; object-fit:cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($cat['category_name']) ?></h5>
                            <a href="login.php" class="btn btn-outline-primary btn-sm mt-2">Explore</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>



<!-- Why Choose Us -->
<section class="py-5">
    <div class="container text-center">
        <h3 class="mb-4 text-primary">Why Choose Our Platform?</h3>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card p-3 shadow-sm h-100">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5>Trusted Providers</h5>
                    <p>Verified and reliable professionals.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 shadow-sm h-100">
                    <i class="fas fa-thumbs-up fa-3x text-primary mb-3"></i>
                    <h5>Easy Booking</h5>
                    <p>Quickly book services online.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 shadow-sm h-100">
                    <i class="fas fa-star fa-3x text-warning mb-3"></i>
                    <h5>Ratings & Reviews</h5>
                    <p>Read and leave feedback on services.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 shadow-sm h-100">
                    <i class="fas fa-mobile-alt fa-3x text-info mb-3"></i>
                    <h5>Mobile Friendly</h5>
                    <p>Access services from any device.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
require 'layout.php';
?>

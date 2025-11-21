<?php
session_start();
require_once "../config/db.php";

// Session check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

// Get service ID
$serviceId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch service details
$stmt = $pdo->prepare("
    SELECT 
        s.id, s.title, s.description, s.image, s.status,
        c.category_name, 
        p.id AS provider_id, p.full_name AS provider_name
    FROM services s
    JOIN providers p ON s.provider_id = p.id
    JOIN categories c ON s.category_id = c.id
    WHERE s.id = :id AND s.status = 'Active' AND p.status = 'Active'
");
$stmt->execute(['id' => $serviceId]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$service) {
    die("<div class='alert alert-danger text-center m-5'>Service not found or unavailable.</div>");
}

// Fetch reviews
$reviewStmt = $pdo->prepare("
    SELECT r.rating, r.comment, u.full_name AS user_name
    FROM reviews r
    JOIN customers u ON r.customer_id = u.id
    WHERE r.service_id = :service_id
    ORDER BY r.id DESC
");
$reviewStmt->execute(['service_id' => $serviceId]);
$reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);

// Image fallback
$imgPath = "../assets/img/services/" . htmlspecialchars($service['image']);
if (empty($service['image']) || !file_exists($imgPath)) {
    $imgPath = "../assets/img/default-service.jpg";
}

// Rating stars
function renderStars($rating) {
    $stars = "";
    for ($i = 1; $i <= 5; $i++) {
        $stars .= ($i <= $rating)
            ? '<i class="fas fa-star text-warning"></i>'
            : '<i class="far fa-star text-muted"></i>';
    }
    return $stars;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($service['title']) ?> - Customer Panel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
<style>
body { background-color: #f8f9fa; }

.service-detail-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 25px;
}
.service-img {
    width: 100%;
    max-width: 400px;
    height: auto;
    border-radius: 10px;
    object-fit: cover;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.service-info h2 {
    font-weight: 600;
}
.btn-book {
    border-radius: 8px;
    padding: 10px 20px;
    font-weight: 500;
}
.review-card {
    background: #fff;
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: 0.3s;
}
.review-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.review-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(45deg, #0d6efd, #6610f2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: bold;
    font-size: 1rem;
    margin-right: 12px;
}
</style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php require 'sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            
            <div class="service-detail-card mb-4">
                <div class="row align-items-center">
                    <div class="col-md-5 text-center mb-3 mb-md-0">
                        <img src="<?= $imgPath ?>" alt="<?= htmlspecialchars($service['title']) ?>" class="service-img">
                    </div>
                    <div class="col-md-7 service-info">
                        <h2 class="mb-2"><?= htmlspecialchars($service['title']) ?></h2>
                        <p class="text-muted mb-1"><strong>Category:</strong> <?= htmlspecialchars($service['category_name']) ?></p>
                        <p class="text-muted mb-3"><strong>Provider:</strong> <?= htmlspecialchars($service['provider_name']) ?></p>
                        <p class="lead text-secondary"><?= nl2br(htmlspecialchars($service['description'])) ?></p>
                        <form method="post" action="book_service.php" class="mt-3">
                            <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                            <input type="hidden" name="provider_id" value="<?= $service['provider_id'] ?>">
                            <button type="submit" class="btn btn-primary btn-book">
                                <i class="fas fa-calendar-check me-1"></i> Book This Service
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <h4 class="mb-3 fw-semibold"><i class="fas fa-star text-warning me-2"></i> Reviews & Ratings</h4>

            <div class="row">
                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $r): 
                        $initial = strtoupper(substr($r['user_name'], 0, 1));
                    ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="review-card d-flex flex-column h-100">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="review-avatar"><?= $initial ?></div>
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($r['user_name']) ?></h6>
                                        <small><?= renderStars($r['rating']) ?></small>
                                    </div>
                                </div>
                                <p class="text-muted mb-0"><?= htmlspecialchars($r['comment']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center text-muted py-5">
                        <i class="fas fa-comment-dots fa-2x mb-2"></i>
                        <p>No reviews yet for this service.</p>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

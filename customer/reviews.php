<?php
session_start();
require_once "../config/db.php";

// Session check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];
$success = $error = "";

// Fetch completed bookings for this customer (status = 'Completed')
$bookingStmt = $pdo->prepare("
    SELECT b.id as booking_id, s.id as service_id, s.title, p.full_name as provider_name
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN providers p ON s.provider_id = p.id
    WHERE b.customer_id = :customer_id AND b.status = 'Completed'
    ORDER BY s.title ASC
");
$bookingStmt->execute(['customer_id' => $customer_id]);
$completedBookings = $bookingStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = intval($_POST['service'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($service_id <= 0) {
        $error = "Please select a valid service.";
    } elseif ($rating < 1 || $rating > 5) {
        $error = "Please select a valid rating.";
    } elseif (empty($comment)) {
        $error = "Please enter your comment.";
    } else {
        // Check if customer already submitted review for this service
        $checkStmt = $pdo->prepare("
            SELECT id FROM reviews 
            WHERE customer_id=:customer_id AND service_id=:service_id
        ");
        $checkStmt->execute(['customer_id'=>$customer_id, 'service_id'=>$service_id]);

        if ($checkStmt->rowCount() > 0) {
            $error = "You have already submitted a review for this service.";
        } else {
            // Insert review
            $insertStmt = $pdo->prepare("
                INSERT INTO reviews (customer_id, service_id, rating, comment, created_at)
                VALUES (:customer_id, :service_id, :rating, :comment, NOW())
            ");
            $insertStmt->execute([
                'customer_id'=>$customer_id,
                'service_id'=>$service_id,
                'rating'=>$rating,
                'comment'=>$comment
            ]);
            $success = "Review submitted successfully!";
        }
    }
}

// Fetch customer's submitted reviews
$reviewStmt = $pdo->prepare("
    SELECT r.id, s.title, r.rating, r.comment
    FROM reviews r
    JOIN services s ON r.service_id = s.id
    WHERE r.customer_id = :customer_id
    ORDER BY r.created_at DESC
");
$reviewStmt->execute(['customer_id' => $customer_id]);
$reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Reviews - Customer Panel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
<style>
.card-hover:hover { transform: translateY(-4px); transition: 0.3s; box-shadow:0 4px 12px rgba(0,0,0,0.15);}
</style>
</head>
<body>
<div class="container-fluid">
<div class="row">
    <?php require 'sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
        <h2 class="mb-4">My Reviews</h2>

        <!-- Display messages -->
        <?php if($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <!-- Review Form -->
        <form id="customerReviewForm" method="post" class="mb-4">
            <div class="mb-3">
                <label for="service" class="form-label">Service</label>
                <select class="form-select" id="service" name="service" required>
                    <option value="">Choose...</option>
                    <?php foreach($completedBookings as $b): ?>
                        <option value="<?= $b['service_id'] ?>"><?= htmlspecialchars($b['title']) ?> (<?= htmlspecialchars($b['provider_name']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="rating" class="form-label">Rating</label>
                <select class="form-select" id="rating" name="rating" required>
                    <option value="">Select rating</option>
                    <option value="5">★★★★★</option>
                    <option value="4">★★★★</option>
                    <option value="3">★★★</option>
                    <option value="2">★★</option>
                    <option value="1">★</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="comment" class="form-label">Comment</label>
                <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-warning">Submit Review</button>
        </form>

        <!-- Submitted Reviews -->
        <h4 class="mt-4">Your Submitted Reviews</h4>
        <div class="list-group">
            <?php if(!empty($reviews)): ?>
                <?php foreach($reviews as $r): ?>
                    <div class="list-group-item">
                        <strong><?= htmlspecialchars($r['title']) ?></strong> - <span class="text-warning"><?= str_repeat('★', $r['rating']) ?></span><br>
                        <span><?= htmlspecialchars($r['comment']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="list-group-item text-muted">No reviews submitted yet.</div>
            <?php endif; ?>
        </div>
    </main>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

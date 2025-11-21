<?php
ob_start();
session_start();
require_once "../config/db.php";

// Check if customer is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];

// =========================
// Stat Cards
// =========================
$total_bookings = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = :id");
$total_bookings->execute(['id'=>$customer_id]);
$total_bookings = $total_bookings->fetchColumn();

$total_reviews = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE customer_id = :id");
$total_reviews->execute(['id'=>$customer_id]);
$total_reviews = $total_reviews->fetchColumn();

$total_active_services = $pdo->prepare("SELECT COUNT(DISTINCT service_id) FROM bookings WHERE customer_id = :id AND status='Ongoing'");
$total_active_services->execute(['id'=>$customer_id]);
$total_active_services = $total_active_services->fetchColumn();

// =========================
// Upcoming Bookings
// =========================
$upcoming = $pdo->prepare("
    SELECT b.*, s.title as service_name, p.full_name as provider_name 
    FROM bookings b 
    JOIN services s ON b.service_id = s.id
    JOIN providers p ON s.provider_id = p.id
    WHERE b.customer_id=:id AND b.status='Upcoming'
    ORDER BY b.booking_date ASC 
    LIMIT 5
");
$upcoming->execute(['id'=>$customer_id]);
$upcoming = $upcoming->fetchAll(PDO::FETCH_ASSOC);

// =========================
// Recent Activity (Bookings + Reviews)
// =========================
$recent_activity = $pdo->prepare("
    SELECT 'booking' AS type, s.title AS name, b.status, b.booking_date AS date 
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    WHERE b.customer_id=:customer_id
    UNION ALL
    SELECT 'review', s.title AS name, r.rating AS status, r.created_at AS date
    FROM reviews r
    JOIN services s ON r.service_id = s.id
    WHERE r.customer_id=:customer_id_review
    ORDER BY date DESC 
    LIMIT 5
");
$recent_activity->execute([
    'customer_id' => $customer_id,
    'customer_id_review' => $customer_id
]);
$recent_activity = $recent_activity->fetchAll(PDO::FETCH_ASSOC);

// =========================
// Monthly Bookings Chart
// =========================
$monthly_bookings = $pdo->prepare("
    SELECT MONTH(booking_date) AS month, COUNT(*) AS total_bookings
    FROM bookings
    WHERE customer_id=:id AND YEAR(booking_date) = YEAR(CURDATE())
    GROUP BY MONTH(booking_date)
");
$monthly_bookings->execute(['id'=>$customer_id]);
$monthly_bookings = $monthly_bookings->fetchAll(PDO::FETCH_ASSOC);

$months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$bookingData = array_fill(0,12,0);
foreach($monthly_bookings as $row){
    $bookingData[$row['month']-1] = (int)$row['total_bookings'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
<style>
.card-hover:hover { transform: translateY(-4px); transition:0.3s; box-shadow:0 4px 12px rgba(0,0,0,0.15);}
.profile-bubble { display:inline-flex; width:32px; height:32px; align-items:center; justify-content:center; border-radius:50%; color:#fff; margin-right:8px; font-weight:bold; }
.stat-icon { width:50px; height:50px; display:flex; align-items:center; justify-content:center; border-radius:50%; font-size:1.2rem; margin:0 auto 10px; color:#fff; background:#fff; }
.badge-status { float:right; }
</style>
</head>
<body>
<div class="container-fluid">
<div class="row">
    <?php require 'sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
        <h2 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Customer') ?>!</h2>

        <!-- Stat Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card card-custom text-center bg-primary text-white">
                    <div class="card-body">
                        <div class="stat-icon text-primary mb-2"><i class="fas fa-calendar-check"></i></div>
                        <h6 class="card-title-sm">Bookings</h6>
                        <h2 class="counter" data-target="<?= $total_bookings ?>"><?= $total_bookings ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card card-custom text-center bg-warning text-white">
                    <div class="card-body">
                        <div class="stat-icon text-warning mb-2"><i class="fas fa-star"></i></div>
                        <h6 class="card-title-sm">Reviews</h6>
                        <h2 class="counter" data-target="<?= $total_reviews ?>"><?= $total_reviews ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card card-custom text-center bg-success text-white">
                    <div class="card-body">
                        <div class="stat-icon text-success mb-2"><i class="fas fa-briefcase"></i></div>
                        <h6 class="card-title-sm">Active Services</h6>
                        <h2 class="counter" data-target="<?= $total_active_services ?>"><?= $total_active_services ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Bookings Chart -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card card-custom">
                    <div class="card-body">
                        <h5 class="mb-3">Monthly Bookings</h5>
                        <canvas id="bookingsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Bookings -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card card-custom">
                    <div class="card-body">
                        <h5 class="mb-3">Upcoming Bookings</h5>
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr><th>Service</th><th>Provider</th><th>Date</th><th>Status</th><th>Action</th></tr>
                            </thead>
                            <tbody>
                                <?php if($upcoming): ?>
                                    <?php foreach($upcoming as $b): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($b['service_name']) ?></td>
                                            <td><?= htmlspecialchars($b['provider_name']) ?></td>
                                            <td><?= htmlspecialchars($b['booking_date']) ?></td>
                                            <td><span class="badge bg-info"><?= htmlspecialchars($b['status']) ?></span></td>
                                            <td><a href="book_service.php" class="btn btn-sm btn-outline-primary">View</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5">No upcoming bookings.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card card-custom">
                    <div class="card-body">
                        <h5 class="mb-3">Recent Activity</h5>
                        <ul class="list-group list-group-flush recent-list">
                            <?php if(!empty($recent_activity)): ?>
                                <?php foreach($recent_activity as $act): ?>
                                    <li class="list-group-item">
                                        <span class="profile-bubble me-2"><?= strtoupper(substr($act['type'],0,1)) ?></span>
                                        <?php if($act['type']=='booking'): ?>
                                            You booked <strong><?= htmlspecialchars($act['name']) ?></strong> on <?= htmlspecialchars($act['date']) ?>.
                                            <span class="badge bg-success badge-status"><?= htmlspecialchars($act['status']) ?></span>
                                        <?php else: ?>
                                            You submitted a review for <strong><?= htmlspecialchars($act['name']) ?></strong>.
                                            <span class="badge bg-warning badge-status">Rated <?= htmlspecialchars($act['status']) ?>/5</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-muted">No recent activity.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Animate Counters
  $('.counter').each(function () {
    const $this = $(this);
    $({ Counter: 0 }).animate({ Counter: $this.data('target') }, {
      duration: 2000,
      easing: 'swing',
      step: function (now) { $this.text(Math.ceil(now)); }
    });
  });

  // Monthly Bookings Chart
  const ctx = document.getElementById('bookingsChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode($months) ?>,
      datasets: [{
        label: 'Bookings per Month',
        data: <?= json_encode($bookingData) ?>,
        borderColor: '#0d6efd',
        backgroundColor: 'rgba(13,110,253,0.2)',
        tension: 0.4,
        fill: true
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: true } },
      scales: { y: { beginAtZero: true } }
    }
  });
</script>
</body>
</html>

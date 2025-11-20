<?php
session_start();
require_once "../config/db.php";

// ==========================
//  Session Check
// ==========================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'provider') {
    header("Location: ../login.php");
    exit;
}

$provider_id = $_SESSION['user_id'];

try {
    // ==========================
    // Fetch Provider Info
    // ==========================
    $stmt = $pdo->prepare("SELECT * FROM providers WHERE id = :id");
    $stmt->execute(['id' => $provider_id]);
    $provider = $stmt->fetch(PDO::FETCH_ASSOC);

    // ==========================
    // Total Services
    // ==========================
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE provider_id = :pid");
    $stmt->execute(['pid' => $provider_id]);
    $total_services = (int)$stmt->fetchColumn();

    // ==========================
    // Total Bookings
    // ==========================
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        WHERE s.provider_id = :pid
    ");
    $stmt->execute(['pid' => $provider_id]);
    $total_bookings = (int)$stmt->fetchColumn();

    // ==========================
    // AVG Rating
    // ==========================
    $stmt = $pdo->prepare("
        SELECT AVG(r.rating) as avg_rating
        FROM reviews r
        JOIN services s ON r.service_id = s.id
        WHERE s.provider_id = :pid
    ");
    $stmt->execute(['pid' => $provider_id]);
    $avg_rating = round((float)$stmt->fetchColumn(), 1);

    // ==========================
    // Monthly Bookings Chart
    // ==========================
    $stmt = $pdo->prepare("
        SELECT MONTH(b.booking_date) AS month, COUNT(*) AS bookings_count
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        WHERE s.provider_id = :pid AND YEAR(b.booking_date) = YEAR(CURDATE())
        GROUP BY MONTH(b.booking_date)
    ");
    $stmt->execute(['pid' => $provider_id]);
    $monthly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $bookingsChartData = array_fill(0, 12, 0);
    foreach($monthly_data as $row){
        $bookingsChartData[$row['month']-1] = (int)$row['bookings_count'];
    }

    // ==========================
    // Pending Bookings
    // ==========================
    $stmt = $pdo->prepare("
        SELECT b.id, b.booking_date, b.details,
               c.full_name AS customer_name, s.title AS service_name
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN services s ON b.service_id = s.id
        WHERE s.provider_id = :pid AND b.status='Pending'
        ORDER BY b.booking_date ASC
        LIMIT 5
    ");
    $stmt->execute(['pid' => $provider_id]);
    $pending_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ==========================
    // Recent Activity (last 5 bookings)
    // ==========================
    $stmt = $pdo->prepare("
        SELECT b.id, b.booking_date, b.status,
               c.full_name AS customer_name, s.title AS service_name
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN services s ON b.service_id = s.id
        WHERE s.provider_id = :pid
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    $stmt->execute(['pid' => $provider_id]);
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ==========================
    // Top Services (most bookings)
    // ==========================
    $stmt = $pdo->prepare("
        SELECT s.id, s.title, COUNT(b.id) AS total_bookings
        FROM services s
        LEFT JOIN bookings b ON s.id = b.service_id
        WHERE s.provider_id = :pid
        GROUP BY s.id
        ORDER BY total_bookings DESC
        LIMIT 5
    ");
    $stmt->execute(['pid' => $provider_id]);
    $top_services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ==========================
    // Subscription Info
    // ==========================
    $plan_type = $provider['plan_id'] ?? 'Basic';
    $next_billing = $provider['plan_expiry'] ?? '—';

} catch (PDOException $e){
    die("Database error: " . $e->getMessage());
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Provider Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link href="../assets/css/style.css" rel="stylesheet">

<style>
.card-hover{transition:0.3s;} .card-hover:hover{transform:scale(1.05); box-shadow:0 10px 20px rgba(0,0,0,0.2);}
.card-gradient{background:linear-gradient(135deg,#667eea,#764ba2); color:#fff;}
.stat-icon{display:flex; align-items:center; justify-content:center; width:50px; height:50px; border-radius:50%; background:rgba(255,255,255,0.2); color:#fff; font-size:1.2rem;}
.profile-bubble{width:45px;height:45px;border-radius:50%;background:#6c5ce7;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;}
.small-muted{color:#6c757d;}
.badge-status{font-size:0.75rem;}
</style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <?php require 'sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-0">Welcome back, <span class="text-primary"><?= htmlspecialchars($provider['full_name']) ?></span>!</h2>
          <div class="small-muted">Here's a quick overview of your services and bookings</div>
        </div>
      </div>

      <!-- Top Stat Cards -->
      <div class="row mb-4">
        <div class="col-md-3 mb-3">
          <div class="card card-gradient card-hover text-white">
            <div class="card-body d-flex align-items-center justify-content-between">
              <div><h6>Total Services</h6><h2 class="counter" data-target="<?= $total_services ?>">0</h2></div>
              <div class="stat-icon bg-warning"><i class="fas fa-briefcase"></i></div>
            </div>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="card card-gradient card-hover text-white" style="background:linear-gradient(135deg,#06b6d4,#0ea5a2);">
            <div class="card-body d-flex align-items-center justify-content-between">
              <div><h6>Total Bookings</h6><h2 class="counter" data-target="<?= $total_bookings ?>">0</h2></div>
              <div class="stat-icon bg-info"><i class="fa-solid fa-calendar-check"></i></div>
            </div>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="card card-gradient card-hover text-white" style="background:linear-gradient(135deg,#7c3aed,#4f46e5);">
            <div class="card-body d-flex align-items-center justify-content-between">
              <div><h6>AVG Rating</h6><h2 class="counter" data-target="<?= $avg_rating ?>">0</h2></div>
              <div class="stat-icon bg-purple"><i class="fa-solid fa-star"></i></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Booking Requests -->
      <div class="row g-3 mb-4">
        <div class="col-lg-8">
          <div class="card card-hover p-3 h-100">
            <h5 class="mb-3">Monthly Bookings Overview</h5>
            <canvas id="revenueChart" style="height:320px"></canvas>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="card card-hover p-3 mb-3">
            <h6 class="mb-2">Booking Requests</h6>
            <ul class="list-group list-group-flush">
              <?php if($pending_bookings): ?>
                <?php foreach($pending_bookings as $b): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div><strong><?= htmlspecialchars($b['customer_name']) ?></strong> booked <br><?= htmlspecialchars($b['service_name']) ?></div>
                    <span class="badge bg-warning"><?= htmlspecialchars($b['booking_date']) ?></span>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li class="list-group-item text-center small-muted py-4">No pending bookings</li>
              <?php endif; ?>
            </ul>
          </div>
          <div class="card card-hover p-3">
            <h6 class="mb-2">Subscription</h6>
            <div class="small-muted">Plan: <?= htmlspecialchars($plan_type) ?><br>Next Billing: <?= htmlspecialchars($next_billing) ?></div>
          </div>
        </div>
      </div>

      <!-- Recent Activity & Top Services -->
      <div class="row g-3">
        <div class="col-lg-6">
          <div class="card card-hover p-3">
            <h6 class="mb-2">Recent Activity</h6>
            <ul class="list-group list-group-flush">
              <?php if($recent_activity): ?>
                <?php foreach($recent_activity as $r): ?>
                  <li class="list-group-item"><strong><?= htmlspecialchars($r['customer_name']) ?></strong> booked <strong><?= htmlspecialchars($r['service_name']) ?></strong> (<?= htmlspecialchars($r['status']) ?>)</li>
                <?php endforeach; ?>
              <?php else: ?>
                <li class="list-group-item text-center small-muted py-4">No recent activity</li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card card-hover p-3">
            <h6 class="mb-2">Top Services</h6>
            <ul class="list-group list-group-flush">
              <?php if($top_services): ?>
                <?php foreach($top_services as $t): ?>
                  <li class="list-group-item d-flex justify-content-between">
                    <?= htmlspecialchars($t['title']) ?>
                    <span class="badge bg-info"><?= $t['total_bookings'] ?> bookings</span>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li class="list-group-item text-center small-muted py-4">No services found</li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </div>
      <div class="mt-4 text-center small-muted">
        Dashboard last updated: <?= date("d M Y, H:i") ?>
      </div>
    </main>
  </div>
</div>

<script>
$(document).ready(function(){
  $('.counter').each(function () {
    const $this = $(this);
    $({ countNum: 0 }).animate(
      { countNum: $this.data('target') },
      { duration: 1500, easing: 'swing', step: function () { $this.text(Math.floor(this.countNum)); }, complete: function() { $this.text(this.countNum); } }
    );
  });
});

// Chart.js
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
  type: 'line',
  data: {
    labels: <?= json_encode($months) ?>,
    datasets: [{
      label: 'Bookings',
      data: <?= json_encode($bookingsChartData) ?>,
      borderColor: '#667eea',
      backgroundColor: 'rgba(102,126,234,0.2)',
      tension: 0.3,
      fill: true
    }]
  },
  options: { responsive:true, plugins:{ legend:{ display:false } } }
});
</script>
</body>
</html>

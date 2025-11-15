<?php
ob_start();
session_start();
require_once "../config/db.php";

// ✅ Admin session check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// ✅ Fetch top stats
$total_users = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$total_providers = (int)$pdo->query("SELECT COUNT(*) FROM providers")->fetchColumn();
$total_services = (int)$pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
$total_revenue = (float)$pdo->query("SELECT IFNULL(SUM(amount),0) FROM payments WHERE status='completed'")->fetchColumn();

// ✅ Prepare monthly revenue chart data
$revenueStmt = $pdo->prepare("
    SELECT MONTH(created_at) AS month, IFNULL(SUM(amount),0) AS total
    FROM payments
    WHERE YEAR(created_at) = YEAR(CURDATE()) AND status='completed'
    GROUP BY MONTH(created_at)
");
$revenueStmt->execute();
$revenueData = $revenueStmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Prepare new users chart data
$userStmt = $pdo->prepare("
    SELECT MONTH(created_at) AS month, COUNT(*) AS total
    FROM customers
    WHERE YEAR(created_at) = YEAR(CURDATE())
    GROUP BY MONTH(created_at)
");
$userStmt->execute();
$userData = $userStmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Chart arrays
$months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$revenueChartData = array_fill(0, 12, 0);
$usersChartData = array_fill(0, 12, 0);

foreach ($revenueData as $row) {
    $revenueChartData[$row['month'] - 1] = (float)$row['total'];
}
foreach ($userData as $row) {
    $usersChartData[$row['month'] - 1] = (int)$row['total'];
}

// ✅ Recent activity (last 5 actions)
$recentSql = "
    SELECT 'User' AS type, full_name AS name, created_at AS date, '' AS extra FROM customers
    UNION ALL
    SELECT 'Provider', full_name, created_at, '' FROM providers
    UNION ALL
    SELECT 'Payment', CONCAT('Paid $', p.amount), p.payment_date, pl.plan_name AS extra
    FROM payments p
    LEFT JOIN plans pl ON p.plan_id = pl.id
    UNION ALL
    SELECT 'Plan', plan_name, created_at, plan_status FROM plans
    ORDER BY date DESC
    LIMIT 5
";

$recentActivity = $pdo->query($recentSql)->fetchAll(PDO::FETCH_ASSOC);

// ✅ Helper function
function formatCurrency($value) {
    return number_format((float)$value, 2, '.', ',');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>

<!-- ✅ CSS & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">

<style>
body { background-color: #f8f9fa; }
.card-hover:hover { transform: translateY(-4px); transition: 0.3s; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
.glow:hover { box-shadow: 0 0 15px rgba(13,110,253,0.6); }
.profile-bubble { display:inline-flex; width:36px; height:36px; border-radius:50%; align-items:center; justify-content:center; color:#fff; font-weight:600; margin-right:8px; }
.badge-status { float:right; }
.recent-list li { padding:12px 10px; border-bottom:1px solid #eee; }
.counter { font-weight:700; font-size:1.75rem; }
.card-title-sm { font-weight:600; font-size:1rem; }
</style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <?php require 'sidebar.php'; ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
      
      <!-- 🧭 Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?>!</h2>
        <span class="text-muted"><?= date("l, d M Y") ?></span>
      </div>

      <!-- 📊 Stat Cards -->
      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="card bg-primary text-white card-hover">
            <div class="card-body d-flex justify-content-between align-items-center">
              <div>
                <h6>Total Users</h6>
                <h2 class="counter" data-target="<?= $total_users ?>"><?= $total_users ?></h2>
              </div>
              <i class="fas fa-users fa-2x"></i>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card bg-success text-white card-hover">
            <div class="card-body d-flex justify-content-between align-items-center">
              <div>
                <h6>Total Providers</h6>
                <h2 class="counter" data-target="<?= $total_providers ?>"><?= $total_providers ?></h2>
              </div>
              <i class="fas fa-user-tie fa-2x"></i>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card bg-warning text-white card-hover">
            <div class="card-body d-flex justify-content-between align-items-center">
              <div>
                <h6>Total Services</h6>
                <h2 class="counter" data-target="<?= $total_services ?>"><?= $total_services ?></h2>
              </div>
              <i class="fas fa-briefcase fa-2x"></i>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card bg-danger text-white card-hover">
            <div class="card-body d-flex justify-content-between align-items-center">
              <div>
                <h6>Total Revenue</h6>
                <h2 class="counter" data-target="<?= $total_revenue ?>"><?= formatCurrency($total_revenue) ?></h2>
              </div>
              <i class="fas fa-dollar-sign fa-2x"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- ⚙️ Quick Actions -->
      <div class="card mb-4 card-hover">
        <div class="card-body d-flex flex-wrap gap-3">
          <a href="manage_plans.php" class="btn btn-light glow"><i class="fas fa-cogs me-2"></i>Manage Plans</a>
          <a href="admin_profile.php" class="btn btn-light glow"><i class="fas fa-user-cog me-2"></i>Profile</a>
          <a href="payments.php" class="btn btn-light glow"><i class="fas fa-credit-card me-2"></i>Payments</a>
          <a href="manage_users.php" class="btn btn-light glow"><i class="fas fa-user-friends me-2"></i>Users</a>
          <a href="manage_providers.php" class="btn btn-light glow"><i class="fas fa-handshake me-2"></i>Providers</a>
        </div>
      </div>

      <!-- 🕒 Recent Activity -->
      <div class="card card-hover mb-4">
        <div class="card-body">
          <h5 class="mb-3 fw-semibold"><i class="fas fa-bolt me-2 text-primary"></i>Recent Activity</h5>
          <ul class="list-group list-group-flush recent-list">
            <?php foreach($recentActivity as $act): 
              $initial = strtoupper(substr($act['name'],0,1));
              $badgeClass = match($act['type']){
                'User' => 'bg-primary',
                'Provider' => 'bg-success',
                'Payment' => 'bg-warning text-dark',
                'Plan' => 'bg-danger',
                default => 'bg-secondary'
              };
            ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center">
                <span class="profile-bubble <?= $badgeClass ?>"><?= $initial ?></span>
                <div>
                  <?php if($act['type']=='Payment'): ?>
                    <?= htmlspecialchars($act['name']) ?> <small>for plan <?= htmlspecialchars($act['extra']) ?></small>
                  <?php elseif($act['type']=='Plan'): ?>
                    Plan <strong><?= htmlspecialchars($act['name']) ?></strong> updated (<span class="text-capitalize"><?= htmlspecialchars($act['extra']) ?></span>)
                  <?php else: ?>
                    <?= $act['type'] ?> <strong><?= htmlspecialchars($act['name']) ?></strong> registered.
                  <?php endif; ?>
                  <div class="text-muted small"><?= date('d M Y, h:i A', strtotime($act['date'])) ?></div>
                </div>
              </div>
              <span class="badge <?= $badgeClass ?> badge-status"><?= $act['type']=='Payment'?'Completed':($act['type']=='Plan'?'Updated':'New') ?></span>
            </li>
            <?php endforeach; ?>
            <?php if(empty($recentActivity)): ?>
              <li class="list-group-item text-center text-muted">No recent activity.</li>
            <?php endif; ?>
          </ul>
        </div>
      </div>

      <!-- 📈 Charts -->
      <div class="row mt-4">
        <div class="col-md-6 mb-3">
          <div class="card card-hover">
            <div class="card-body">
              <h5 class="card-title-sm mb-3 text-danger"><i class="fas fa-chart-line me-2"></i>Monthly Revenue</h5>
              <canvas id="revenueChart"></canvas>
            </div>
          </div>
        </div>
        <div class="col-md-6 mb-3">
          <div class="card card-hover">
            <div class="card-body">
              <h5 class="card-title-sm mb-3 text-primary"><i class="fas fa-user-plus me-2"></i>New Users</h5>
              <canvas id="usersChart"></canvas>
            </div>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- ✅ JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// 🧮 Counter Animation
document.querySelectorAll('.counter').forEach(counter => {
  const target = +counter.getAttribute('data-target');
  const step = Math.ceil(target / 100);
  const update = () => {
    const value = +counter.innerText.replace(/,/g, '') || 0;
    if (value < target) {
      counter.innerText = (value + step).toLocaleString();
      setTimeout(update, 20);
    } else {
      counter.innerText = target.toLocaleString();
    }
  };
  update();
});

// 📊 Revenue Chart
new Chart(document.getElementById('revenueChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($months) ?>,
    datasets: [{
      label: 'Revenue ($)',
      data: <?= json_encode($revenueChartData) ?>,
      borderColor: '#dc3545',
      backgroundColor: 'rgba(220,53,69,0.2)',
      fill: true,
      tension: 0.4
    }]
  },
  options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

// 📊 Users Chart
new Chart(document.getElementById('usersChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($months) ?>,
    datasets: [{
      label: 'New Users',
      data: <?= json_encode($usersChartData) ?>,
      backgroundColor: '#0d6efd'
    }]
  },
  options: { responsive: true, scales: { y: { beginAtZero: true } } }
});
</script>
</body>
</html>

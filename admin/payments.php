<?php
session_start();
require_once "../config/db.php";

// Admin session check
if(!isset($_SESSION['role']) || $_SESSION['role']!=='admin'){
    header("Location: ../login.php");
    exit;
}

// Fetch all payments
$payments = $pdo->query("
    SELECT pay.*, p.full_name AS provider_name, pay.plan_id
    FROM payments pay
    JOIN providers p ON pay.provider_id = p.id
    ORDER BY pay.payment_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for chart (total amount per provider)
$chartData = [];
foreach($payments as $pay){
    $chartData[$pay['provider_name']] = ($chartData[$pay['provider_name']] ?? 0) + $pay['amount'];
}

$chartLabels = json_encode(array_keys($chartData));
$chartValues = json_encode(array_values($chartData));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Payments</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php require 'sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <h2 class="mb-4">Payments</h2>
            
            <!-- Payment Chart -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Payments Overview</h5>
                    <canvas id="paymentsChart" height="100"></canvas>
                </div>
            </div>

            <!-- Payments Table -->
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Provider</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($payments): ?>
                        <?php foreach($payments as $index => $p): ?>
                        <tr>
                            <td><?= $index+1 ?></td>
                            <td><?= htmlspecialchars($p['provider_name']) ?></td>
                            <td><?= htmlspecialchars($p['plan_name']) ?></td>
                            <td>$<?= number_format($p['amount'],2) ?></td>
                            <td>
                                <span class="badge <?= $p['status']=='Paid'?'bg-success':'bg-danger' ?>">
                                    <?= htmlspecialchars($p['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($p['payment_date']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">No payments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('paymentsChart').getContext('2d');
const paymentsChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= $chartLabels ?>,
        datasets: [{
            label: 'Payments ($)',
            data: <?= $chartValues ?>,
            backgroundColor: '#0d6efd'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>
</body>
</html>

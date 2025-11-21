<?php
ob_start();
session_start();
date_default_timezone_set('Asia/Kolkata');
require_once "../config/db.php";

// Session check
if(!isset($_SESSION['role']) || $_SESSION['role']!=='provider'){
    header("Location: ../login.php");
    exit;
}

$provider_id = $_SESSION['user_id'];
$msg = "";

// Fetch provider current subscription
$stmt = $pdo->prepare("SELECT p.id as plan_id, p.plan_name, p.max_services, p.plan_price, pr.plan_expiry, pr.email
                       FROM providers pr
                       LEFT JOIN plans p ON pr.plan_id=p.id
                       WHERE pr.id=:pid");
$stmt->execute(['pid'=>$provider_id]);
$providerPlan = $stmt->fetch(PDO::FETCH_ASSOC);

$current_date = new DateTime();
$expiry_date = $providerPlan['plan_expiry'] ? new DateTime($providerPlan['plan_expiry']) : null;
$remaining_days = $expiry_date ? max(0, $current_date->diff($expiry_date)->days + 1) : 0;
$plan_expired = ($expiry_date && $current_date > $expiry_date) || !$providerPlan['plan_expiry'];

// Email reminder if plan expiring in 3 days
if($remaining_days > 0 && $remaining_days <= 3){
    $to = $providerPlan['email'];
    $subject = "Subscription Plan Expiry Reminder";
    $message = "Dear Provider, your subscription plan '".$providerPlan['plan_name']."' will expire in $remaining_days days. Please renew it.";
    $headers = "From: admin@example.com\r\n";
    // mail($to,$subject,$message,$headers); // Uncomment to send
}

// Fetch all plans
$plans = $pdo->query("SELECT * FROM plans ORDER BY plan_price ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle plan purchase/renew
if(isset($_GET['plan_id'])){
    $plan_id = intval($_GET['plan_id']);
    $stmt = $pdo->prepare("SELECT * FROM plans WHERE id=:id");
    $stmt->execute(['id'=>$plan_id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    if($plan){
        $new_start = new DateTime('today');
        $new_expiry = (clone $new_start)->modify("+".($plan['plan_days'] ?? 30 -1)." days"); // include start day

        $stmt = $pdo->prepare("UPDATE providers SET plan_id=:plan_id, plan_expiry=:expiry WHERE id=:pid");
        $stmt->execute(['plan_id'=>$plan_id,'expiry'=>$new_expiry->format('Y-m-d'),'pid'=>$provider_id]);

        $msg = "Plan updated to ".$plan['plan_name'].". Expiry: ".$new_expiry->format('Y-m-d');
        $providerPlan['plan_name'] = $plan['plan_name'];
        $providerPlan['plan_expiry'] = $new_expiry->format('Y-m-d');
        $remaining_days = $new_expiry->diff($current_date)->days + 1;
        $plan_expired = false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Provider Subscription</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
<style>
.card-hover:hover { transform: translateY(-4px); transition: 0.3s; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.badge-status { float: right; }
.card-header { font-weight: 600; }
</style>
</head>
<body>
<div class="container-fluid">
<div class="row">
    <?php $page='subscription'; require 'sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

        <h2 class="mb-4">Subscription Plans</h2>
        <?php if($msg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="row mb-4">
            <?php foreach($plans as $plan): ?>
            <div class="col-md-6">
                <div class="card border-primary shadow card-hover mb-3">
                    <div class="card-header <?= ($plan['id']==$providerPlan['plan_id'] && !$plan_expired) ? 'bg-success text-white' : 'bg-primary text-white' ?>">
                        <?= htmlspecialchars($plan['plan_name']) ?>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">$<?= htmlspecialchars($plan['plan_price']) ?>/month</h5>
                        <ul class="list-unstyled mb-3">
                            <li><i class="fas fa-check text-success me-2"></i>List up to <?= htmlspecialchars($plan['max_services']) ?> services</li>
                            <li><i class="fas fa-check text-success me-2"></i>Email support</li>
                            <li><i class="fas fa-check text-success me-2"></i>Priority visibility</li>
                        </ul>
                        <?php if($plan['id']==$providerPlan['plan_id'] && !$plan_expired): ?>
                            <button class="btn btn-success w-100" disabled>Current Plan</button>
                        <?php elseif($plan_expired): ?>
                            <a href="?plan_id=<?= $plan['id'] ?>" class="btn btn-primary w-100">Buy/Renew Plan</a>
                        <?php else: ?>
                            <button class="btn btn-outline-secondary w-100" disabled>Other Plan</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <h4 class="mt-4">Current Subscription</h4>
        <?php if($providerPlan && $providerPlan['plan_name']): ?>
            <div class="alert <?= $plan_expired ? 'alert-danger' : 'alert-info' ?>">
                You are currently on the <strong><?= htmlspecialchars($providerPlan['plan_name']) ?></strong> plan.</br> 
                <strong>Expiry:</strong> <?= htmlspecialchars($providerPlan['plan_expiry']) ?>.</br>
                <?php if(!$plan_expired): ?>
                    <strong>Remaining days:</strong> <?= $remaining_days ?>
                <?php else: ?>
                    <a href="?plan_id=<?= $providerPlan['plan_id'] ?>" class="btn btn-sm btn-primary ms-2">Renew Plan</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">You currently have no subscription. <a href="?plan_id=1" class="btn btn-sm btn-primary">Choose Plan</a></div>
        <?php endif; ?>

    </main>
</div>
</div>
</body>
</html>

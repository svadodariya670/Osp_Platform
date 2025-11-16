<?php
session_start();
require_once "../config/db.php";

// Admin session check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$msg = "";

/* --------------------------------------------
   Flash Message
-------------------------------------------- */
if (isset($_SESSION['flash_msg'])) {
    $msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

// Handle Add / Update Plan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_id = $_POST['plan_id'] ?? null;
    $name = trim($_POST['plan_name']);
    $price = trim($_POST['plan_price']);
    $max_services = intval($_POST['max_services']);
    $days = intval($_POST['plan_days']);
    $status = $_POST['plan_status'] ?? 'inactive';

    if (empty($name) || $price < 0 || $max_services < 0 || $days <= 0) {
        $_SESSION['flash_msg'] = "❌ Please enter valid plan details.";
    } else {
        if ($plan_id) {
            $stmt = $pdo->prepare("UPDATE plans 
                SET plan_name=:name, plan_price=:price, max_services=:max, plan_days=:days, plan_status=:status 
                WHERE id=:id");
            $stmt->execute([
                'name' => $name,
                'price' => $price,
                'max' => $max_services,
                'days' => $days,
                'status' => $status,
                'id' => $plan_id
            ]);
            $_SESSION['flash_msg'] = "✅ Plan updated successfully.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO plans (plan_name, plan_price, max_services, plan_days, plan_status, created_at) 
                                   VALUES (:name, :price, :max, :days, :status, NOW())");
            $stmt->execute([
                'name' => $name,
                'price' => $price,
                'max' => $max_services,
                'days' => $days,
                'status' => $status
            ]);
            $_SESSION['flash_msg'] = "✅ New plan added successfully.";
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: manage_plan.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM plans WHERE id=:id");
    $stmt->execute(['id' => $_GET['delete_id']]);
    $_SESSION['flash_msg'] = "🗑️ Plan deleted successfully.";
    
    // Redirect to prevent resubmission
    header("Location: manage_plan.php");
    exit;
}

// Handle Status Toggle
if (isset($_GET['toggle_id'])) {
    $id = $_GET['toggle_id'];
    $stmt = $pdo->prepare("SELECT plan_status FROM plans WHERE id=:id");
    $stmt->execute(['id' => $id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($plan) {
        $new_status = ($plan['plan_status'] === 'active') ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE plans SET plan_status=:status WHERE id=:id");
        $stmt->execute(['status' => $new_status, 'id' => $id]);
        $_SESSION['flash_msg'] = "🔁 Plan status updated to <b>" . ucfirst($new_status) . "</b>.";
        
        // Redirect to prevent resubmission
        header("Location: manage_plan.php");
        exit;
    }
}

// Fetch all plans
$plans = $pdo->query("SELECT * FROM plans ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Plans</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
<style>
    .plan-card {
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    .plan-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 18px rgba(0,0,0,0.2);
    }
    .add-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        border-radius: 50%;
        width: 60px;
        height: 60px;
        font-size: 26px;
    }
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php require 'sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Manage Plans</h2>
                <button class="btn btn-primary add-btn shadow" data-bs-toggle="modal" data-bs-target="#planModal">
                    <i class="fas fa-plus"></i>
                </button>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-info alert-dismissible fade show"><?= $msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <?php if ($plans): ?>
                    <?php foreach ($plans as $p): ?>
                        <div class="col-md-4">
                            <div class="card plan-card border-<?= $p['plan_status']=='active'?'success':'danger' ?>">
                                <div class="card-body">
                                    <h5 class="card-title text-primary"><?= htmlspecialchars($p['plan_name']) ?></h5>
                                    <p class="card-text mb-1">
                                        <strong>Price:</strong> $<?= number_format($p['plan_price'], 2) ?><br>
                                        <strong>Max Services:</strong> <?= $p['max_services'] ?><br>
                                        <strong>Duration:</strong> <?= $p['plan_days'] ?> days<br>
                                        <strong>Status:</strong> 
                                        <span class="badge <?= $p['plan_status']=='active'?'bg-success':'bg-danger' ?>">
                                            <?= ucfirst($p['plan_status']) ?>
                                        </span>
                                    </p>
                                    <div class="mt-3 d-flex justify-content-between">
                                        <a href="?toggle_id=<?= $p['id'] ?>" 
                                           class="btn btn-sm <?= $p['plan_status']=='active'?'btn-warning':'btn-success' ?>">
                                           <?= $p['plan_status']=='active'?'Deactivate':'Activate' ?>
                                        </a>
                                        <button class="btn btn-sm btn-primary edit-btn"
                                            data-id="<?= $p['id'] ?>"
                                            data-name="<?= htmlspecialchars($p['plan_name']) ?>"
                                            data-price="<?= $p['plan_price'] ?>"
                                            data-max="<?= $p['max_services'] ?>"
                                            data-days="<?= $p['plan_days'] ?>"
                                            data-status="<?= $p['plan_status'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete_id=<?= $p['id'] ?>" onclick="return confirm('Delete this plan?')" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-muted">No plans available.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Modal for Add/Edit Plan -->
<div class="modal fade" id="planModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title">Add / Edit Plan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="plan_id" id="plan_id">
            <div class="mb-3">
                <label class="form-label">Plan Name</label>
                <input type="text" class="form-control" id="plan_name" name="plan_name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Price ($)</label>
                <input type="number" class="form-control" id="plan_price" name="plan_price" min="0" step="0.01" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Max Services</label>
                <input type="number" class="form-control" id="max_services" name="max_services" min="0" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Plan Duration (days)</label>
                <input type="number" class="form-control" id="plan_days" name="plan_days" min="1" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select class="form-select" id="plan_status" name="plan_status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save Plan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function(){
    // Open modal with existing data for edit
    $('.edit-btn').click(function(){
        $('#plan_id').val($(this).data('id'));
        $('#plan_name').val($(this).data('name'));
        $('#plan_price').val($(this).data('price'));
        $('#max_services').val($(this).data('max'));
        $('#plan_days').val($(this).data('days'));
        $('#plan_status').val($(this).data('status'));
        $('#planModal').modal('show');
    });
    
    // Clear modal when opening for new plan
    $('#planModal').on('show.bs.modal', function (e) {
        if (!$(e.relatedTarget).hasClass('edit-btn')) {
            $('#plan_id').val('');
            $('#plan_name').val('');
            $('#plan_price').val('');
            $('#max_services').val('');
            $('#plan_days').val('');
            $('#plan_status').val('active');
        }
    });
});
</script>
</body>
</html>
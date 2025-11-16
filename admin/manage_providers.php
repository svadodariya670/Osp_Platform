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

/* --------------------------------------------
   Handle Provider Activation / Deactivation
-------------------------------------------- */
if (isset($_GET['toggle_id'])) {
    $id = intval($_GET['toggle_id']);
    $stmt = $pdo->prepare("SELECT status FROM providers WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $provider = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($provider) {
        $new_status = ($provider['status'] === 'Active') ? 'Inactive' : 'Active';
        $stmt = $pdo->prepare("UPDATE providers SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $new_status, 'id' => $id]);
        $_SESSION['flash_msg'] = "Provider status updated to $new_status.";
        
        // Preserve search and filter parameters
        $preserved_params = [];
        if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
            $preserved_params['search'] = trim($_GET['search']);
        }
        if (isset($_GET['filter']) && !empty($_GET['filter'])) {
            $preserved_params['filter'] = $_GET['filter'];
        }
        
        // Redirect to preserve parameters and prevent form resubmission
        if (!empty($preserved_params)) {
            $query_string = http_build_query($preserved_params);
            header("Location: manage_providers.php?" . $query_string);
            exit;
        } else {
            header("Location: manage_providers.php");
            exit;
        }
    }
}

/* --------------------------------------------
   Handle Search & Filter
-------------------------------------------- */
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? '';

$query = "
SELECT p.*, pl.plan_name, pl.plan_days, pl.plan_price
FROM providers p
LEFT JOIN plans pl ON pl.id = p.plan_id
WHERE 1
";
$params = [];

if ($search) {
    $query .= " AND (p.full_name LIKE :s1 OR p.email LIKE :s2 OR p.phone LIKE :s3 OR pl.plan_name LIKE :s4)";
    $params['s1'] = "%$search%";
    $params['s2'] = "%$search%";
    $params['s3'] = "%$search%";
    $params['s4'] = "%$search%";
}

if ($filter && in_array($filter, ['Active', 'Inactive'])) {
    $query .= " AND p.status = :status";
    $params['status'] = $filter;
}

$query .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Providers</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
<style>
    body { background-color: #f8f9fa; }
    .table th { background-color: #0d6efd; color: #fff; }
    .search-bar input, .search-bar select { border-radius: 10px; }
    .badge-status { font-size: 0.85rem; }
    .provider-card { transition: all 0.3s; }
    .provider-card:hover { transform: translateY(-4px); box-shadow: 0 4px 15px rgba(0,0,0,0.15); }
    .expired { color: #dc3545; font-weight: 600; }
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php require 'sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Manage Providers</h2>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Search and Filter -->
            <form method="get" class="row g-3 align-items-center mb-4 search-bar">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone, or plan" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="filter" class="form-select">
                        <option value="">All Status</option>
                        <option value="Active" <?= $filter == 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $filter == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Search</button>
                </div>
                <div class="col-md-2">
                    <a href="manage_providers.php" class="btn btn-secondary w-100"><i class="fas fa-sync-alt"></i> Reset</a>
                </div>
            </form>

            <!-- Providers Table -->
            <div class="card shadow-sm provider-card">
                <div class="card-body table-responsive">
                <table class="table table-hover align-middle custom-table">
                        <thead >
                            <tr>
                                <th ></th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Current Plan</th>
                                <th>Plan Price</th>
                                <th>Plan Expiry</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($providers): ?>
                                <?php foreach ($providers as $index => $provider): 
                                    $expiry = $provider['plan_expiry'] ? date('d M Y', strtotime($provider['plan_expiry'])) : '—';
                                    $isExpired = ($provider['plan_expiry'] && strtotime($provider['plan_expiry']) < time());
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($provider['full_name']) ?></td>
                                    <td><?= htmlspecialchars($provider['email']) ?></td>
                                    <td><?= htmlspecialchars($provider['phone']) ?></td>
                                    <td>
                                        <span class="badge <?= $provider['status'] == 'Active' ? 'bg-success' : 'bg-danger' ?> badge-status">
                                            <?= htmlspecialchars($provider['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $provider['plan_name'] 
                                            ? '<span class="badge bg-info">' . htmlspecialchars($provider['plan_name']) . '</span>' 
                                            : '<span class="text-muted">No Plan</span>' ?>
                                    </td>
                                    <td>
                                        <?= $provider['plan_price'] 
                                            ? '$' . number_format($provider['plan_price'], 2) 
                                            : '—' ?>
                                    </td>
                                    <td class="<?= $isExpired ? 'expired' : '' ?>">
                                        <?= $provider['plan_expiry'] 
                                            ? ($isExpired ? "$expiry (Expired)" : $expiry)
                                            : '—' ?>
                                    </td>
                                    <td>
                                        <?php
                                        // Build URL with preserved parameters
                                        $toggle_url = "?toggle_id=" . $provider['id'];
                                        if (!empty($search)) {
                                            $toggle_url .= "&search=" . urlencode($search);
                                        }
                                        if (!empty($filter)) {
                                            $toggle_url .= "&filter=" . urlencode($filter);
                                        }
                                        ?>
                                        <a href="<?= $toggle_url ?>" 
                                           class="btn btn-sm <?= $provider['status'] == 'Active' ? 'btn-danger' : 'btn-success' ?>">
                                            <i class="fas <?= $provider['status'] == 'Active' ? 'fa-ban' : 'fa-check' ?>"></i>
                                            <?= $provider['status'] == 'Active' ? 'Deactivate' : 'Activate' ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="9" class="text-center py-4">No providers found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
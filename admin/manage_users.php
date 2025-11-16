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
   Handle Block / Unblock (Active <-> Inactive)
-------------------------------------------- */
if (isset($_GET['toggle_id'])) {
    $id = intval($_GET['toggle_id']);
    $stmt = $pdo->prepare("SELECT status FROM customers WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $current_status = trim($user['status']);
        $new_status = ($current_status === 'Active') ? 'Inactive' : 'Active';
        $stmt = $pdo->prepare("UPDATE customers SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $new_status, 'id' => $id]);
        $_SESSION['flash_msg'] = "✅ User status updated to <b>$new_status</b>.";
        
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
            header("Location: manage_users.php?" . $query_string);
            exit;
        } else {
            header("Location: manage_users.php");
            exit;
        }
    }
}

/* --------------------------------------------
   Handle Delete
-------------------------------------------- */
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $pdo->prepare("DELETE FROM customers WHERE id = :id")->execute(['id' => $id]);
    $_SESSION['flash_msg'] = "🗑️ User deleted successfully.";
    
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
        header("Location: manage_users.php?" . $query_string);
        exit;
    } else {
        header("Location: manage_users.php");
        exit;
    }
}

/* --------------------------------------------
   Handle Search & Filter
-------------------------------------------- */
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? '';

$query = "SELECT * FROM customers WHERE 1";
$params = [];

if ($search) {
    $query .= " AND (full_name LIKE :s1 OR email LIKE :s2 OR phone LIKE :s3)";
    $params['s1'] = "%$search%";
    $params['s2'] = "%$search%";
    $params['s3'] = "%$search%";
}

if ($filter && in_array($filter, ['Active', 'Inactive'])) {
    $query .= " AND status = :status";
    $params['status'] = $filter;
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .table th {
            background-color: #0d6efd;
            color: #fff;
        }

        .action-btn {
            border-radius: 25px;
        }

        .search-bar {
            background: #fff;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <?php require 'sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">Manage Customers</h2>
                </div>

                <?php if ($msg): ?>
                    <div class="alert alert-info alert-dismissible fade show"><?= $msg ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Search & Filter -->
                <form method="get" class="row g-3 align-items-center mb-4 search-bar">
                    <div class="col-md-5">
                        <input type="text" name="search" class="form-control" placeholder="Search by name, email, or phone"
                            value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="filter" class="form-select">
                            <option value="">All Status</option>
                            <option value="Active" <?= $filter == 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Inactive" <?= $filter == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="manage_users.php" class="btn btn-secondary w-100">
                            <i class="fas fa-sync-alt"></i> Reset
                        </a>
                    </div>
                </form>

                <!-- Users Table -->
                <div class="table-responsive shadow-sm">
                    <table class="table table-hover align-middle custom-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users): ?>
                                <?php foreach ($users as $i => $user): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= htmlspecialchars($user['phone']) ?></td>
                                        <td>
                                            <span class="badge <?= $user['status'] == 'Active' ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= htmlspecialchars($user['status'] ?: 'Inactive') ?>
                                            </span>
                                        </td>
                                        <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <?php
                                            // Build URL with preserved parameters
                                            $toggle_url = "?toggle_id=" . $user['id'];
                                            $delete_url = "?delete_id=" . $user['id'];
                                            
                                            if (!empty($search)) {
                                                $toggle_url .= "&search=" . urlencode($search);
                                                $delete_url .= "&search=" . urlencode($search);
                                            }
                                            if (!empty($filter)) {
                                                $toggle_url .= "&filter=" . urlencode($filter);
                                                $delete_url .= "&filter=" . urlencode($filter);
                                            }
                                            ?>
                                            <a href="<?= $toggle_url ?>"
                                                class="btn btn-sm <?= $user['status'] == 'Active' ? 'btn-warning' : 'btn-success' ?> action-btn">
                                                <i class="fas <?= $user['status'] == 'Active' ? 'fa-ban' : 'fa-check' ?>"></i>
                                                <?= $user['status'] == 'Active' ? 'Deactivate' : 'Activate' ?>
                                            </a>
                                            <a href="<?= $delete_url ?>"
                                                class="btn btn-sm btn-danger action-btn"
                                                onclick="return confirm('Are you sure you want to delete this user?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">No customers found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
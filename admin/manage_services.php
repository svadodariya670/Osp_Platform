<?php
session_start();
require_once "../config/db.php";

// Session check
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'provider'])) {
    header("Location: ../login.php");
    exit;
}

$role = $_SESSION['role'];
$user_id = intval($_SESSION['user_id'] ?? 0);
$msg = "";

/* --------------------------------------------
   Handle Toggle Status (Admin only)
-------------------------------------------- */
if (isset($_GET['toggle_id']) && $role === 'admin') {
    $id = intval($_GET['toggle_id']);

    $stmt = $pdo->prepare("SELECT status FROM services WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($service) {
        $current = strtolower(trim($service['status']));
        $new_status = ($current === 'active') ? 'Inactive' : 'Active';

        $update = $pdo->prepare("UPDATE services SET status = :status WHERE id = :id");
        $update->execute(['status' => $new_status, 'id' => $id]);

        $_SESSION['flash_msg'] = "✅ Service status successfully changed to <b>$new_status</b>.";
        
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
            header("Location: manage_services.php?" . $query_string);
            exit;
        } else {
            header("Location: manage_services.php");
            exit;
        }
    }
}

/* --------------------------------------------
   Handle Delete (Admin or Provider)
-------------------------------------------- */
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    if ($role === 'admin') {
        $pdo->prepare("DELETE FROM services WHERE id = :id")->execute(['id' => $id]);
        $_SESSION['flash_msg'] = "🗑️ Service deleted successfully.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = :id AND provider_id = :pid");
        $stmt->execute(['id' => $id, 'pid' => $user_id]);
        $_SESSION['flash_msg'] = "🗑️ Service deleted successfully.";
    }
    
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
        header("Location: manage_services.php?" . $query_string);
        exit;
    } else {
        header("Location: manage_services.php");
        exit;
    }
}

/* --------------------------------------------
   Flash Message
-------------------------------------------- */
if (isset($_SESSION['flash_msg'])) {
    $msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

/* --------------------------------------------
   Search + Filter
-------------------------------------------- */
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? '';

$query = "
    SELECT s.*, 
           p.full_name AS provider_name, 
           c.category_name
    FROM services s
    LEFT JOIN providers p ON s.provider_id = p.id
    LEFT JOIN categories c ON s.category_id = c.id
    WHERE 1
";
$params = [];

if ($role === 'provider') {
    $query .= " AND s.provider_id = :pid";
    $params['pid'] = $user_id;
}

if ($search !== '') {
    $query .= " AND (s.title LIKE :s1 OR c.category_name LIKE :s2 OR p.full_name LIKE :s3)";
    $params['s1'] = "%$search%";
    $params['s2'] = "%$search%";
    $params['s3'] = "%$search%";
}

if ($filter && in_array($filter, ['Active', 'Inactive'])) {
    $query .= " AND s.status = :status";
    $params['status'] = $filter;
}

$query .= " ORDER BY s.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h($v)
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Services</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .search-bar {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .card-service {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: 0.3s;
        }

        .card-service:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .badge-status {
            font-size: 0.85rem;
        }

        .card-img-top {
            height: 180px;
            object-fit: cover;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }

        .opacity-inactive {
            opacity: 0.75;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <?php require 'sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-semibold">Manage Services</h2>
                </div>

                <?php if ($msg): ?>
                    <div class="alert alert-success alert-dismissible fade show"><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <!-- Search & Filter -->
                <form method="get" class="row g-3 align-items-center mb-4 search-bar">
                    <div class="col-md-5">
                        <input type="text" name="search" value="<?= h($search) ?>" class="form-control" placeholder="Search by service name, category, or provider">
                    </div>
                    <div class="col-md-3">
                        <select name="filter" class="form-select">
                            <option value="">All Status</option>
                            <option value="Active" <?= $filter == 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Inactive" <?= $filter == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Search</button>
                    </div>
                    <div class="col-md-2">
                        <a href="manage_services.php" class="btn btn-secondary w-100"><i class="fas fa-sync-alt"></i> Reset</a>
                    </div>
                </form>

                <!-- Service Cards -->
                <div class="row g-4">
                    <?php if ($services): ?>
                        <?php foreach ($services as $s): ?>
                            <div class="col-md-4 col-lg-3">
                                <div class="card card-service <?= strtolower(trim($s['status'])) === 'inactive' ? 'opacity-inactive' : '' ?>">
                                    <?php
                                    $imgPath = !empty($s['image']) ? "../assets/img/services/" . h($s['image']) : "../assets/img/default-service.jpg";
                                    ?>
                                    <img src="<?= $imgPath ?>" class="card-img-top" alt="<?= h($s['title']) ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= h($s['title']) ?></h5>
                                        <p class="text-muted mb-1"><i class="fas fa-layer-group me-1"></i><?= h($s['category_name']) ?></p>
                                        <p class="text-muted mb-1"><i class="fas fa-user me-1"></i><?= h($s['provider_name']) ?></p>
                                        <span class="badge <?= strtolower(trim($s['status'])) === 'active' ? 'bg-success' : 'bg-secondary' ?> badge-status"><?= ucfirst(h($s['status'])) ?></span>

                                        <div class="mt-3 d-flex flex-wrap gap-2">
                                            <?php if ($role === 'admin'): ?>
                                                <?php
                                                // Build URL with preserved parameters for toggle
                                                $toggle_url = "?toggle_id=" . $s['id'];
                                                if (!empty($search)) {
                                                    $toggle_url .= "&search=" . urlencode($search);
                                                }
                                                if (!empty($filter)) {
                                                    $toggle_url .= "&filter=" . urlencode($filter);
                                                }
                                                ?>
                                                <?php if (strtolower(trim($s['status'])) === 'active'): ?>
                                                    <a href="<?= $toggle_url ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-ban"></i> Deactivate
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?= $toggle_url ?>" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i> Activate
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <?php
                                            // Build URL with preserved parameters for delete
                                            $delete_url = "?delete_id=" . $s['id'];
                                            if (!empty($search)) {
                                                $delete_url .= "&search=" . urlencode($search);
                                            }
                                            if (!empty($filter)) {
                                                $delete_url .= "&filter=" . urlencode($filter);
                                            }
                                            ?>
                                            <a href="<?= $delete_url ?>" onclick="return confirm('Are you sure you want to delete this service?')" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center text-muted py-5">
                            <i class="fas fa-info-circle fa-2x mb-2"></i>
                            <p>No services found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html> 
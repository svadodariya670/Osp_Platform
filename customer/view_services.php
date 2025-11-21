<?php
session_start();
require_once "../config/db.php";

// ===============================
// Session Check
// ===============================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

// ===============================
// Fetch Active Categories
// ===============================
$catStmt = $pdo->query("SELECT id, category_name FROM categories ORDER BY category_name ASC");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// ===============================
// Handle Filters
// ===============================
$search   = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? 'all');

// ===============================
// Fetch Active Services Only
// ===============================
$sql = "
    SELECT 
        s.id, 
        s.title, 
        s.description, 
        s.image, 
        c.category_name
    FROM services s
    JOIN providers p ON s.provider_id = p.id
    JOIN categories c ON s.category_id = c.id
    WHERE s.status = 'Active' 
      AND p.status = 'Active'
";

$params = [];

if ($search !== '') {
    $sql .= " AND (s.title LIKE :s1 OR s.description LIKE :s2)";
        
    $params['s1'] = "%$search%";
    $params['s2'] = "%$search%";
    
}

if ($category !== 'all') {
    $sql .= " AND c.id = :category_id";
    $params['category_id'] = $category;
}

$sql .= " ORDER BY s.title ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Available Services</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
<style>
body { background-color: #f8f9fa; }
.card-custom {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.3s, box-shadow 0.3s;
}
.card-custom:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}
.service-img {
    height: 200px;
    width: 100%;
    object-fit: cover;
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
}
.category-filter .btn {
    border-radius: 25px;
}
.search-bar {
    background: #fff;
    padding: 15px;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php require 'sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

            <h2 class="mb-4 fw-semibold">Available Services</h2>

            <!-- Search & Filter -->
            <div class="search-bar mb-4">
                <form method="get" class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <input type="text" name="search" class="form-control" placeholder="Search by service name or description..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="category" class="form-select">
                            <option value="all">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($category == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-50"><i class="fas fa-search me-1"></i> Search</button>
                        <a href="view_services.php" class="btn btn-secondary w-50"><i class="fas fa-sync-alt me-1"></i> Reset</a>
                    </div>
                </form>
            </div>

            <!-- Service Cards -->
            <div class="row">
                <?php if (!empty($services)): ?>
                    <?php foreach ($services as $s): 
                        $imgPath = "../assets/img/services/" . htmlspecialchars($s['image']);
                        if (!file_exists($imgPath) || empty($s['image'])) {
                            $imgPath = "../assets/img/default-service.jpg";
                        }
                    ?>
                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card card-custom h-100">
                                <img src="<?= $imgPath ?>" class="service-img" alt="<?= htmlspecialchars($s['title']) ?>">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= htmlspecialchars($s['title']) ?></h5>
                                    <p class="card-text text-muted small"><?= htmlspecialchars(substr($s['description'], 0, 80)) ?>...</p>
                                    <p class="mt-auto mb-2"><i class="fas fa-tag text-primary me-1"></i> <?= htmlspecialchars($s['category_name']) ?></p>
                                    <a href="service_details.php?id=<?= $s['id'] ?>" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-eye me-1"></i> View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center text-muted py-5">
                        <i class="fas fa-info-circle fa-2x mb-2"></i>
                        <p>No active services found.</p>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
ob_start();
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'provider') {
    header("Location: ../login.php");
    exit;
}

$provider_id = $_SESSION['user_id'];
$msg = "";
$error = "";

// Fetch provider plan
$stmt = $pdo->prepare("SELECT plan_id FROM providers WHERE id=:id");
$stmt->execute(['id' => $provider_id]);
$plan_id = $stmt->fetchColumn();

// Max services allowed by plan
$stmt = $pdo->prepare("SELECT max_services FROM plans WHERE id=:id");
$stmt->execute(['id' => $plan_id]);
$max_services = $stmt->fetchColumn();

// Fetch categories for dropdown
$categoriesStmt = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

// ------------------ Add Service ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $service_title = trim($_POST['service_title']);
    $service_category = trim($_POST['service_category']);
    $service_description = trim($_POST['service_description']);

    // Count existing services
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE provider_id=:provider_id");
    $stmt->execute(['provider_id' => $provider_id]);
    $service_count = $stmt->fetchColumn();

    if ($service_count >= $max_services) {
        $error = "You have reached the maximum number of services for your plan ($max_services). Upgrade your plan to add more.";
    } else {
        $uploadDir = "../assets/img/services/";
        $photos = ['photo1' => null, 'photo2' => null, 'photo3' => null];

        foreach ($photos as $key => $val) {
            if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES[$key]['tmp_name'];
                $fileName = basename($_FILES[$key]['name']);
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                    $error = "Invalid file type for $key. Only JPG/PNG allowed.";
                    break;
                }
                if ($_FILES[$key]['size'] > 2 * 1024 * 1024) {
                    $error = "$key exceeds maximum size 2MB.";
                    break;
                }
                $newFileName = uniqid() . '_' . $fileName;
                if (move_uploaded_file($fileTmpPath, $uploadDir . $newFileName)) {
                    $photos[$key] = $newFileName;
                } else {
                    $error = "Failed to upload $key.";
                    break;
                }
            } elseif ($key === 'photo1') {
                $error = "Primary photo is required.";
            }
        }

        if (!$error) {
            $imageColumn = implode(',', array_filter($photos));
            $stmt = $pdo->prepare("INSERT INTO services 
                (provider_id, category_id, title, description, image, status, created_at) 
                VALUES (:pid, :cat, :title, :desc, :img, 'active', NOW())");
            $stmt->execute([
                'pid' => $provider_id,
                'cat' => $service_category,
                'title' => $service_title,
                'desc' => $service_description,
                'img' => $imageColumn
            ]);
            $msg = "Service added successfully.";
        }
    }
}

// ------------------ Edit Service ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $service_id = intval($_POST['service_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = trim($_POST['category_id']);

    // Fetch old images
    $stmt = $pdo->prepare("SELECT image FROM services WHERE id=:id AND provider_id=:pid");
    $stmt->execute(['id' => $service_id, 'pid' => $provider_id]);
    $oldImages = $stmt->fetchColumn();
    $images = explode(',', $oldImages);

    // Handle new image upload -> go to second slot
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = basename($_FILES['image']['name']);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $newFileName = uniqid() . '_' . $fileName;
            $uploadDir = "../assets/img/services/";
            if (move_uploaded_file($fileTmpPath, $uploadDir . $newFileName)) {
                // Add/replace second image slot
                if (count($images) < 2) {
                    $images[1] = $newFileName;
                } else {
                    $images[1] = $newFileName;
                }
            }
        }
    }
    $imageColumn = implode(',', array_filter($images));

    $stmt = $pdo->prepare("UPDATE services SET title=:title, description=:desc, category_id=:cat, image=:img WHERE id=:id AND provider_id=:pid");
    $stmt->execute([
        'title' => $title,
        'desc' => $description,
        'cat' => $category_id,
        'img' => $imageColumn,
        'id' => $service_id,
        'pid' => $provider_id
    ]);
    $msg = "Service updated successfully.";
}

// ------------------ Delete / Toggle ------------------
if (isset($_GET['action'], $_GET['service_id'])) {
    $service_id = intval($_GET['service_id']);
    $action = $_GET['action'];
    if ($action === 'delete') {
        // Delete images from folder
        $stmt = $pdo->prepare("SELECT image FROM services WHERE id=:id AND provider_id=:pid");
        $stmt->execute(['id' => $service_id, 'pid' => $provider_id]);
        $images = $stmt->fetchColumn();
        if ($images) {
            foreach (explode(',', $images) as $img) {
                $imgPath = "../assets/img/services/" . $img;
                if (file_exists($imgPath)) unlink($imgPath);
            }
        }
        $stmt = $pdo->prepare("DELETE FROM services WHERE id=:id AND provider_id=:pid");
        $stmt->execute(['id' => $service_id, 'pid' => $provider_id]);
        $msg = "Service deleted successfully.";
    } elseif ($action === 'toggle') {
        $stmt = $pdo->prepare("SELECT status FROM services WHERE id=:id AND provider_id=:pid");
        $stmt->execute(['id' => $service_id, 'pid' => $provider_id]);
        $status = $stmt->fetchColumn();
        $new_status = $status === 'active' ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE services SET status=:status WHERE id=:id AND provider_id=:pid");
        $stmt->execute(['status' => $new_status, 'id' => $service_id, 'pid' => $provider_id]);
        $msg = "Service status updated to " . ucfirst($new_status) . ".";
    }
}

// ------------------ Fetch Services with Category ------------------
$stmt = $pdo->prepare("
    SELECT s.*, c.category_name 
    FROM services s 
    LEFT JOIN categories c ON s.category_id=c.id
    WHERE s.provider_id=:pid
    ORDER BY s.id DESC
");
$stmt->execute(['pid' => $provider_id]);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .card-hover:hover {
            transform: translateY(-4px);
            transition: 0.3s;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
        }

        .service-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 5px;
        }

        .service-img-small {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 5px;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <?php require 'sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

                <h2 class="mb-4">Manage Services</h2>
                <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
                <?php if ($msg) echo "<div class='alert alert-success'>$msg</div>"; ?>

                <button class="btn btn-success mb-4" data-bs-toggle="modal" data-bs-target="#addServiceModal"><i class="fas fa-plus"></i> Add Service</button>

                <div class="row">
                    <?php if ($services): ?>
                        <?php foreach ($services as $s):
                            $images = explode(',', $s['image']);
                            $badgeClass = $s['status'] == 'active' ? 'bg-success' : 'bg-danger';
                        ?>
                            <div class="col-md-6 mb-4">
                                <div class="card card-hover p-3">
                                    <div class="d-flex gap-3 flex-wrap align-items-center">
                                        <?php foreach ($images as $img): if ($img): ?>
                                                <img src="../assets/img/services/<?= htmlspecialchars($img) ?>" class="service-img" alt="Service Image">
                                        <?php endif;
                                        endforeach; ?>
                                        <div>
                                            <h5><?= htmlspecialchars($s['title']) ?></h5>
                                            <p><?= htmlspecialchars($s['description']) ?></p>
                                            <p><strong>Category:</strong> <?= htmlspecialchars($s['category_name']) ?></p>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-3">
                                        <span class="badge <?= $badgeClass ?>"><?= ucfirst($s['status']) ?></span>
                                        <div>
                                            <a href="?action=toggle&service_id=<?= $s['id'] ?>" class="btn btn-sm btn-warning"><?= $s['status'] == 'active' ? 'Deactivate' : 'Activate' ?></a>
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editServiceModal<?= $s['id'] ?>"><i class="fas fa-edit"></i> Edit</button>
                                            <a href="?action=delete&service_id=<?= $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure to delete this service?')">Delete</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editServiceModal<?= $s['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Service</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="service_id" value="<?= $s['id'] ?>">
                                                <div class="mb-3">
                                                    <label>Title</label>
                                                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($s['title']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label>Description</label>
                                                    <textarea name="description" class="form-control" required><?= htmlspecialchars($s['description']) ?></textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label>Category</label>
                                                    <select name="category_id" class="form-select" required>
                                                        <?php foreach ($categories as $cat): ?>
                                                            <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $s['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['category_name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label>Upload Image (goes to second slot)</label>
                                                    <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-success">Update Service</button>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center">No services added yet.</p>
                    <?php endif; ?>
                </div>

                <!-- Add Service Modal -->
                <div class="modal fade" id="addServiceModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="modal-header">
                                    <h5 class="modal-title">Add New Service</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="add">
                                    <div class="mb-3">
                                        <label>Service Title</label>
                                        <input type="text" name="service_title" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Category</label>
                                        <select name="service_category" class="form-select" required>
                                            <option value="">Choose...</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label>Description</label>
                                        <textarea name="service_description" class="form-control" rows="3" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label>Primary Photo (required)</label>
                                        <input type="file" name="photo1" class="form-control" accept=".jpg,.jpeg,.png" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Photo 2 (optional)</label>
                                        <input type="file" name="photo2" class="form-control" accept=".jpg,.jpeg,.png">
                                    </div>
                                    <div class="mb-3">
                                        <label>Photo 3 (optional)</label>
                                        <input type="file" name="photo3" class="form-control" accept=".jpg,.jpeg,.png">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-success">Add Service</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
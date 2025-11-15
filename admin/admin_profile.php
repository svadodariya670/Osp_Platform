<?php
session_start();
require_once "../config/db.php";

// Session check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_id = $_SESSION['user_id'];
$success = $error = "";

// Fetch admin details safely
$stmt = $pdo->prepare("SELECT * FROM admin WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Avoid undefined index errors by assigning variables directly
$name        = $admin['name'] ?? '';
$email       = $admin['email'] ?? '';
$phone       = $admin['phone'] ?? '';
$role        = $admin['role'] ?? '';
$created_at  = $admin['created_at'] ?? '';
$firstLetter = strtoupper(substr($name, 0, 1));

// Handle password change form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password     = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        // Fetch current password
        $stmt = $pdo->prepare("SELECT password FROM admin WHERE id = :id");
        $stmt->execute(['id' => $admin_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data && password_verify($current_password, $data['password'])) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE admin SET password = :password WHERE id = :id")->execute([
                'password' => $hashed,
                'id' => $admin_id
            ]);
            $success = "Password updated successfully!";
        } else {
            $error = "Incorrect current password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Profile</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">

<style>
body {
    background-color: #f8f9fa;
}
.profile-card {
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    background: #fff;
    text-align: center;
    padding: 30px;
}
.avatar-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: #ff6f61;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 32px;
    font-weight: bold;
    margin: 0 auto 15px;
    text-transform: uppercase;
}
.profile-info p {
    margin-bottom: 8px;
}
.modal-header {
    background-color: #0d6efd;
    color: #fff;
}
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php require 'sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">My Profile</h2>
                <a href="../logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= $success ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <div class="card profile-card">
                <div class="avatar-circle"><?= htmlspecialchars($firstLetter) ?></div>
                <h4 class="mb-1 text-capitalize"><?= htmlspecialchars($name ?: 'Not Available') ?></h4>
                <p class="text-muted mb-3"><?= htmlspecialchars($role ?: 'Admin') ?></p>

                <div class="profile-info text-start mx-auto" style="max-width: 400px;">
                    <p><strong><i class="fas fa-envelope me-2 text-primary"></i></strong><?= htmlspecialchars($email ?: 'Not Available') ?></p>
                    <p><strong><i class="fas fa-phone-alt me-2 text-primary"></i></strong><?= htmlspecialchars($phone ?: 'Not Available') ?></p>
                    <p><strong><i class="fas fa-calendar-alt me-2 text-primary"></i></strong><?= $created_at ? date('d M Y', strtotime($created_at)) : 'Not Available' ?></p>
                </div>

                <div class="mt-4">
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <i class="fas fa-lock me-1"></i> Change Password
                    </button>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Password Change Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-key me-1"></i> Change Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
          <div class="mb-3">
              <label class="form-label">Current Password</label>
              <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="mb-3">
              <label class="form-label">New Password</label>
              <input type="password" name="new_password" class="form-control" minlength="6" required>
          </div>
          <div class="mb-3">
              <label class="form-label">Confirm New Password</label>
              <input type="password" name="confirm_password" class="form-control" minlength="6" required>
          </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

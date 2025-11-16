<?php
ob_start();
session_start();
require_once "../config/db.php";

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Session check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];
$success = $error = "";
$pwd_success = $pwd_error = "";

// Fetch current customer data
$stmt = $pdo->prepare("SELECT id, full_name, email, phone, status, password, created_at FROM customers WHERE id=:id LIMIT 1");
$stmt->execute(['id' => $customer_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// =====================
// Handle Edit Profile
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // CSRF token check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request.";
    } else {
        $full_name = trim($_POST['customer_name']);
        $phone = trim($_POST['customer_phone']);

        if (empty($full_name)) {
            $error = "Name cannot be empty.";
        } elseif (!preg_match("/^[a-zA-Z\s]+$/", $full_name)) {
            $error = "Name can only contain letters and spaces.";
        } elseif (!empty($phone) && !preg_match("/^\+?\d{10,15}$/", $phone)) {
            $error = "Invalid phone number format.";
        } else {
            $stmt = $pdo->prepare("UPDATE customers SET full_name=:full_name, phone=:phone WHERE id=:id");
            $stmt->execute([
                'full_name' => $full_name,
                'phone' => $phone,
                'id' => $customer_id
            ]);
            $success = "Profile updated successfully.";
            $_SESSION['name'] = $full_name;
            $user['full_name'] = $full_name;
            $user['phone'] = $phone;
        }
    }
}

// =====================
// Handle Change Password
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $pwd_error = "Invalid request.";
    } else {
        $current_pwd = $_POST['current_password'] ?? '';
        $new_pwd = $_POST['new_password'] ?? '';
        $confirm_pwd = $_POST['confirm_password'] ?? '';

        if (empty($current_pwd) || empty($new_pwd) || empty($confirm_pwd)) {
            $pwd_error = "All fields are required.";
        } elseif (!password_verify($current_pwd, $user['password'])) {
            $pwd_error = "Current password is incorrect.";
        } elseif (strlen($new_pwd) < 6) {
            $pwd_error = "New password must be at least 6 characters.";
        } elseif ($new_pwd !== $confirm_pwd) {
            $pwd_error = "New password and confirm password do not match.";
        } else {
            $hashed_pwd = password_hash($new_pwd, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE customers SET password=:pwd WHERE id=:id");
            $stmt->execute(['pwd' => $hashed_pwd, 'id' => $customer_id]);
            $pwd_success = "Password updated successfully.";
        }
    }
}

// Get first letter for avatar
$firstLetter = strtoupper(substr($user['full_name'] ?? 'C', 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Profile</title>
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
    background-color: #0d6efd;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 32px;
    font-weight: bold;
    margin: 0 auto 15px;
    text-transform: uppercase;
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
            <a href="../logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
        </div>

        <!-- Alerts -->
        <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <?php if ($success) echo "<div class='alert alert-success'>$success</div>"; ?>
        <?php if ($pwd_error) echo "<div class='alert alert-danger'>$pwd_error</div>"; ?>
        <?php if ($pwd_success) echo "<div class='alert alert-success'>$pwd_success</div>"; ?>

        <!-- Profile Card -->
        <div class="card profile-card">
            <div class="avatar-circle"><?= htmlspecialchars($firstLetter) ?></div>
            <h4 class="mb-1 text-capitalize"><?= htmlspecialchars($user['full_name'] ?? 'Customer') ?></h4>
            <p class="text-muted mb-3">Customer</p>

            <div class="text-start mx-auto" style="max-width: 400px;">
                <p><strong><i class="fas fa-envelope me-2 text-primary"></i></strong><?= htmlspecialchars($user['email'] ?? 'Not Available') ?></p>
                <p><strong><i class="fas fa-phone-alt me-2 text-primary"></i></strong><?= htmlspecialchars($user['phone'] ?? 'Not Available') ?></p>
                <p><strong><i class="fas fa-calendar-alt me-2 text-primary"></i></strong><?= isset($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : 'Not Available' ?></p>
            </div>

            <div class="mt-4">
                <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="fas fa-edit me-1"></i> Edit Profile
                </button>
                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                    <i class="fas fa-key me-1"></i> Change Password
                </button>
            </div>
        </div>

        <!-- Edit Profile Modal -->
        <div class="modal fade" id="editProfileModal" tabindex="-1">
          <div class="modal-dialog">
            <form method="post" class="modal-content">
              <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
              <input type="hidden" name="update_profile" value="1">
              <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-edit me-1"></i> Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                  <div class="mb-3">
                      <label class="form-label">Full Name</label>
                      <input type="text" name="customer_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                  </div>
                  <div class="mb-3">
                      <label class="form-label">Phone</label>
                      <input type="tel" name="customer_phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" placeholder="+911234567890">
                  </div>
              </div>
              <div class="modal-footer">
                  <button type="submit" class="btn btn-primary">Save Changes</button>
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Change Password Modal -->
        <div class="modal fade" id="changePasswordModal" tabindex="-1">
          <div class="modal-dialog">
            <form method="post" class="modal-content">
              <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
              <input type="hidden" name="change_password" value="1">
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
                      <label class="form-label">Confirm Password</label>
                      <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                  </div>
              </div>
              <div class="modal-footer">
                  <button type="submit" class="btn btn-warning">Update Password</button>
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </form>
          </div>
        </div>

    </main>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
ob_start();
session_start();
require_once "../config/db.php";

// =====================
// Session validation
// =====================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'provider') {
    header("Location: ../login.php");
    exit;
}

$provider_id = $_SESSION['user_id'];
$success = $error = "";

// =============================
// Fetch provider data properly
// =============================
try {
    $stmt = $pdo->prepare("SELECT * FROM providers WHERE id = :id");
    $stmt->execute(['id' => $provider_id]);
    $provider = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$provider) {
        $error = "Provider data not found.";
        $provider = [];
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $provider = [];
}

// =============================
// Normalize field names (avoid undefined key warnings)
// =============================
$provider = array_change_key_case($provider, CASE_LOWER);
$full_name = $provider['full_name'] ?? $provider['name'] ?? '';
$email     = $provider['email'] ?? $provider['provider_email'] ?? '';
$phone     = $provider['phone'] ?? $provider['mobile'] ?? '';
$address   = $provider['address'] ?? $provider['provider_address'] ?? '';
$password  = $provider['password'] ?? '';

// =============================
// Handle Edit Profile
// =============================
if (isset($_POST['edit_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');

    if (empty($full_name)) {
        $error = "Full Name cannot be empty.";
    } elseif (!empty($phone) && !preg_match('/^[0-9+\-\s]{6,15}$/', $phone)) {
        $error = "Enter a valid phone number.";
    } else {
        $stmt = $pdo->prepare("UPDATE providers SET full_name=:full_name, phone=:phone, address=:address WHERE id=:id");
        $stmt->execute([
            'full_name' => $full_name,
            'phone'     => $phone,
            'address'   => $address,
            'id'        => $provider_id
        ]);
        $success = "Profile updated successfully.";
    }
}

// =============================
// Handle Change Password
// =============================
if (isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass     = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $error = "All password fields are required.";
    } elseif (!password_verify($current_pass, $password)) {
        $error = "Current password is incorrect.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "New password and confirm password do not match.";
    } else {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE providers SET password=:password WHERE id=:id");
        $stmt->execute(['password' => $hashed, 'id' => $provider_id]);
        $success = "Password changed successfully.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Provider Profile</title>
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
.profile-info p {
    margin-bottom: 8px;
}
.card-header {
    background-color: #0d6efd;
    color: #fff;
    font-weight: bold;
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

<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php elseif ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<div class="card profile-card text-center p-4">
    <div class="avatar-circle">
        <?= strtoupper(substr($full_name ?: 'P', 0, 1)) ?>
    </div>
    <h4 class="mb-1"><?= htmlspecialchars($full_name ?: 'Not Available') ?></h4>
    <p class="text-muted mb-3"><?= htmlspecialchars($email ?: 'No Email') ?></p>
    <div class="profile-info text-start mx-auto" style="max-width: 400px;">
        <p><strong><i class="fas fa-phone-alt me-2 text-primary"></i></strong><?= htmlspecialchars($phone ?: 'Not Available') ?></p>
        <p><strong><i class="fas fa-map-marker-alt me-2 text-primary"></i></strong><?= htmlspecialchars($address ?: 'Not Available') ?></p>
    </div>
    <div class="mt-4">
        <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#editProfileModal">
            <i class="fas fa-edit me-1"></i> Edit Profile
        </button>
        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
            <i class="fas fa-lock me-1"></i> Change Password
        </button>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
          <div class="mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($full_name) ?>" required>
          </div>
          <div class="mb-3">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($phone) ?>">
          </div>
          <div class="mb-3">
              <label class="form-label">Address</label>
              <textarea name="address" class="form-control"><?= htmlspecialchars($address) ?></textarea>
          </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="edit_profile" class="btn btn-primary">Save Changes</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </form>
  </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Change Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
          <div class="mb-3">
              <label class="form-label">Current Password</label>
              <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="mb-3">
              <label class="form-label">New Password</label>
              <input type="password" name="new_password" class="form-control" required>
          </div>
          <div class="mb-3">
              <label class="form-label">Confirm New Password</label>
              <input type="password" name="confirm_password" class="form-control" required>
          </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
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

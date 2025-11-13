<?php
ob_start();
session_start();
require_once "config/db.php";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = trim($_POST['role']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $error = "";

    if (empty($role) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        switch ($role) {
            case 'admin':
                $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = :email LIMIT 1");
                break;
            case 'customer':
                $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = :email LIMIT 1");
                break;
            case 'provider':
                $stmt = $pdo->prepare("SELECT * FROM providers WHERE email = :email LIMIT 1");
                break;
            default:
                $error = "Invalid role selected.";
        }

        if (empty($error)) {
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['role'] = $role;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'] ?? $user['full_name'] ?? '';

                // Redirect to role-specific dashboard
                if ($role == 'admin') {
                    header("Location: admin/dashboard.php");
                } elseif ($role == 'customer') {
                    header("Location: customer/dashboard.php");
                } elseif ($role == 'provider') {
                    header("Location: provider/dashboard.php");
                }
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow p-4">
                <h2 class="mb-4 text-center">Login</h2>
                <?php if (!empty($error)) { ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php } ?>
                <form id="mainLoginForm" method="post">
                    <div class="mb-3">
                        <label for="role" class="form-label">Login As</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin" <?= (isset($role) && $role=='admin') ? 'selected' : '' ?>>Admin</option>
                            <option value="customer" <?= (isset($role) && $role=='customer') ? 'selected' : '' ?>>Customer</option>
                            <option value="provider" <?= (isset($role) && $role=='provider') ? 'selected' : '' ?>>Provider</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
?>

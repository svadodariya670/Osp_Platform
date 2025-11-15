<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../config/db.php";

// Verify admin login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch admin info from DB using session
$admin_id = $_SESSION['user_id'] ?? null;
$admin_name = "Admin";
$admin_role = "Super User";

if ($admin_id) {
    $stmt = $pdo->prepare("SELECT name, role FROM admin WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin) {
        $admin_name = $admin['name'];
        $admin_role = ucfirst($admin['role']);
    }
}

// Determine active page
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="col-md-3 col-lg-2 d-md-block sidebar py-4">
    <div class="position-sticky px-3">

        <!-- Profile Section -->
        <div class="d-flex align-items-center mb-4 p-2 bg-dark rounded shadow-sm text-white">
            <div class="profile-bubble me-2" style="width:45px; height:45px; font-size:1.2rem; background:#ff6f61;">
                <?= strtoupper(substr($admin_name, 0, 1)) ?>
            </div>
            <div>
                <div class="fw-bold"><?= htmlspecialchars($admin_name) ?></div>
                <small><?= htmlspecialchars($admin_role) ?></small>
            </div>
        </div>

        <!-- Navigation Links -->
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded <?= $current_page=='dashboard.php'?'active-link':'' ?>" href="dashboard.php">
                    <div class="icon-circle bg-primary text-white me-2">
                        <i class="fas fa-home"></i>
                    </div>
                    Dashboard
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded <?= $current_page=='manage_users.php'?'active-link':'' ?>" href="manage_users.php">
                    <div class="icon-circle bg-success text-white me-2">
                        <i class="fas fa-users"></i>
                    </div>
                    Manage Customers
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded <?= $current_page=='manage_providers.php'?'active-link':'' ?>" href="manage_providers.php">
                    <div class="icon-circle bg-warning text-white me-2">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    Manage Providers
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded <?= $current_page=='manage_services.php'?'active-link':'' ?>" href="manage_services.php">
                    <div class="icon-circle bg-info text-white me-2">
                        <i class="fas fa-cogs"></i>
                    </div>
                    Manage Services
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded <?= $current_page=='payments.php'?'active-link':'' ?>" href="payments.php">
                    <div class="icon-circle bg-danger text-white me-2">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    Payments
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded <?= $current_page=='manage_plan.php'?'active-link':'' ?>" href="manage_plan.php">
                    <div class="icon-circle bg-secondary text-white me-2">
                        <i class="fas fa-cogs"></i>
                    </div>
                    Manage Plans
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded <?= $current_page=='admin_profile.php'?'active-link':'' ?>" href="admin_profile.php">
                    <div class="icon-circle bg-dark text-white me-2">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    Profile
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded text-danger" href="../logout.php">
                    <div class="icon-circle bg-danger text-white me-2">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    Logout
                </a>
            </li>
        </ul>
    </div>

    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #2c3e50, #34495e);
            color: #fff;
        }

        .nav-link {
            color: #ced4da;
            transition: all 0.3s;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .active-link {
            background: linear-gradient(90deg, #0d6efd, #6c63ff);
            color: #fff !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        .icon-circle {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .profile-bubble {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
</nav>

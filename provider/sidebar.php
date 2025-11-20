<?php
// =======================
// Sidebar: Provider info
// =======================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'provider') {
    header("Location: ../login.php");
    exit;
}

$provider_id = $_SESSION['user_id'];

// Fetch provider details
$stmt = $pdo->prepare("SELECT full_name FROM providers WHERE id=:id");
$stmt->execute(['id' => $provider_id]);
$provider = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch latest active subscription
$stmt = $pdo->prepare("
    SELECT s.start_date, s.end_date
    FROM subscriptions s
    WHERE s.provider_id = :pid AND s.status='active'
    ORDER BY s.id DESC
    LIMIT 1
");
$stmt->execute(['pid' => $provider_id]);
$sub = $stmt->fetch(PDO::FETCH_ASSOC);

// Remaining days calculation
if ($sub) {
    $today = new DateTime('today');
    $end = new DateTime($sub['end_date']);
    $interval = $today->diff($end);
    $remaining_days = ($interval->invert == 0) ? $interval->days + 1 : 0;
    $plan_status = ($remaining_days > 0) ? 'Active' : 'Expired';
} else {
    $remaining_days = 0;
    $plan_status = 'Expired';
}

// Provider initials
$initials = '';
if ($provider && !empty($provider['full_name'])) {
    $parts = explode(' ', $provider['full_name']);
    foreach ($parts as $p) {
        $initials .= strtoupper($p[0]);
    }
}

// Active menu detection
$current_page = $page ?? basename($_SERVER['PHP_SELF']);
?>

<nav class="col-md-3 col-lg-2 d-md-block sidebar py-4">
    <div class="position-sticky px-3">

        <!-- Profile Section -->
        <div class="d-flex align-items-center mb-4 p-2 bg-dark rounded shadow-sm text-white">
            <div class="profile-bubble me-2" style="width:45px; height:45px; font-size:1.2rem; background:#6c63ff;">
                <?= htmlspecialchars($initials) ?>
            </div>
            <div>
                <div class="fw-bold"><?= htmlspecialchars($provider['full_name'] ?? 'Provider') ?></div>
                <small><?= $plan_status ?> | <?= $remaining_days ?> days</small>
            </div>
        </div>

        <!-- Navigation Links -->
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded <?= $current_page == 'dashboard' ? 'active-link' : '' ?>" href="dashboard.php">
                    <div class="icon-circle bg-primary text-white me-2">
                        <i class="fas fa-home"></i>
                    </div>
                    Dashboard
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded <?= $current_page == 'manage_services' ? 'active-link' : '' ?>" href="manage_services.php">
                    <div class="icon-circle bg-success text-white me-2">
                        <i class="fas fa-cogs"></i>
                    </div>
                    Manage Services
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded <?= $current_page == 'bookings' ? 'active-link' : '' ?>" href="bookings.php">
                    <div class="icon-circle bg-warning text-white me-2">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    Booking Requests
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded <?= $current_page == 'subscription' ? 'active-link' : '' ?>" href="subscription.php">
                    <div class="icon-circle bg-info text-white me-2">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    Subscription
                    <span class="badge bg-light text-dark ms-auto"><?= $remaining_days ?> days</span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded <?= $current_page == 'profile' ? 'active-link' : '' ?>" href="profile.php">
                    <div class="icon-circle bg-danger text-white me-2">
                        <i class="fas fa-user"></i>
                    </div>
                    Profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-danger" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>

        </ul>
    </div>

    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #343a40, #495057);
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

        .badge {
            font-size: 0.75rem;
        }
    </style>
</nav>
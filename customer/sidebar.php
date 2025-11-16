<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page to highlight active link
$currentPage = basename($_SERVER['PHP_SELF']);

// Get customer name and initials
$customerName = $_SESSION['name'] ?? 'Customer';
$initials = '';
$parts = explode(' ', $customerName);
foreach($parts as $p) {
    $initials .= strtoupper($p[0]);
}
$initials = substr($initials, 0, 2); // max 2 letters
?>

<nav class="col-md-3 col-lg-2 d-md-block sidebar py-4">
    <div class="position-sticky px-3">

        <!-- Profile Section -->
        <div class="d-flex align-items-center mb-4 p-2 bg-dark rounded shadow-sm text-white">
            <div class="profile-bubble me-2" style="width:45px; height:45px; font-size:1.2rem; background:#6c63ff;"><?= htmlspecialchars($initials) ?></div>
            <div>
                <div class="fw-bold"><?= htmlspecialchars($customerName) ?></div>
                <small>Active User</small>
            </div>
        </div>

        <!-- Navigation Links -->
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded <?= ($currentPage=='dashboard.php') ? 'active-link' : '' ?>" href="dashboard.php">
                    <div class="icon-circle bg-primary text-white me-2">
                        <i class="fas fa-home"></i>
                    </div>
                    Dashboard
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded <?= ($currentPage=='book_service.php') ? 'active-link' : '' ?>" href="book_service.php">
                    <div class="icon-circle bg-success text-white me-2">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    Book Service
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded <?= ($currentPage=='reviews.php') ? 'active-link' : '' ?>" href="reviews.php">
                    <div class="icon-circle bg-warning text-white me-2">
                        <i class="fas fa-star"></i>
                    </div>
                    My Reviews
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded <?= ($currentPage=='profile.php') ? 'active-link' : '' ?>" href="profile.php">
                    <div class="icon-circle bg-info text-white me-2">
                        <i class="fas fa-user"></i>
                    </div>
                    Profile
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded <?= ($currentPage=='view_services.php') ? 'active-link' : '' ?>" href="view_services.php">
                    <div class="icon-circle bg-primary text-white me-2">
                        <i class="fas fa-concierge-bell"></i>
                    </div>
                    View Services
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
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
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

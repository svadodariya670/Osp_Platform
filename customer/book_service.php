<?php
session_start();
require_once "../config/db.php";

// =====================
// Session check
// =====================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];
$error = "";

// =====================
// Cancel booking
// =====================
if (isset($_GET['cancel_id'])) {
    $cancel_id = intval($_GET['cancel_id']);

    $stmt = $pdo->prepare("SELECT booking_date, status FROM bookings WHERE id=:id AND customer_id=:cid");
    $stmt->execute(['id' => $cancel_id, 'cid' => $customer_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($booking) {
        $bookingDate = strtotime($booking['booking_date']);
        $hoursDiff = ($bookingDate - time()) / 3600;

        if ($booking['status'] === 'Upcoming' && $hoursDiff > 24) {
            $updateStmt = $pdo->prepare("UPDATE bookings SET status='Cancelled' WHERE id=:id");
            $updateStmt->execute(['id' => $cancel_id]);
            $_SESSION['success'] = "Booking cancelled successfully.";
        } else {
            $_SESSION['error'] = "You can only cancel upcoming bookings at least 24 hours before the scheduled time.";
        }
    }
    header("Location: book_service.php");
    exit;
}

// =====================
// Fetch active services
// =====================
$serviceStmt = $pdo->query("
    SELECT s.id, s.title, p.full_name as provider_name
    FROM services s
    JOIN providers p ON s.provider_id = p.id
    WHERE s.status='active' AND p.status='active' AND p.plan_expiry >= CURDATE()
    ORDER BY s.title ASC
");
$services = $serviceStmt->fetchAll(PDO::FETCH_ASSOC);

// =====================
// Preselect service
// =====================
$preselect_service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$preselect_service = null;
foreach ($services as $s) {
    if ($s['id'] == $preselect_service_id) $preselect_service = $s;
}

// =====================
// Handle booking form
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service'])) {
    $service_id = intval($_POST['service']);
    $date = $_POST['date'] ?? '';
    $details = trim($_POST['details'] ?? '');

    if ($service_id <= 0) {
        $error = "Please select a valid service.";
    } elseif (empty($date)) {
        $error = "Please select a date.";
    } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
        $error = "Please select a valid future date.";
    } elseif (strlen($details) < 5) {
        $error = "Please enter at least 5 characters in details.";
    } else {
        $checkStmt = $pdo->prepare("
            SELECT id FROM bookings 
            WHERE customer_id=:cid AND service_id=:sid AND status IN ('Pending','Accepted','Upcoming')
        ");
        $checkStmt->execute(['cid' => $customer_id, 'sid' => $service_id]);
        if ($checkStmt->rowCount() > 0) {
            $error = "You already have an active booking for this service.";
        } else {
            $insertStmt = $pdo->prepare("
                INSERT INTO bookings (customer_id, service_id, booking_date, details, status, created_at)
                VALUES (:cid, :sid, :date, :details, 'Pending', NOW())
            ");
            $insertStmt->execute([
                'cid' => $customer_id,
                'sid' => $service_id,
                'date' => $date,
                'details' => $details
            ]);
            $_SESSION['success'] = "Service booked successfully!";
            header("Location: book_service.php");
            exit;
        }
    }
}

// =====================
// Fetch all bookings
// =====================
$bookingsStmt = $pdo->prepare("
    SELECT b.*, s.title AS service_title, p.full_name AS provider_name
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN providers p ON s.provider_id = p.id
    WHERE b.customer_id = :cid
    ORDER BY b.booking_date DESC
");
$bookingsStmt->execute(['cid' => $customer_id]);
$all_bookings = $bookingsStmt->fetchAll(PDO::FETCH_ASSOC);

// =====================
// Count statuses
// =====================
$statusCounts = [
    'Pending' => 0,
    'Accepted' => 0,
    'Upcoming' => 0,
    'Completed' => 0,
    'Cancelled' => 0,
    'Rejected' => 0
];
foreach ($all_bookings as $b) {
    $s = ucfirst(strtolower($b['status']));
    if (isset($statusCounts[$s])) $statusCounts[$s]++;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Service - Customer Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">

    <style>
        body {
            background: #f8f9fa;
        }

        .card-custom {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .btn-success {
            background: linear-gradient(90deg, #0d6efd, #6610f2);
            border: none;
        }

        .btn-success:hover {
            background: linear-gradient(90deg, #6610f2, #0d6efd);
        }

        .badge-status {
            font-size: 0.75rem;
            padding: 0.4em 0.6em;
            border-radius: 6px;
        }

        .modal-header {
            background-color: #0d6efd;
            color: #fff;
        }

        .table-hover tbody tr:hover {
            background-color: #f1f5ff;
        }

        .chart-wrapper {
            width: 320px;
            height: 320px;
            margin: 0 auto;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <?php require 'sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="fw-bold">📅 Book a Service</h2>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bookServiceModal">
                        <i class="fas fa-plus me-1"></i> New Booking
                    </button>
                </div>

                <!-- Alerts -->
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']);
                                                    unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Booking Status Chart -->
                <div class="card card-custom mb-4">
                    <div class="card-body text-center">
                        <h4 class="fw-bold mb-4">
                            <i class="fas fa-chart-pie text-primary me-2"></i> Booking Status Overview
                        </h4>
                        <div class="chart-container mx-auto">
                            <canvas id="statusChart"></canvas>
                        </div>

                    </div>
                </div>


                <!-- Bookings Table -->
                <div class="card card-custom">
                    <div class="card-body">
                        <h5 class="fw-semibold mb-3"><i class="fas fa-list me-1"></i> My Bookings</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle custom-table">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Service</th>
                                        <th>Provider</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Details</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($all_bookings): ?>
                                        <?php foreach ($all_bookings as $b):
                                            $status = ucfirst(strtolower($b['status']));
                                            $color = match ($status) {
                                                'Pending' => 'bg-warning text-dark',
                                                'Accepted' => 'bg-success',
                                                'Upcoming' => 'bg-primary',
                                                'Completed' => 'bg-info text-dark',
                                                'Cancelled' => 'bg-secondary',
                                                'Rejected' => 'bg-danger',
                                                default => 'bg-light text-dark'
                                            };
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($b['service_title']) ?></td>
                                                <td><?= htmlspecialchars($b['provider_name']) ?></td>
                                                <td><?= htmlspecialchars($b['booking_date']) ?></td>
                                                <td><span class="badge <?= $color ?>"><?= $status ?></span></td>
                                                <td><?= htmlspecialchars($b['details']) ?></td>
                                                <td>
                                                    <?php
                                                    $diff = (strtotime($b['booking_date']) - time()) / 3600;
                                                    if ($status === 'Upcoming' && $diff > 24): ?>
                                                        <a href="?cancel_id=<?= $b['id'] ?>"
                                                            class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Cancel this booking?');">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">No bookings found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookServiceModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-calendar-plus me-1"></i> Book Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Service</label>
                        <select name="service" class="form-select" required>
                            <option value="">Choose...</option>
                            <?php foreach ($services as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($preselect_service && $preselect_service['id'] == $s['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['title']) ?> (<?= htmlspecialchars($s['provider_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Booking Date</label>
                        <input type="date" class="form-control" name="date" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Details</label>
                        <textarea name="details" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Confirm Booking</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('statusChart').getContext('2d');
        const data = {
            labels: <?= json_encode(array_keys($statusCounts)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($statusCounts)) ?>,
                backgroundColor: ['#ffc107', '#28a745', '#0d6efd', '#0dcaf0', '#6c757d', '#dc3545']
            }]
        };

        new Chart(ctx, {
            type: 'doughnut',
            data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15
                        }
                    }
                },
                layout: {
                    padding: 10
                }
            }
        });
    </script>

</body>

</html>
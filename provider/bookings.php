<?php
ob_start();
session_start();
require_once "../config/db.php";

// ==========================
// Session check
// ==========================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'provider') {
  header("Location: ../login.php");
  exit;
}

$provider_id = $_SESSION['user_id'];

// ==========================
// Handle AJAX booking status update
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['new_status'])) {
  $booking_id = intval($_POST['booking_id']);
  $new_status = $_POST['new_status'];

  $stmt = $pdo->prepare("
        UPDATE bookings b
        JOIN services s ON b.service_id = s.id
        SET b.status = :status
        WHERE b.id = :id AND s.provider_id = :provider_id
    ");
  $stmt->execute([
    'status' => $new_status,
    'id' => $booking_id,
    'provider_id' => $provider_id
  ]);
  echo json_encode(['status' => 'success']);
  exit;
}

// ==========================
// Fetch all bookings for this provider
// ==========================
$stmt = $pdo->prepare("
    SELECT b.*, s.title AS service_title, c.full_name AS customer_name
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN customers c ON b.customer_id = c.id
    WHERE s.provider_id = :provider_id
    ORDER BY b.booking_date DESC
");
$stmt->execute(['provider_id' => $provider_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================
// Group bookings by status
// ==========================
$statuses = ['Pending', 'Upcoming', 'Completed', 'Rejected', 'Cancelled'];
$bookingsByStatus = [];
$statusCounts = [];

foreach ($statuses as $status) {
  $filtered = array_filter($bookings, fn($b) => strtolower($b['status']) === strtolower($status));
  $bookingsByStatus[$status] = $filtered;
  $statusCounts[$status] = count($filtered);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Provider Bookings</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>
  <link href="../assets/css/style.css" rel="stylesheet">

  <style>
    body {
      background-color: #f8f9fa;
    }

    .card-hover:hover {
      transform: translateY(-4px);
      transition: 0.3s;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .status-badge {
      margin-right: 5px;
      padding: 8px 14px;
      border-radius: 5px;
      color: #fff;
      font-size: 0.9rem;
      cursor: pointer;
      user-select: none;
    }

    .status-Pending {
      background: #f0ad4e;
    }

    .status-Upcoming {
      background: #007bff;
    }

    .status-Completed {
      background: #28a745;
    }

    .status-Cancelled {
      background: #6c757d;
    }

    .status-Rejected {
      background: #dc3545;
    }
  </style>
</head>

<body>
  <div class="container-fluid">
    <div class="row">
      <?php require 'sidebar.php'; ?>

      <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
        <h2 class="mb-4"><i class="fas fa-calendar-check text-primary me-2"></i>Bookings Dashboard</h2>

        <!-- New Chart Card -->
        <div class="card mt-4 card-hover" style="margin-bottom: 20px;">
          <div class="card-body text-center p-3" style="padding-bottom: 0;">
            <h5 class="card-title mb-3">Booking Status Overview</h5>
            <div class="chart-wrapper" style="width: 450px; height: 200px; margin: 0 auto ;">
              <canvas id="statusChart" class="chart-provider"></canvas>
            </div>
          </div>
        </div>


        <!-- Status Summary -->
        <div class="d-flex justify-content-center mb-4 flex-wrap gap-2">
          <?php foreach ($statusCounts as $status => $count): ?>
            <span class="status-badge status-<?= $status ?>" data-status="<?= $status ?>">
              <?= $status ?> (<?= $count ?>)
            </span>
          <?php endforeach; ?>
        </div>

        <!-- Hidden booking lists for modal -->
        <?php foreach ($bookingsByStatus as $status => $list): ?>
          <div id="list-<?= $status ?>" class="d-none">
            <?php if (count($list) === 0): ?>
              <p class="text-muted">No bookings in this status.</p>
            <?php else: ?>
              <?php foreach ($list as $b): ?>
                <div class="card mb-2 card-hover p-3">
                  <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                      <strong class="text-primary"><?= htmlspecialchars($b['service_title']) ?></strong>
                      <span class="text-muted">(<?= htmlspecialchars($b['booking_date']) ?>)</span><br>
                      Customer: <strong><?= htmlspecialchars($b['customer_name']) ?></strong><br>
                      Details: <?= htmlspecialchars($b['details'] ?: '-') ?>
                    </div>
                    <div class="mt-2 mt-md-0">
                      <?php if (strtolower($status) == 'pending'): ?>
                        <button class="btn btn-success btn-sm update-status" data-id="<?= $b['id'] ?>" data-status="Upcoming">Accept</button>
                        <button class="btn btn-danger btn-sm update-status" data-id="<?= $b['id'] ?>" data-status="Rejected">Reject</button>
                      <?php elseif (strtolower($status) == 'upcoming'): ?>
                        <button class="btn btn-primary btn-sm update-status" data-id="<?= $b['id'] ?>" data-status="Completed">Mark Completed</button>
                        <button class="btn btn-danger btn-sm update-status" data-id="<?= $b['id'] ?>" data-status="Cancelled">Cancel</button>
                      <?php else: ?>
                        <span class="status-badge status-<?= $status ?>"><?= $status ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

      </main>
    </div>
  </div>

  <!-- Modal -->
  <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="statusModalLabel">Bookings</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="modalBody"></div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const statusCounts = <?= json_encode(array_values($statusCounts)) ?>;
    const statusLabels = <?= json_encode(array_keys($statusCounts)) ?>;

    // ==== Chart ====
    new Chart(document.getElementById('statusChart'), {
      type: 'doughnut',
      data: {
        labels: statusLabels,
        datasets: [{
          data: statusCounts,
          backgroundColor: ['#f0ad4e', '#007bff', '#28a745', '#dc3545', '#6c757d'],
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              boxWidth: 18,
              padding: 15
            }
          }
        },
        layout: {
          padding: {
            bottom: 0
          }
        }
      }
    });

    // ==== Modal popup logic ====
    document.querySelectorAll('.status-badge').forEach(badge => {
      badge.addEventListener('click', () => {
        const status = badge.dataset.status;
        const modalBody = document.getElementById('modalBody');
        const listDiv = document.getElementById('list-' + status);
        modalBody.innerHTML = listDiv ? listDiv.innerHTML : '<p>No data found.</p>';
        document.getElementById('statusModalLabel').textContent = status + " Bookings";
        const modal = new bootstrap.Modal(document.getElementById('statusModal'));
        modal.show();
      });
    });

    // ==== AJAX booking status update ====
    document.addEventListener('click', e => {
      if (e.target.classList.contains('update-status')) {
        const booking_id = e.target.dataset.id;
        const new_status = e.target.dataset.status;
        fetch('', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: `booking_id=${booking_id}&new_status=${new_status}`
        }).then(res => res.json()).then(data => {
          if (data.status === 'success') location.reload();
          else alert('Failed to update status');
        });
      }
    });
  </script>
</body>

</html>
<?php
ob_start();

// Fetch statistics from DB (dynamic content)
require_once "config/db.php";

// Total services
$total_services = $pdo->query("SELECT COUNT(*) FROM services WHERE status='active'")->fetchColumn();
// Total providers
$total_providers = $pdo->query("SELECT COUNT(*) FROM providers WHERE status='active'")->fetchColumn();
// Total customers
$total_customers = $pdo->query("SELECT COUNT(*) FROM customers WHERE status='Active'")->fetchColumn();
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h2 class="text-primary fw-bold">About Our Platform</h2>
        <p class="lead">Connecting you with trusted service providers for all your needs, quickly and securely.</p>
    </div>

    <!-- Why Choose Us -->
    <div class="row mb-5 g-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-start border-5 border-primary">
                <div class="card-body">
                    <h5 class="card-title text-primary">Why Choose Us?</h5>
                    <ul class="list-group list-group-flush mt-3">
                        <li class="list-group-item"><i class="fas fa-check text-success me-2"></i>Wide range of services and categories</li>
                        <li class="list-group-item"><i class="fas fa-check text-success me-2"></i>Verified and trusted providers</li>
                        <li class="list-group-item"><i class="fas fa-check text-success me-2"></i>Easy booking and review system</li>
                        <li class="list-group-item"><i class="fas fa-check text-success me-2"></i>Secure online payments</li>
                        <li class="list-group-item"><i class="fas fa-check text-success me-2"></i>Responsive customer support</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Mission & Vision -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-start border-5 border-success">
                <div class="card-body">
                    <h5 class="card-title text-success">Our Mission & Vision</h5>
                    <p class="mb-2"><strong>Mission:</strong> To simplify the process of finding and booking reliable services, empowering providers to grow their business.</p>
                    <p><strong>Vision:</strong> To be the leading platform for online service connections, trusted by customers and providers alike.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- How It Works -->
    <div class="row mb-5 g-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-info border-2">
                <div class="card-body">
                    <h5 class="card-title text-info">How It Works</h5>
                    <ol>
                        <li>Browse services and categories</li>
                        <li>Register and login as a customer</li>
                        <li>Book your desired service</li>
                        <li>Rate and review your experience</li>
                    </ol>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-warning border-2">
                <div class="card-body">
                    <h5 class="card-title text-warning">For Providers</h5>
                    <ul>
                        <li>Register and create your profile</li>
                        <li>List your services and manage bookings</li>
                        <li>Choose a subscription plan</li>
                        <li>Grow your business with us!</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Section -->
    <div class="row text-center mb-5 g-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100 p-4 border-primary border-2">
                <i class="fas fa-concierge-bell fa-3x text-primary mb-3"></i>
                <h4><?= $total_services ?></h4>
                <p>Active Services</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100 p-4 border-success border-2">
                <i class="fas fa-user-tie fa-3x text-success mb-3"></i>
                <h4><?= $total_providers ?></h4>
                <p>Trusted Providers</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100 p-4 border-warning border-2">
                <i class="fas fa-users fa-3x text-warning mb-3"></i>
                <h4><?= $total_customers ?></h4>
                <p>Active Customers</p>
            </div>
        </div>
    </div>

    <!-- Team Section -->
    <div class="text-center mb-4">
        <h3 class="text-primary">Meet Our Team</h3>
        <p class="lead">The people behind the platform</p>
    </div>
    <div class="row g-4 justify-content-center">
        <div class="col-md-3 text-center">
            <img src="../assets/img/team1.jpg" class="rounded-circle mb-2" width="120" height="120" alt="Team Member">
            <h6>Shyam Kumar</h6>
            <p class="text-muted">Founder & CEO</p>
        </div>
        <div class="col-md-3 text-center">
            <img src="../assets/img/team2.jpg" class="rounded-circle mb-2" width="120" height="120" alt="Team Member">
            <h6>Amit Singh</h6>
            <p class="text-muted">Head of Operations</p>
        </div>
        <div class="col-md-3 text-center">
            <img src="../assets/img/team3.jpg" class="rounded-circle mb-2" width="120" height="120" alt="Team Member">
            <h6>Sara Sharma</h6>
            <p class="text-muted">Lead Developer</p>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require 'layout.php';
?>

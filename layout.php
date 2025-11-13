<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) session_start();

// Determine current page for active link highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OSP Platform</title>
<meta name="description" content="Experience premium services at OSP Platform - A complete service provider portal.">

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
<!-- Custom CSS -->
<link rel="stylesheet" href="assets/css/style.css">


<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<style>
/* Header */
.navbar {
    background: rgba(13,110,253,0.95);
    transition: all 0.3s;
}
.navbar .nav-link {
    color: #fff !important;
    transition: 0.3s;
}
.navbar .nav-link:hover {
    color: #ffd700 !important;
}
.navbar .btn {
    transition: 0.3s;
}
.navbar .btn:hover {
    transform: scale(1.05);
}

/* Footer */
.footer {
    background: #343a40;
    color: #ced4da;
    padding: 40px 0 20px;
}
.footer a {
    color: #ced4da;
    text-decoration: none;
}
.footer a:hover {
    color: #fff;
}
.footer h5 {
    color: #fff;
    margin-bottom: 15px;
}
.footer .social-links a {
    display: inline-block;
    margin-right: 10px;
    font-size: 1.2rem;
    color: #fff;
    transition: 0.3s;
}
.footer .social-links a:hover {
    color: #0d6efd;
}

/* Newsletter input */
.footer .newsletter input {
    border-radius: 0;
    border: 0;
    padding: 10px;
}
.footer .newsletter button {
    border-radius: 0;
    border: 0;
    padding: 10px 20px;
}

/* Smooth page top padding for fixed header */
main {
    padding-top: 90px;
}
</style>
</head>

<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><i class="fas fa-building me-2"></i>OSP-Platform</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link <?= ($currentPage=='index.php')?'active':'' ?>" href="index.php"><i class="fas fa-home me-1"></i>Home</a></li>
                <li class="nav-item"><a class="nav-link <?= ($currentPage=='about.php')?'active':'' ?>" href="about.php"><i class="fas fa-info-circle me-1"></i>About Us</a></li>
                <li class="nav-item"><a class="nav-link <?= ($currentPage=='contact.php')?'active':'' ?>" href="contact.php"><i class="fas fa-envelope me-1"></i>Contact</a></li>
                <li class="nav-item"><a class="nav-link <?= ($currentPage=='register.php')?'active':'' ?>" href="register.php"><i class="fas fa-user-plus me-1"></i>Register</a></li>
                <li class="nav-item"><a class="btn btn-warning ms-2" href="login.php"><i class="fas fa-sign-in-alt me-1"></i>Login</a></li>
                <li class="nav-item"><a class="btn btn-success ms-3 px-4 fw-bold" href="registerprovider.php"><i class="fas fa-user-tie me-1"></i>Join Now</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Page Content -->
<main>
    <?php if (isset($content)) echo $content; ?>
</main>

<!-- Footer -->
<footer class="footer mt-5">
    <div class="container">
        <div class="row gy-4">
            <div class="col-md-4">
                <h5>OSP Platform</h5>
                <p>Connecting customers with trusted providers quickly and safely.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
            <div class="col-md-2">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="register.php">Register</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h5>Newsletter</h5>
                <p>Subscribe to get latest updates and offers.</p>
                <form class="d-flex newsletter" method="post" action="subscribe.php">
                    <input type="email" name="email" class="form-control me-2" placeholder="Enter your email" required>
                    <button type="submit" class="btn btn-primary">Subscribe</button>
                </form>
            </div>
            <div class="col-md-3 text-md-end mt-3 mt-md-0">
                <p>&copy; <?= date('Y') ?> OSP Platform. All Rights Reserved.</p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/validateform.js"></script>
</body>
</html>


<?php
session_start();
$_SESSION = [];
session_destroy();

// Example: redirect based on role stored in session before destroying
$role = $_SESSION['role'] ?? '';

    header("Location: login.php");

exit;

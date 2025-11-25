<?php
session_start();
require_once "config/db.php";
include 'sendMail.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $role = trim($_POST['role']);
    $email = trim($_POST['email']);

    if (empty($role) || empty($email)) {
        $_SESSION['fp_error'] = "All fields are required.";
        header("Location: forgot_password.php");
        exit;
    }

    // Step 1: Check role + email existence
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
            $_SESSION['fp_error'] = "Invalid role.";
            header("Location: forgot_password.php");
            exit;
    }

    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['fp_error'] = "Email does not exist for selected role.";
        header("Location: forgot_password.php");
        exit;
    }

    // Step 2: Generate OTP
    $otp = rand(100000, 999999);

    $_SESSION['reset_role']  = $role;
    $_SESSION['reset_email'] = $email;
    $_SESSION['reset_otp']   = $otp;
    $_SESSION['otp_expire']  = time() + 300;

    // Step 3: Send OTP using sendMail()
    $subject = "Your Password Reset OTP";
    $htmlMessage = "
        <h2>Your OTP is: <b>$otp</b></h2>
        <p>This OTP is valid for 5 minutes.</p>
    ";

    $sent = sendMail($email, $subject, $htmlMessage);

    if ($sent === true) {
        $_SESSION['fp_success'] = "OTP has been sent to your email.";
        header("Location: verify_reset.php");
        exit;
    } else {
        $_SESSION['fp_error'] = "Failed to send OTP: " . $sent;
        header("Location: forgot_password.php");
        exit;
    }
}
?>

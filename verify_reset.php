<?php
session_start();
require_once "config/db.php";

// --- SAFETY CHECK: Prevent direct access ---
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_otp']) || !isset($_SESSION['otp_expire'])) {
    header("Location: forgot_password.php");
    exit;
}

$step = "otp"; // default step

// -------------------------------
// STEP 1: VERIFY OTP
// -------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["verify_otp"])) {

    $entered_otp = trim($_POST["otp"]);
    $saved_otp   = $_SESSION['reset_otp'];
    $expire_time = $_SESSION['otp_expire'];

    if (time() > $expire_time) {
        $error = "OTP expired. Please request a new one.";
    } elseif ($entered_otp != $saved_otp) {
        $error = "Invalid OTP. Please try again.";
    } else {
        $step = "reset_password";
    }
}

// -------------------------------
// STEP 2: RESET PASSWORD
// -------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reset_password"])) {

    $password  = trim($_POST["password"]);
    $confirm   = trim($_POST["confirm_password"]);

    if (empty($password) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {

        $email = $_SESSION['reset_email'];
        $role  = $_SESSION['reset_role'];

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        switch ($role) {
            case 'admin':
                $stmt = $pdo->prepare("UPDATE admin SET password = :pass WHERE email = :email");
                break;
            case 'customer':
                $stmt = $pdo->prepare("UPDATE customers SET password = :pass WHERE email = :email");
                break;
            case 'provider':
                $stmt = $pdo->prepare("UPDATE providers SET password = :pass WHERE email = :email");
                break;
        }

        $stmt->execute([
            ":pass" => $hashed,
            ":email" => $email
        ]);

        $success = "Password successfully updated!";
        unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['otp_expire'], $_SESSION['reset_role']);
        $step = "done";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP & Reset Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
    body {
        font-family: 'Poppins', sans-serif;
        height: 100vh;
        margin: 0;
        background: linear-gradient(135deg, #4F46E5, #0EA5E9);
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .main-card {
        width: 420px;
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(12px);
        border-radius: 18px;
        padding: 30px;
        color: #fff;
        box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        animation: fadeIn 0.6s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    h3 {
        font-weight: 600;
        text-align: center;
        margin-bottom: 20px;
    }

    label {
        font-size: 14px;
        font-weight: 500;
    }

    .form-control {
        background: rgba(255,255,255,0.2);
        border: none;
        color: #fff;
        height: 45px;
        border-radius: 8px;
    }

    .form-control::placeholder {
        color: #eee;
        opacity: 0.6;
    }

    .btn-custom {
        height: 45px;
        border-radius: 10px;
        background: #fff;
        color: #4F46E5;
        font-weight: 600;
        transition: 0.3s;
    }

    .btn-custom:hover {
        background: #4F46E5;
        color: #fff;
    }

    .loader {
        display: none;
        border: 5px solid #eee;
        border-top: 5px solid #fff;
        border-radius: 50%;
        width: 35px;
        height: 35px;
        animation: spin 1s linear infinite;
        margin: auto;
    }

    @keyframes spin { 100% { transform: rotate(360deg); } }

    .alert {
        border-radius: 10px;
    }
</style>

<script>
    function showLoader() {
        document.getElementById("loader").style.display = "block";
    }
</script>

</head>
<body>

<div class="main-card">

    <h3>🔐 Password Recovery</h3>

    <?php if (!empty($error)) { ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php } ?>

    <?php if (!empty($success)) { ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php } ?>

    <div id="loader" class="loader mb-3"></div>

    <!-- STEP 1: OTP -->
    <?php if ($step == "otp") { ?>
        <form method="post" onsubmit="showLoader()">
            <label>Enter the OTP sent to your email</label>
            <input type="text" name="otp" class="form-control mb-3" placeholder="6-digit OTP" required>

            <button type="submit" name="verify_otp" class="btn btn-custom w-100 mt-2">
                Verify OTP
            </button>
        </form>
    <?php } ?>

    <!-- STEP 2: Reset Password -->
    <?php if ($step == "reset_password") { ?>
        <form method="post">
            <label>New Password</label>
            <input type="password" name="password" class="form-control mb-3" placeholder="Enter new password" required>

            <label>Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control mb-3" placeholder="Re-enter password" required>

            <button type="submit" name="reset_password" class="btn btn-custom w-100 mt-2">
                Reset Password
            </button>
        </form>
    <?php } ?>

    <!-- STEP 3: Done -->
    <?php if ($step == "done") { ?>
        <a href="login.php" class="btn btn-custom w-100 mt-3">Go to Login</a>
    <?php } ?>

</div>

</body>
</html>

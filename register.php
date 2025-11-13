<?php
ob_start();
require_once "config/db.php";
//require_once "email.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $password  = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    $error = "";

    // Basic validation
    if (empty($full_name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Generate username from full name (lowercase + no spaces)
        $username = strtolower(str_replace(" ", "", $full_name));

        // Ensure username is unique
        $checkUser = $pdo->prepare("SELECT id FROM customers WHERE username = :username LIMIT 1");
        $checkUser->execute(['username' => $username]);
        if ($checkUser->fetch()) {
            $username .= rand(100, 999);
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $error = "Email is already registered.";
        } else {
            // Insert customer
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $status = "inactive";
            $created_at = date('Y-m-d H:i:s');
            $token = bin2hex(random_bytes(32));

            $stmt = $pdo->prepare("
                INSERT INTO customers (full_name, username, email, password, phone, status, created_at) 
                VALUES (:full_name, :username, :email, :password, :phone, :status, :created_at)
            ");
            $stmt->execute([
                'full_name'  => $full_name,
                'username'   => $username,
                'email'      => $email,
                'password'   => $hashed_password,
                'phone'      => $phone,
                'status'     => $status,
                'created_at' => $created_at,
              //  'token'      => $token
            ]);

            $verifyLink = "http://yourdomain.com/verify_email.php?token=$token";
            $subject = "Verify Your Email";
            $message = "
                <div style='font-family: Arial, sans-serif;'>
                    <h2>Welcome, $full_name!</h2>
                    <p>Thanks for registering. Please click the button below to verify your email:</p>
                    <p style='text-align:center;'>
                        <a href='$verifyLink' style='display:inline-block; padding:12px 24px; background:#007bff; color:#fff; text-decoration:none; border-radius:4px;'>Verify Email</a>
                    </p>
                    <p>If you didn’t request this, please ignore this email.</p>
                </div>
            ";

            // if (sendEmail($email, $subject, $message)) {
            //     setcookie("success", "Registration successful! Please check your email to verify your account.", time()+5, "/");
            // } else {
            //     setcookie("error", "Failed to send verification email. Try again later.", time()+5, "/");
            // }

            header("Location: register.php");
            exit;
        }
    }

    if (!empty($error)) {
        setcookie("error", $error, time()+5, "/");
        header("Location: register.php");
        exit;
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow p-4">
                <h2 class="mb-4 text-center">Customer Registration</h2>
                <form method="post">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
?>

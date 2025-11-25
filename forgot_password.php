<?php
session_start();
ob_start();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow p-4">
                <h2 class="mb-4 text-center">Forgot Password</h2>

                <?php 
                if (isset($_SESSION['fp_error'])) { ?>
                    <div class="alert alert-danger">
                        <?= $_SESSION['fp_error']; unset($_SESSION['fp_error']); ?>
                    </div>
                <?php } ?>

                <?php 
                if (isset($_SESSION['fp_success'])) { ?>
                    <div class="alert alert-success">
                        <?= $_SESSION['fp_success']; unset($_SESSION['fp_success']); ?>
                    </div>
                <?php } ?>

                <form action="send_otp.php" method="POST">

                    <!-- Select Role -->
                    <div class="mb-3">
                        <label for="role" class="form-label">Select Role</label>
                        <select class="form-select" name="role" id="role" required>
                            <option value="">-- Select Role --</option>
                            <option value="admin">Admin</option>
                            <option value="customer">Customer</option>
                            <option value="provider">Provider</option>
                        </select>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Enter Registered Email</label>
                        <input type="email" 
                               class="form-control" 
                               name="email" 
                               id="email" 
                               placeholder="example@gmail.com" 
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Send OTP
                    </button>

                    <div class="text-center mt-3">
                        <a href="login.php" class="text-decoration-none">Back to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
?>

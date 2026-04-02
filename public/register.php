<?php
require_once '../class/Admin.php';
$admin = new Admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — NORECO 1 WMS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/login.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<video id="bg-video" autoplay loop muted playsinline>
    <source src="../assets/video/Final_Back.mp4" type="video/mp4">
</video>

<div class="bg-circle bg-circle-1"></div>
<div class="bg-circle bg-circle-2"></div>

<div class="login-card">

    <!-- ── LEFT: Register Form ── -->
    <div class="left-panel">
    <div class="left-main">

        <div class="login-brand">
            <div class="login-brand-icon"><i class="fas fa-warehouse"></i></div>
            <div class="login-brand-text">
                <div class="brand-name">NORECO 1 WMS</div>
                <div class="brand-sub">Warehouse Monitoring System</div>
            </div>
        </div>

        <div class="welcome-title">Create Account</div>
        <div class="welcome-sub">Fill in the details below to register a new account.</div>

        <?php if (isset($_GET['register_error'])): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            <?php if ($_GET['register_error'] === 'password_mismatch'): ?>
            Swal.fire({
                icon: 'warning',
                title: 'Passwords Do Not Match',
                text: 'Please make sure both passwords are the same.',
                confirmButtonText: 'Try Again',
                confirmButtonColor: '#3399ff',
                allowOutsideClick: false,
                allowEscapeKey: false
            });
            <?php elseif ($_GET['register_error'] === 'user_exists'): ?>
            Swal.fire({
                icon: 'error',
                title: 'Username Taken',
                text: 'That username already exists. Please choose a different one.',
                confirmButtonText: 'Try Again',
                confirmButtonColor: '#3399ff',
                allowOutsideClick: false,
                allowEscapeKey: false
            });
            <?php elseif ($_GET['register_error'] === 'email_exists'): ?>
            Swal.fire({
                icon: 'error',
                title: 'Email Already Registered',
                text: 'That email address is already linked to an account.',
                confirmButtonText: 'Try Again',
                confirmButtonColor: '#3399ff',
                allowOutsideClick: false,
                allowEscapeKey: false
            });
            <?php elseif ($_GET['register_error'] === 'invalid_email'): ?>
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Email',
                text: 'Please enter a valid email address.',
                confirmButtonText: 'Try Again',
                confirmButtonColor: '#3399ff',
                allowOutsideClick: false,
                allowEscapeKey: false
            });
            <?php elseif ($_GET['register_error'] === 'failed'): ?>
            Swal.fire({
                icon: 'error',
                title: 'Registration Failed',
                text: 'Something went wrong. Please try again.',
                confirmButtonText: 'Try Again',
                confirmButtonColor: '#3399ff',
                allowOutsideClick: false,
                allowEscapeKey: false
            });
            <?php endif; ?>
        });
        </script>
        <?php endif; ?>

        <?php if (isset($_GET['register_success'])): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'success',
                title: 'Account Created!',
                text: 'Your account has been registered successfully.',
                confirmButtonText: '<i class="fas fa-sign-in-alt"></i> Back to Login',
                confirmButtonColor: '#3399ff',
                allowOutsideClick: false,
                allowEscapeKey: false,
                customClass: { popup: 'swal-custom' }
            }).then(function () {
                window.location.href = '../Controller/homepage.php';
            });
        });
        </script>
        <?php endif; ?>

        <form method="POST" action="../Controller/register.php">

            <div class="form-group">
                <label>Username</label>
                <div class="input-wrap">
                    <input type="text" name="username" placeholder="Enter your username" required autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <div class="input-wrap">
                    <input type="email" name="email" placeholder="Enter your Gmail address" required autocomplete="email">
                    <i class="fas fa-envelope" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px;pointer-events:none"></i>
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-wrap">
                    <input type="password" name="password" id="pwInput" placeholder="Enter your password" required autocomplete="new-password">
                    <i class="fas fa-eye-slash toggle-pw" id="pwToggle"></i>
                </div>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <div class="input-wrap">
                    <input type="password" name="confirm_password" id="cpInput" placeholder="Re-enter your password" required autocomplete="new-password">
                    <i class="fas fa-eye-slash toggle-pw" id="cpToggle"></i>
                </div>
            </div>

            <button type="submit" name="register" class="btn-login">Sign Up</button>

        </form>

        <div class="register-link">
            Already have an account? <a href="../Controller/homepage.php">Sign In</a>
        </div>

    </div><!-- end left-main -->

        <div class="powered-by">
            <i class="fas fa-chart-line"></i>
            Powered by <span>Data Analytics</span>
        </div>

    </div>

    <!-- ── RIGHT: Info panel ── -->
    <div class="right-panel">
        <div class="right-content">
            <div class="right-headline">
                Effortlessly manage your<br><span>warehouse inventory</span><br>and operations.
            </div>
            <div class="right-sub">
                Log in to access the NORECO 1 WMS dashboard — track materials, monitor stock levels, and generate real-time reports.
            </div>

            <div class="preview-cards">
                <div class="preview-card">
                    <div class="preview-card-icon icon-purple"><i class="fas fa-layer-group"></i></div>
                    <div class="preview-card-info">
                        <div class="pc-label">Total Materials</div>
                        <div class="pc-value">Tracked & Organized</div>
                    </div>
                </div>
                <div class="preview-card">
                    <div class="preview-card-icon icon-blue"><i class="fas fa-bolt"></i></div>
                    <div class="preview-card-info">
                        <div class="pc-label">Line Materials</div>
                        <div class="pc-value">Real-time Stock Levels</div>
                    </div>
                </div>
                <div class="preview-card">
                    <div class="preview-card-icon icon-gold"><i class="fas fa-tools"></i></div>
                    <div class="preview-card-info">
                        <div class="pc-label">Special Equipment</div>
                        <div class="pc-value">Bin Card Monitoring</div>
                    </div>
                </div>
                <div class="preview-card">
                    <div class="preview-card-icon icon-green"><i class="fas fa-chart-bar"></i></div>
                    <div class="preview-card-info">
                        <div class="pc-label">Stock Reports</div>
                        <div class="pc-value">Monthly Analytics</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="../assets/js/register.js"></script>

</body>
</html>

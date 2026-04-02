<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NORECO 1 WMS — Sign In</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/login.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <video id="bg-video" autoplay loop muted playsline >
        <source src="../assets/video/Final_Back.mp4" type="video/mp4"> </video>

<div class="bg-circle bg-circle-1"></div>
<div class="bg-circle bg-circle-2"></div>

<div class="login-card">

    <!-- ── LEFT: Form ── -->
    <div class="left-panel">
    <div class="left-main">

        <div class="login-brand">
            <div class="login-brand-icon"><i class="fas fa-warehouse"></i></div>
            <div class="login-brand-text">
                <div class="brand-name">NORECO 1 WMS</div>
                <div class="brand-sub">Warehouse Monitoring System</div>
            </div>
        </div>

        <div class="welcome-title">Welcome Back</div>
        <div class="welcome-sub">Enter your credentials to access the warehouse dashboard.</div>

        <?php if (isset($_GET['error'])): ?>
            <div class="msg-error"><i class="fas fa-exclamation-circle"></i> Invalid username or password.</div>
        <?php endif; ?>

        <?php if (isset($_GET['reset_success'])): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'success',
                title: 'Password Updated!',
                text: 'Your password has been reset successfully. You can now log in.',
                confirmButtonColor: '#3399ff',
                confirmButtonText: 'Log In Now'
            });
        });
        </script>
        <?php elseif (isset($_GET['forgot_success'])): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'success',
                title: 'Reset Link Sent!',
                text: 'A password reset link has been sent to your email. Check your inbox.',
                confirmButtonColor: '#3399ff',
                confirmButtonText: 'OK'
            });
        });
        </script>
        <?php elseif (isset($_GET['forgot_error'])): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            <?php if ($_GET['forgot_error'] === 'email_not_found'): ?>
            Swal.fire({ icon:'error', title:'Email Not Found',
                text:'No account is registered with that email address.',
                confirmButtonColor:'#3399ff' });
            <?php elseif ($_GET['forgot_error'] === 'mail_failed'): ?>
            Swal.fire({ icon:'error', title:'Email Failed to Send',
                text:'Could not send the reset email. Please check the mail configuration.',
                confirmButtonColor:'#3399ff' });
            <?php else: ?>
            Swal.fire({ icon:'error', title:'Something Went Wrong',
                text:'Please try again later.',
                confirmButtonColor:'#3399ff' });
            <?php endif; ?>
        });
        </script>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Username</label>
                <div class="input-wrap">
                    <input type="text" name="username" placeholder="Enter your username" required>
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-wrap">
                    <input type="password" name="password" id="pwInput" placeholder="Enter your password" required>
                    <i class="fas fa-eye-slash toggle-pw" id="togglePw"></i>
                </div>
            </div>

            <div class="login-meta">
                <label class="remember-me">
                    <input type="checkbox" name="remember"> Remember Me
                </label>
                <a href="#" class="forgot-link" id="openForgotModal">Forgot Your Password?</a>
            </div>

            <button type="submit" name="login" class="btn-login">Log In</button>
        </form>

        <div class="register-link">
            Don't Have An Account? <a href="../public/register.php">Register Now.</a>
        </div>

    </div><!-- end left-main -->

        <div class="powered-by">
            <i class="fas fa-chart-line"></i>
            Powered by <span>Data Analytics</span>
        </div>

    </div>

    <!-- ── RIGHT: Branding panel ── -->
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

<!-- ── Forgot Password Modal ── -->
<div class="fp-overlay" id="forgotOverlay">
    <div class="fp-modal">
        <h3><i class="fas fa-key" style="color:#3399ff;margin-right:8px"></i>Forgot Password</h3>
        <p>Enter the email address linked to your account and we'll send you a password reset link.</p>
        <form method="POST" action="forgot_password.php" id="forgotForm">
            <div class="fp-field">
                <label>Email Address</label>
                <input type="email" name="email" id="fpEmail" placeholder="your_email@gmail.com" required>
            </div>
            <div class="fp-actions">
                <button type="button" class="btn-cancel" id="closeForgotModal">Cancel</button>
                <button type="submit" class="btn-send">
                    <i class="fas fa-paper-plane" style="margin-right:6px"></i>Send Reset Link
                </button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/login.js"></script>

</body>
</html>

<?php
require_once '../class/Admin.php';

$admin = new Admin();
$token = trim($_GET['token'] ?? '');

// ── Handle form submission ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token    = trim($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        $postError = 'mismatch';
    } elseif (strlen($password) < 6) {
        $postError = 'short';
    } elseif ($admin->resetPassword($token, $password)) {
        header("Location: homepage.php?reset_success=1");
        exit();
    } else {
        $postError = 'failed';
    }
}

// ── Validate token for GET (and POST errors) ─────────────────────────────────
$validEmail = $admin->validateResetToken($token);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password — NORECO 1 WMS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/reset-password.css">
</head>
<body>

<video id="bg-video" autoplay loop muted playsinline>
    <source src="../assets/video/Final_Back.mp4" type="video/mp4">
</video>
<div id="bg-overlay"></div>

<div class="card">
    <div class="brand">
        <div class="brand-icon"><i class="fas fa-warehouse"></i></div>
        <div class="brand-text">
            <div class="b-name">NORECO 1 WMS</div>
            <div class="b-sub">Warehouse Monitoring System</div>
        </div>
    </div>

    <?php if (!$validEmail): ?>
    <!-- Token invalid or expired -->
    <div class="state-box">
        <div class="state-icon"><i class="fas fa-link-slash"></i></div>
        <h3>Link Expired or Invalid</h3>
        <p>This password reset link has expired or is no longer valid.<br>
           Please request a new one from the login page.</p>
        <a href="homepage.php" class="btn-back">
            <i class="fas fa-arrow-left" style="margin-right:6px"></i>Back to Login
        </a>
    </div>

    <?php else: ?>
    <!-- Valid token — show reset form -->
    <h2>Set New Password</h2>
    <p class="sub">Choose a strong new password for <strong><?= htmlspecialchars($validEmail) ?></strong></p>

    <?php if (isset($postError)): ?>
        <div class="msg-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php
            if ($postError === 'mismatch') echo 'Passwords do not match. Please try again.';
            elseif ($postError === 'short') echo 'Password must be at least 6 characters long.';
            else echo 'Something went wrong. Please request a new reset link.';
            ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="reset_password.php">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="field">
            <label>New Password</label>
            <div class="input-wrap">
                <input type="password" name="password" id="pw1" placeholder="Enter new password" required minlength="6">
                <i class="fas fa-eye-slash toggle-pw" id="t1"></i>
            </div>
        </div>

        <div class="field">
            <label>Confirm New Password</label>
            <div class="input-wrap">
                <input type="password" name="confirm_password" id="pw2" placeholder="Re-enter new password" required minlength="6">
                <i class="fas fa-eye-slash toggle-pw" id="t2"></i>
            </div>
        </div>

        <button type="submit" class="btn-submit">
            <i class="fas fa-shield-halved" style="margin-right:7px"></i>Save New Password
        </button>
    </form>

    <div class="back-link">
        <a href="homepage.php"><i class="fas fa-arrow-left" style="margin-right:4px"></i>Back to Login</a>
    </div>
    <?php endif; ?>
</div>

<script src="../assets/js/reset-password.js"></script>

</body>
</html>

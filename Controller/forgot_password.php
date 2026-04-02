<?php
require_once '../class/Admin.php';
require_once '../class/Mailer.php';

if (!isset($_POST['email'])) {
    header("Location: homepage.php");
    exit();
}

$email = trim($_POST['email']);
$admin = new Admin();

// Verify email is registered
if (!$admin->checkEmailExists($email)) {
    header("Location: homepage.php?forgot_error=email_not_found");
    exit();
}

// Generate reset token (stored in DB)
$token = $admin->createPasswordReset($email);
if (!$token) {
    header("Location: homepage.php?forgot_error=failed");
    exit();
}

// Build reset link (works on local WAMP and production)
$scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'];
$resetLink = "$scheme://$host/Warehouse/Controller/reset_password.php?token=$token";

// Send email
$mailer = new Mailer();
$sent   = $mailer->sendPasswordReset($email, $email, $resetLink);

if ($sent) {
    header("Location: homepage.php?forgot_success=1");
} else {
    header("Location: homepage.php?forgot_error=mail_failed");
}
exit();

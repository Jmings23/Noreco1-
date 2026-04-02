<?php
require_once '../class/Admin.php';

if (isset($_POST['register'])) {

    $username         = trim($_POST['username']);
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $admin = new Admin();

    // Validate email format
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../public/register.php?register_error=invalid_email");
        exit;
    }

    // Password match check
    if ($password !== $confirm_password) {
        header("Location: ../public/register.php?register_error=password_mismatch");
        exit;
    }

    // Username already taken
    if ($admin->checkUserExists($username)) {
        header("Location: ../public/register.php?register_error=user_exists");
        exit;
    }

    // Email already registered
    if ($admin->checkEmailExists($email)) {
        header("Location: ../public/register.php?register_error=email_exists");
        exit;
    }

    // Create account
    if ($admin->register($username, $password, $email)) {
        header("Location: ../public/register.php?register_success=1");
        exit;
    } else {
        header("Location: ../public/register.php?register_error=failed");
        exit;
    }
}

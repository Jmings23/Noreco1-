<?php
session_start();

// If user is already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: Controller/dashboard.php');
    exit();
}

// Default: redirect to login page
header('Location: Controller/homepage.php');
exit();

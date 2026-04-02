<?php
require_once '../class/Admin.php';

$user = new Admin();

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Call the correct method from Admin.php
    $login = $user->login($username, $password);

    if ($login === true) {
        header("Location: dashboard.php");
        exit();
    } else {
        // You can customize the error message if login fails
        header("Location: homepage.php?error=invalid_credentials");
        exit();
    }
}
?>

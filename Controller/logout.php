<?php
require_once '../class/Admin.php';

$user = new Admin();
$user->logout();

header("Location: homepage.php");
exit();

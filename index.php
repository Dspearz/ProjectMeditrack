<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

if ($_SESSION['role'] === 'admin') {
    header("Location: admin/dashboard.php");
    exit;
}

if ($_SESSION['role'] === 'patient') {
    header("Location: patient/dashboard.php");
    exit;
}

session_destroy();

header("Location: auth/login.php");
exit;

?>
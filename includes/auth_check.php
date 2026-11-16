<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin() {

    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit;
    }
}

function requireRole($role) {

    requireLogin();

    if ($_SESSION['role'] !== $role) {

        if ($_SESSION['role'] === 'admin') {
            header("Location: ../admin/dashboard.php");
        } else {
            header("Location: ../patient/dashboard.php");
        }

        exit;
    }
}

?>
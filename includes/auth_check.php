<?php

// Memulai session jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Memastikan pengguna sudah login
function requireLogin() {

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: ../auth/login.php");
        exit;
    }
}

// Memastikan role pengguna sesuai
function requireRole($role) {

    requireLogin();

    // Validasi role yang diperbolehkan
    $allowedRoles = ['admin', 'patient'];

    if (!in_array($_SESSION['role'], $allowedRoles, true)) {
        session_unset();
        session_destroy();

        header("Location: ../auth/login.php");
        exit;
    }

    // Jika role tidak sesuai, arahkan ke dashboard masing-masing
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
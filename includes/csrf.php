<?php

// Memastikan session sudah aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Membuat token CSRF
function generateCSRFToken() {

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Memvalidasi token CSRF
function validateCSRFToken() {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $token = $_POST['csrf_token'] ?? '';

    if (
        empty($token) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit('Permintaan ditolak. Token CSRF tidak valid.');
    }
}

?>
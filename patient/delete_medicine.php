<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";
require_once "../includes/csrf.php";

requireRole('patient');

// Pastikan hanya metode POST yang diperbolehkan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: medicines.php");
    exit;
}

// Validasi token CSRF
validateCSRFToken();

$user_id = $_SESSION['user_id'];

// Validasi ID obat
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: medicines.php?error=invalid");
    exit;
}

// Hapus hanya obat milik pasien yang login
$stmt = $pdo->prepare("
    DELETE FROM medicines
    WHERE id = ? AND user_id = ?
");

$stmt->execute([$id, $user_id]);

if ($stmt->rowCount() > 0) {
    header("Location: medicines.php?success=deleted");
} else {
    header("Location: medicines.php?error=notfound");
}

exit;
?>
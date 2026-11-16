<?php
require_once "../includes/auth_check.php";

requireRole('admin');

$name = htmlspecialchars($_SESSION['name']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - MediTrack</title>
</head>
<body>

    <h1>Dashboard Admin MediTrack</h1>

    <h2>
        Selamat datang, <?= $name ?>!
    </h2>

    <p>Panel pengelolaan sistem MediTrack.</p>

    <ul>
        <li><a href="#">Data Pasien</a></li>
        <li><a href="#">Data Obat</a></li>
        <li><a href="#">Aktivitas Pengguna</a></li>
    </ul>

    <a href="../auth/logout.php">Logout</a>

</body>
</html>
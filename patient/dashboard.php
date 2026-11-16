<?php
require_once "../includes/auth_check.php";

requireRole('patient');

$name = htmlspecialchars($_SESSION['name']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Pasien - MediTrack</title>
</head>
<body>

    <h1>Dashboard Pasien MediTrack</h1>

    <h2>
        Selamat datang, <?= $name ?>!
    </h2>

    <p>Kelola pengobatanmu dengan lebih teratur.</p>

    <ul>
        <li><a href="#">Data Obat</a></li>
        <a href="medicines.php" class="btn btn-primary">
    Kelola Data Obat
</a>
        <li><a href="#">Jadwal Konsumsi</a></li>
        <a href="schedules.php" class="btn btn-primary">
    Kelola Jadwal Obat
</a>
        <li><a href="#">Riwayat Pengobatan</a></li>
        <a href="medication_tracking.php" class="btn btn-primary">
    Catat Konsumsi Obat
</a>
       <br> <a href="medication_history.php" class="btn btn-outline-primary">
        Riwayat Konsumsi Obat
</a> </br>
    </ul>

    <a href="../auth/logout.php">Logout</a>

</body>
</html>

     
<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('admin');

$name = $_SESSION['name'];

// Menghitung total pasien
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM users 
    WHERE role = 'patient'
");
$stmt->execute();
$total_patients = $stmt->fetchColumn();

// Menghitung total obat
$stmt = $pdo->query("
    SELECT COUNT(*) FROM medicines
");
$total_medicines = $stmt->fetchColumn();

// Menghitung total jadwal konsumsi
$stmt = $pdo->query("
    SELECT COUNT(*) FROM medication_schedules
");
$total_schedules = $stmt->fetchColumn();

// Menghitung total catatan konsumsi
$stmt = $pdo->query("
    SELECT COUNT(*) FROM medication_logs
");
$total_logs = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Admin - MediTrack</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <!-- Header -->
    <div class="mb-4">
        <h1>Dashboard Admin MediTrack</h1>

        <h4>
            Selamat datang, <?= htmlspecialchars($name) ?>!
        </h4>

        <p class="text-muted">
            Panel pengelolaan dan pemantauan sistem MediTrack.
        </p>
    </div>

    <hr>

    <!-- Statistik -->
    <h4 class="mb-3">Ringkasan Sistem</h4>

    <div class="row g-3">

        <!-- Total Pasien -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Total Pasien</h6>
                    <h2 class="text-primary">
                        <?= $total_patients ?>
                    </h2>
                    <p class="mb-0 text-muted">
                        Akun pasien terdaftar
                    </p>
                </div>
            </div>
        </div>

        <!-- Total Obat -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Total Obat</h6>
                    <h2 class="text-success">
                        <?= $total_medicines ?>
                    </h2>
                    <p class="mb-0 text-muted">
                        Data obat tersimpan
                    </p>
                </div>
            </div>
        </div>

        <!-- Total Jadwal -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Total Jadwal</h6>
                    <h2 class="text-warning">
                        <?= $total_schedules ?>
                    </h2>
                    <p class="mb-0 text-muted">
                        Jadwal konsumsi obat
                    </p>
                </div>
            </div>
        </div>

        <!-- Total Catatan -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Total Aktivitas</h6>
                    <h2 class="text-info">
                        <?= $total_logs ?>
                    </h2>
                    <p class="mb-0 text-muted">
                        Catatan konsumsi obat
                    </p>
                </div>
            </div>
        </div>

    </div>

    <!-- Informasi Admin -->
    <div class="card shadow-sm border-0 mt-4">
        <div class="card-body">
            <h5>Informasi Sistem</h5>

            <p class="text-muted mb-0">
                MediTrack merupakan sistem informasi berbasis web
                untuk membantu pasien mengelola data obat,
                jadwal konsumsi, serta riwayat pengobatan.
                Admin bertugas mengelola akun dan memantau
                data yang tersimpan dalam sistem.
            </p>
        </div>
    </div>

   <br> <a href="patients.php" class="btn btn-primary">
    Kelola Data Pasien
</a>
</br>

<br>
<a href="activities.php" class="btn btn-outline-primary">
    Monitoring Aktivitas
</a>
</br>

    <!-- Logout -->
    <div class="mt-4">
        <a href="../auth/logout.php" class="btn btn-danger">
            Logout
        </a>
    </div>

</div>

</body>
</html>
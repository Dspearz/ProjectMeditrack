<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('admin');

$nama = $_SESSION['name'] ?? 'Admin';
$pageTitle = 'Dashboard Admin';

// Total pasien
$stmt = $pdo->query("
    SELECT COUNT(*) FROM users WHERE role = 'patient'
");
$totalPasien = (int) $stmt->fetchColumn();

// Total obat
$stmt = $pdo->query("
    SELECT COUNT(*) FROM medicines
");
$totalObat = (int) $stmt->fetchColumn();

// Total jadwal
$stmt = $pdo->query("
    SELECT COUNT(*) FROM medication_schedules
");
$totalJadwal = (int) $stmt->fetchColumn();

// Total aktivitas
$stmt = $pdo->query("
    SELECT COUNT(*) FROM medication_logs
");
$totalAktivitas = (int) $stmt->fetchColumn();

// Ringkasan status aktivitas
$stmt = $pdo->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'taken'), 0) AS taken,
        COALESCE(SUM(status = 'missed'), 0) AS missed,
        COALESCE(SUM(status = 'skipped'), 0) AS skipped
    FROM medication_logs
");
$aktivitas = $stmt->fetch(PDO::FETCH_ASSOC);

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<main class="main-content">

    <div class="page-header">
        <div>
            <h1>Dashboard Admin</h1>
            <p>Selamat datang, <?= htmlspecialchars($nama) ?>!</p>
        </div>
        <span class="badge text-bg-primary px-3 py-2">
            Administrator
        </span>
    </div>

    <div class="card mb-4"
         style="background: linear-gradient(120deg, #1e40af, #60a5fa); color: white;">
        <h2 class="h4 mb-2">Panel Administrasi MediTrack</h2>
        <p class="mb-0">
            Pantau data pasien dan aktivitas penggunaan sistem
            melalui satu dashboard terintegrasi.
        </p>
    </div>

    <h2 class="h5 mb-3">Statistik Sistem</h2>

    <div class="stats-grid">

        <div class="card stat-card">
            <h3>Total Pasien</h3>
            <div class="stat-value"><?= $totalPasien ?></div>
            <small class="text-secondary">Akun pasien terdaftar</small>
        </div>

        <div class="card stat-card">
            <h3>Total Obat</h3>
            <div class="stat-value"><?= $totalObat ?></div>
            <small class="text-secondary">Data obat tersimpan</small>
        </div>

        <div class="card stat-card">
            <h3>Total Jadwal</h3>
            <div class="stat-value"><?= $totalJadwal ?></div>
            <small class="text-secondary">Jadwal terdaftar</small>
        </div>

        <div class="card stat-card">
            <h3>Total Aktivitas</h3>
            <div class="stat-value"><?= $totalAktivitas ?></div>
            <small class="text-secondary">Catatan konsumsi obat</small>
        </div>

    </div>

    <div class="row g-4">

        <div class="col-lg-7">
            <div class="card h-100">
                <h2 class="h5 mb-3">Ringkasan Aktivitas Pasien</h2>

                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span>Obat dikonsumsi</span>
                    <strong class="text-success">
                        <?= (int) $aktivitas['taken'] ?>
                    </strong>
                </div>

                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span>Obat terlewat</span>
                    <strong class="text-danger">
                        <?= (int) $aktivitas['missed'] ?>
                    </strong>
                </div>

                <div class="d-flex justify-content-between py-2">
                    <span>Obat dilewati</span>
                    <strong class="text-warning">
                        <?= (int) $aktivitas['skipped'] ?>
                    </strong>
                </div>

                <p class="text-secondary small mt-3 mb-0">
                    Data berdasarkan catatan aktivitas yang tersimpan
                    dari seluruh pasien.
                </p>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <h2 class="h5 mb-3">Manajemen Sistem</h2>

                <div class="d-grid gap-2">
                    <a href="patients.php" class="btn btn-primary">
                        Kelola Data Pasien
                    </a>

                    <a href="activities.php" class="btn btn-outline-primary">
                        Pantau Aktivitas Pasien
                    </a>
                </div>
            </div>
        </div>

    </div>

    <div class="card mt-4">
        <h2 class="h5 mb-2">Informasi Administrator</h2>
        <p class="text-secondary mb-0">
            Administrator dapat melihat data pasien, memantau
            catatan aktivitas, dan mengawasi penggunaan MediTrack.
        </p>
    </div>

</main>

<?php require_once "../includes/footer.php"; ?>
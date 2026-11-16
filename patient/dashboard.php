<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('patient');

$user_id = $_SESSION['user_id'];
$nama = $_SESSION['name'] ?? 'Pasien';

$pageTitle = 'Dashboard Pasien';

// Menghitung statistik obat
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM medicines 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$totalObat = (int) $stmt->fetchColumn();

// Menghitung statistik jadwal aktif
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM medication_schedules 
    WHERE user_id = ? AND status = 'active'
");
$stmt->execute([$user_id]);
$totalJadwal = (int) $stmt->fetchColumn();

// Menghitung statistik riwayat
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'taken'), 0) AS taken,
        COALESCE(SUM(status = 'missed'), 0) AS missed,
        COALESCE(SUM(status = 'skipped'), 0) AS skipped
    FROM medication_logs
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$statistik = $stmt->fetch(PDO::FETCH_ASSOC);

$totalRiwayat = (int) $statistik['total'];
$totalDiambil = (int) $statistik['taken'];
$totalTerlewat = (int) $statistik['missed'];
$totalDilewati = (int) $statistik['skipped'];

$persentase = $totalRiwayat > 0
    ? round(($totalDiambil / $totalRiwayat) * 100)
    : 0;

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<main class="main-content">

    <div class="page-header">
        <div>
            <h1>Dashboard</h1>
            <p>Selamat datang kembali, <?= htmlspecialchars($nama) ?>!</p>
        </div>
        <div class="text-secondary">
            MediTrack Patient
        </div>
    </div>

    <div class="card mb-4" style="background: linear-gradient(120deg, #2563eb, #60a5fa); color: white;">
        <h2 class="h4 mb-2">Halo, <?= htmlspecialchars($nama) ?>!</h2>
        <p class="mb-0">
            Pantau jadwal obat dan riwayat pengobatanmu dengan lebih mudah bersama MediTrack.
        </p>
    </div>

    <h2 class="h5 mb-3">Ringkasan Aktivitas</h2>

    <div class="stats-grid">

        <div class="card stat-card">
            <h3>Total Obat</h3>
            <div class="stat-value"><?= $totalObat ?></div>
            <small class="text-secondary">Obat terdaftar</small>
        </div>

        <div class="card stat-card">
            <h3>Jadwal Aktif</h3>
            <div class="stat-value"><?= $totalJadwal ?></div>
            <small class="text-secondary">Jadwal berjalan</small>
        </div>

        <div class="card stat-card">
            <h3>Obat Dikonsumsi</h3>
            <div class="stat-value text-success"><?= $totalDiambil ?></div>
            <small class="text-secondary">Tercatat diminum</small>
        </div>

        <div class="card stat-card">
            <h3>Obat Terlewat</h3>
            <div class="stat-value text-danger"><?= $totalTerlewat ?></div>
            <small class="text-secondary">Tercatat terlewat</small>
        </div>

    </div>

    <div class="row g-4">

        <div class="col-lg-7">
            <div class="card h-100">
                <h2 class="h5 mb-3">Ringkasan Kepatuhan</h2>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Persentase konsumsi tercatat</span>
                    <strong><?= $persentase ?>%</strong>
                </div>

                <div class="progress" style="height: 12px;">
                    <div
                        class="progress-bar bg-success"
                        role="progressbar"
                        style="width: <?= $persentase ?>%;"
                        aria-valuenow="<?= $persentase ?>"
                        aria-valuemin="0"
                        aria-valuemax="100">
                    </div>
                </div>

                <p class="text-secondary small mt-3 mb-0">
                    Berdasarkan seluruh catatan aktivitas obat yang tersimpan di sistem.
                </p>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <h2 class="h5 mb-3">Akses Cepat</h2>

                <div class="d-grid gap-2">
                    <a href="medicines.php" class="btn btn-primary">
                        Kelola Daftar Obat
                    </a>

                    <a href="schedules.php" class="btn btn-outline-primary">
                        Lihat Jadwal Obat
                    </a>

                    <a href="medication_tracking.php" class="btn btn-outline-primary">
                        Catat Konsumsi Obat
                    </a>

                    <a href="medication_history.php" class="btn btn-outline-primary">
                        Lihat Riwayat Pengobatan
                    </a>
                </div>
            </div>
        </div>

    </div>

    <div class="card mt-4">
        <h2 class="h5 mb-2">Informasi MediTrack</h2>
        <p class="text-secondary mb-0">
            MediTrack membantu kamu mengelola informasi obat, jadwal konsumsi,
            serta riwayat aktivitas pengobatan dalam satu platform.
        </p>
    </div>

</main>

<?php require_once "../includes/footer.php"; ?>
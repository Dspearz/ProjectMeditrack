<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('patient');

$user_id = $_SESSION['user_id'];
$nama = $_SESSION['name'];

// Menghitung statistik konsumsi obat
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
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$total = (int) $stats['total'];
$taken = (int) $stats['taken'];
$missed = (int) $stats['missed'];
$skipped = (int) $stats['skipped'];

$adherence = $total > 0
    ? round(($taken / $total) * 100)
    : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pasien - MediTrack</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4">

    <h1>Dashboard Pasien MediTrack</h1>

    <h2>
        Selamat datang, <?= htmlspecialchars($nama) ?>!
    </h2>

    <p>Kelola pengobatanmu dengan lebih teratur.</p>

    <hr>

    <h4>Menu Pengobatan</h4>

    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="medicines.php" class="btn btn-primary">
            Kelola Data Obat
        </a>

        <a href="schedules.php" class="btn btn-primary">
            Kelola Jadwal Obat
        </a>

        <a href="medication_tracking.php" class="btn btn-success">
            Catat Konsumsi Obat
        </a>

        <a href="medication_history.php" class="btn btn-outline-primary">
            Riwayat Konsumsi Obat
        </a>
    </div>

    <hr>

    <h4 class="mb-3">Statistik Konsumsi Obat</h4>

    <div class="row g-3">

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Total Catatan</h6>
                    <h2><?= $total ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Sudah Diminum</h6>
                    <h2 class="text-success"><?= $taken ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Terlewat</h6>
                    <h2 class="text-danger"><?= $missed ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Dilewati</h6>
                    <h2 class="text-warning"><?= $skipped ?></h2>
                </div>
            </div>
        </div>

    </div>

    <div class="card shadow-sm mt-3">
        <div class="card-body">

            <h5>Persentase Kepatuhan</h5>

            <h2 class="text-success"><?= $adherence ?>%</h2>

            <div class="progress" style="height: 18px;">
                <div
                    class="progress-bar bg-success"
                    style="width: <?= $adherence ?>%">
                </div>
            </div>

            <small class="text-muted">
                Berdasarkan seluruh catatan konsumsi obat.
            </small>

        </div>
    </div>

    <div class="mt-4">
        <a href="../auth/logout.php" class="btn btn-danger">
            Logout
        </a>
    </div>

</div>

</body>
</html>
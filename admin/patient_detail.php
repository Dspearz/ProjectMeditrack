<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('admin');

$patient_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$patient_id) {
    header("Location: patients.php");
    exit;
}

// Ambil data pasien
$stmt = $pdo->prepare("
    SELECT id, name, email, created_at
    FROM users
    WHERE id = ? AND role = 'patient'
");

$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    header("Location: patients.php");
    exit;
}

// Ambil data obat pasien
$stmt = $pdo->prepare("
    SELECT *
    FROM medicines
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->execute([$patient_id]);
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil jadwal konsumsi pasien
$stmt = $pdo->prepare("
    SELECT
        s.*,
        m.medicine_name,
        m.dosage
    FROM medication_schedules s
    JOIN medicines m ON s.medicine_id = m.id
    WHERE s.user_id = ?
    ORDER BY s.schedule_time ASC
");

$stmt->execute([$patient_id]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil riwayat konsumsi pasien
$stmt = $pdo->prepare("
    SELECT
        ml.*,
        m.medicine_name,
        m.dosage
    FROM medication_logs ml
    JOIN medication_schedules s ON ml.schedule_id = s.id
    JOIN medicines m ON s.medicine_id = m.id
    WHERE ml.user_id = ?
    ORDER BY ml.scheduled_at DESC
    LIMIT 50
");

$stmt->execute([$patient_id]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Detail Pasien - MediTrack</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <a href="patients.php" class="btn btn-outline-secondary mb-4">
        Kembali ke Data Pasien
    </a>

    <h2>Detail Pasien</h2>

    <!-- Identitas Pasien -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <h4><?= htmlspecialchars($patient['name']) ?></h4>

            <p class="mb-1">
                Email: <?= htmlspecialchars($patient['email']) ?>
            </p>

            <p class="mb-0 text-muted">
                Terdaftar:
                <?= date('d-m-Y', strtotime($patient['created_at'])) ?>
            </p>

        </div>
    </div>

    <!-- Data Obat -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <h5>Daftar Obat (<?= count($medicines) ?>)</h5>

            <div class="table-responsive">

                <table class="table table-hover">

                    <thead>
                        <tr>
                            <th>Nama Obat</th>
                            <th>Dosis</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($medicines): ?>

                        <?php foreach ($medicines as $medicine): ?>

                            <tr>
                                <td><?= htmlspecialchars($medicine['medicine_name']) ?></td>
                                <td><?= htmlspecialchars($medicine['dosage']) ?></td>
                                <td><?= htmlspecialchars($medicine['start_date']) ?></td>
                                <td><?= htmlspecialchars($medicine['end_date'] ?? '-') ?></td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="4" class="text-center">
                                Belum ada data obat.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>
    </div>

    <!-- Jadwal Konsumsi -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <h5>Jadwal Konsumsi (<?= count($schedules) ?>)</h5>

            <div class="table-responsive">

                <table class="table table-hover">

                    <thead>
                        <tr>
                            <th>Nama Obat</th>
                            <th>Dosis</th>
                            <th>Waktu</th>
                            <th>Mulai</th>
                            <th>Selesai</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($schedules): ?>

                        <?php foreach ($schedules as $schedule): ?>

                            <tr>
                                <td><?= htmlspecialchars($schedule['medicine_name']) ?></td>
                                <td><?= htmlspecialchars($schedule['dosage']) ?></td>
                                <td><?= htmlspecialchars($schedule['schedule_time']) ?></td>
                                <td><?= htmlspecialchars($schedule['start_date']) ?></td>
                                <td><?= htmlspecialchars($schedule['end_date'] ?? '-') ?></td>
                                <td>
                                    <span class="badge bg-<?= $schedule['status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= htmlspecialchars($schedule['status']) ?>
                                    </span>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="text-center">
                                Belum ada jadwal konsumsi.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>
    </div>

    <!-- Riwayat Konsumsi -->
    <div class="card shadow-sm">
        <div class="card-body">

            <h5>Riwayat Konsumsi (Maksimal 50 Catatan)</h5>

            <div class="table-responsive">

                <table class="table table-hover">

                    <thead>
                        <tr>
                            <th>Nama Obat</th>
                            <th>Jadwal</th>
                            <th>Waktu Dicatat</th>
                            <th>Status</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($logs): ?>

                        <?php foreach ($logs as $log): ?>

                            <tr>
                                <td><?= htmlspecialchars($log['medicine_name']) ?></td>

                                <td>
                                    <?= date('d-m-Y H:i', strtotime($log['scheduled_at'])) ?>
                                </td>

                                <td>
                                    <?= $log['taken_at']
                                        ? date('d-m-Y H:i', strtotime($log['taken_at']))
                                        : '-' ?>
                                </td>

                                <td>
                                    <?php
                                    $badge = [
                                        'taken' => 'success',
                                        'missed' => 'danger',
                                        'skipped' => 'warning'
                                    ];

                                    $label = [
                                        'taken' => 'Sudah Diminum',
                                        'missed' => 'Terlewat',
                                        'skipped' => 'Dilewati'
                                    ];
                                    ?>

                                    <span class="badge bg-<?= $badge[$log['status']] ?>">
                                        <?= $label[$log['status']] ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars($log['notes'] ?? '-') ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="text-center">
                                Belum ada riwayat konsumsi.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>
    </div>

</div>

</body>
</html>
<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('patient');

$user_id = $_SESSION['user_id'];

// Filter tanggal dan status
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "
    SELECT 
        ml.id,
        ml.scheduled_at,
        ml.taken_at,
        ml.status,
        ml.notes,
        m.medicine_name,
        m.dosage,
        s.schedule_time
    FROM medication_logs ml
    JOIN medication_schedules s 
        ON ml.schedule_id = s.id
    JOIN medicines m 
        ON s.medicine_id = m.id
    WHERE ml.user_id = ?
";

$params = [$user_id];

if (!empty($start_date)) {
    $sql .= " AND DATE(ml.scheduled_at) >= ?";
    $params[] = $start_date;
}

if (!empty($end_date)) {
    $sql .= " AND DATE(ml.scheduled_at) <= ?";
    $params[] = $end_date;
}

if (in_array($status, ['taken', 'missed', 'skipped'])) {
    $sql .= " AND ml.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY ml.scheduled_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Konsumsi Obat - MediTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Riwayat Konsumsi Obat</h2>
            <p class="text-muted">Catatan aktivitas konsumsi obat Anda.</p>
        </div>

        <a href="dashboard.php" class="btn btn-outline-secondary">
            Kembali
        </a>
    </div>

    <!-- Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="start_date"
                           class="form-control"
                           value="<?= htmlspecialchars($start_date) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="end_date"
                           class="form-control"
                           value="<?= htmlspecialchars($end_date) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="taken" <?= $status === 'taken' ? 'selected' : '' ?>>
                            Sudah Diminum
                        </option>
                        <option value="missed" <?= $status === 'missed' ? 'selected' : '' ?>>
                            Terlewat
                        </option>
                        <option value="skipped" <?= $status === 'skipped' ? 'selected' : '' ?>>
                            Dilewati
                        </option>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        Filter
                    </button>

                    <a href="medication_history.php"
                       class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>

            </form>
        </div>
    </div>

    <!-- Tabel Riwayat -->
    <div class="card shadow-sm">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Obat</th>
                            <th>Dosis</th>
                            <th>Jadwal</th>
                            <th>Waktu Dicatat</th>
                            <th>Status</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($history) > 0): ?>

                        <?php foreach ($history as $index => $item): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>

                                <td>
                                    <?= htmlspecialchars($item['medicine_name']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($item['dosage']) ?>
                                </td>

                                <td>
                                    <?= date('d-m-Y H:i', strtotime($item['scheduled_at'])) ?>
                                </td>

                                <td>
                                    <?= $item['taken_at']
                                        ? date('d-m-Y H:i', strtotime($item['taken_at']))
                                        : '-' ?>
                                </td>

                                <td>
                                    <?php
                                    $badges = [
                                        'taken' => 'success',
                                        'missed' => 'danger',
                                        'skipped' => 'warning'
                                    ];

                                    $labels = [
                                        'taken' => 'Sudah Diminum',
                                        'missed' => 'Terlewat',
                                        'skipped' => 'Dilewati'
                                    ];
                                    ?>

                                    <span class="badge bg-<?= $badges[$item['status']] ?>">
                                        <?= $labels[$item['status']] ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars($item['notes'] ?? '-') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Belum ada riwayat konsumsi obat.
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
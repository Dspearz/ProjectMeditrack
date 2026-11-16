<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('admin');

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

if (!in_array($status, ['', 'taken', 'missed', 'skipped'])) {
    $status = '';
}

$sql = "
    SELECT
        ml.id,
        ml.scheduled_at,
        ml.taken_at,
        ml.status,
        ml.notes,
        u.name AS patient_name,
        m.medicine_name,
        m.dosage
    FROM medication_logs ml
    JOIN users u ON ml.user_id = u.id
    JOIN medication_schedules s ON ml.schedule_id = s.id
    JOIN medicines m ON s.medicine_id = m.id
    WHERE u.role = 'patient'
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            u.name LIKE ?
            OR m.medicine_name LIKE ?
        )
    ";

    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status !== '') {
    $sql .= " AND ml.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY ml.scheduled_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Aktivitas Pengguna - MediTrack</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2>Aktivitas Pengguna</h2>
            <p class="text-muted">
                Pantau catatan konsumsi obat seluruh pasien.
            </p>
        </div>

        <a href="dashboard.php" class="btn btn-outline-secondary">
            Kembali
        </a>

    </div>

    <!-- Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <form method="GET" class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Cari Pasien atau Obat</label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Nama pasien atau nama obat..."
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Filter Status</label>

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

                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        Filter
                    </button>

                    <a href="activities.php" class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>

            </form>

        </div>
    </div>

    <!-- Tabel Aktivitas -->
    <div class="card shadow-sm">

        <div class="card-body">

            <h5 class="mb-3">
                Daftar Aktivitas (<?= count($activities) ?>)
            </h5>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Pasien</th>
                            <th>Nama Obat</th>
                            <th>Dosis</th>
                            <th>Jadwal</th>
                            <th>Waktu Dicatat</th>
                            <th>Status</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($activities): ?>

                        <?php foreach ($activities as $index => $activity): ?>

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

                            <tr>

                                <td><?= $index + 1 ?></td>

                                <td>
                                    <?= htmlspecialchars($activity['patient_name']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($activity['medicine_name']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($activity['dosage']) ?>
                                </td>

                                <td>
                                    <?= date('d-m-Y H:i', strtotime($activity['scheduled_at'])) ?>
                                </td>

                                <td>
                                    <?= $activity['taken_at']
                                        ? date('d-m-Y H:i', strtotime($activity['taken_at']))
                                        : '-' ?>
                                </td>

                                <td>
                                    <span class="badge bg-<?= $badges[$activity['status']] ?>">
                                        <?= $labels[$activity['status']] ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars($activity['notes'] ?? '-') ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Belum ada aktivitas yang ditemukan.
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
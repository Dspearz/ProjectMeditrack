<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('patient');

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$errors = [];
$success = false;

// Proses pencatatan konsumsi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $schedule_id = filter_input(
        INPUT_POST,
        'schedule_id',
        FILTER_VALIDATE_INT
    );

    $status = $_POST['status'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if (!$schedule_id || $schedule_id < 1) {
        $errors[] = "Jadwal tidak valid.";
    }

    if (!in_array($status, ['taken', 'missed', 'skipped'], true)) {
        $errors[] = "Status konsumsi tidak valid.";
    }

    // Pastikan jadwal milik pasien dan berlaku hari ini
    if (empty($errors)) {

        $check = $pdo->prepare("
            SELECT id, schedule_time
            FROM medication_schedules
            WHERE id = ?
              AND user_id = ?
              AND status = 'active'
              AND start_date <= ?
              AND (end_date IS NULL OR end_date >= ?)
        ");

        $check->execute([
            $schedule_id,
            $user_id,
            $today,
            $today
        ]);

        $schedule = $check->fetch(PDO::FETCH_ASSOC);

        if (!$schedule) {
            $errors[] = "Jadwal tidak ditemukan atau tidak berlaku hari ini.";
        }
    }

    if (empty($errors)) {

        $scheduled_at = $today . ' ' . $schedule['schedule_time'];

        $taken_at = ($status === 'taken')
            ? date('Y-m-d H:i:s')
            : null;

        // Simpan atau perbarui catatan jadwal hari ini
        $insert = $pdo->prepare("
            INSERT INTO medication_logs
            (
                user_id,
                schedule_id,
                scheduled_at,
                taken_at,
                status,
                notes
            )
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                taken_at = VALUES(taken_at),
                status = VALUES(status),
                notes = VALUES(notes)
        ");

        $insert->execute([
            $user_id,
            $schedule_id,
            $scheduled_at,
            $taken_at,
            $status,
            $notes
        ]);

        header("Location: medication_tracking.php?success=1");
        exit;
    }
}

// Ambil jadwal aktif hari ini beserta catatannya
$stmt = $pdo->prepare("
    SELECT
        s.id AS schedule_id,
        s.schedule_time,
        s.instructions,
        m.medicine_name,
        m.dosage,
        l.status AS log_status,
        l.notes AS log_notes
    FROM medication_schedules s
    INNER JOIN medicines m
        ON s.medicine_id = m.id
    LEFT JOIN medication_logs l
        ON l.schedule_id = s.id
        AND l.scheduled_at = CONCAT(?, ' ', s.schedule_time)
        AND l.user_id = s.user_id
    WHERE s.user_id = ?
      AND s.status = 'active'
      AND s.start_date <= ?
      AND (s.end_date IS NULL OR s.end_date >= ?)
    ORDER BY s.schedule_time ASC
");

$stmt->execute([
    $today,
    $user_id,
    $today,
    $today
]);

$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pencatatan Konsumsi - MediTrack</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <h2>Pencatatan Konsumsi Obat</h2>

    <p class="text-muted">
        Tanggal:
        <?= date('d-m-Y', strtotime($today)) ?>
    </p>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            Catatan konsumsi berhasil disimpan!
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (empty($schedules)): ?>

        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <h5>Belum Ada Jadwal Hari Ini</h5>
                <p class="text-muted">
                    Tidak ada jadwal konsumsi obat yang aktif hari ini.
                </p>
                <a href="schedules.php" class="btn btn-primary">
                    Lihat Jadwal
                </a>
            </div>
        </div>

    <?php else: ?>

        <?php foreach ($schedules as $schedule): ?>

            <div class="card shadow-sm mb-3">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5>
                                <?= htmlspecialchars($schedule['medicine_name']) ?>
                            </h5>

                            <p class="mb-1">
                                Dosis: <?= htmlspecialchars($schedule['dosage']) ?>
                            </p>

                            <p class="mb-1">
                                Waktu:
                                <strong>
                                    <?= htmlspecialchars(substr($schedule['schedule_time'], 0, 5)) ?>
                                </strong>
                            </p>

                            <p class="text-muted">
                                <?= htmlspecialchars($schedule['instructions'] ?? '') ?>
                            </p>
                        </div>

                        <div>
                            <?php
                            $status = $schedule['log_status'];

                            if ($status === 'taken') {
                                echo '<span class="badge bg-success">Sudah Diminum</span>';
                            } elseif ($status === 'missed') {
                                echo '<span class="badge bg-danger">Terlewat</span>';
                            } elseif ($status === 'skipped') {
                                echo '<span class="badge bg-warning text-dark">Dilewati</span>';
                            } else {
                                echo '<span class="badge bg-secondary">Belum Dicatat</span>';
                            }
                            ?>
                        </div>
                    </div>

                    <hr>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="schedule_id"
                            value="<?= (int)$schedule['schedule_id'] ?>"
                        >

                        <div class="mb-3">
                            <label class="form-label">
                                Status Konsumsi
                            </label>

                            <select name="status" class="form-select" required>
                                <option value="taken"
                                    <?= $schedule['log_status'] === 'taken' ? 'selected' : '' ?>>
                                    Sudah Diminum
                                </option>

                                <option value="missed"
                                    <?= $schedule['log_status'] === 'missed' ? 'selected' : '' ?>>
                                    Terlewat
                                </option>

                                <option value="skipped"
                                    <?= $schedule['log_status'] === 'skipped' ? 'selected' : '' ?>>
                                    Dilewati
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Catatan (Opsional)
                            </label>

                            <textarea
                                name="notes"
                                class="form-control"
                                rows="2"
                                placeholder="Tambahkan catatan..."
                            ><?= htmlspecialchars($schedule['log_notes'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Simpan Catatan
                        </button>

                    </form>

                </div>
            </div>

        <?php endforeach; ?>

    <?php endif; ?>

    <a href="dashboard.php" class="btn btn-outline-secondary mt-2">
        Kembali ke Dashboard
    </a>

</div>

</body>
</html>
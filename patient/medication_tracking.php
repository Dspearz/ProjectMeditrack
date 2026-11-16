<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('patient');

date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$errors = [];

$pageTitle = "Pelacakan Konsumsi Obat";
$currentPage = "medication_tracking";

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

// Statistik
$totalSchedules = count($schedules);
$totalTaken = 0;
$totalMissed = 0;
$totalSkipped = 0;
$totalPending = 0;

foreach ($schedules as $schedule) {
    if ($schedule['log_status'] === 'taken') {
        $totalTaken++;
    } elseif ($schedule['log_status'] === 'missed') {
        $totalMissed++;
    } elseif ($schedule['log_status'] === 'skipped') {
        $totalSkipped++;
    } else {
        $totalPending++;
    }
}

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h1>Pelacakan Konsumsi Obat</h1>
            <p class="text-muted">
                Pantau dan catat konsumsi obat harianmu.
            </p>
        </div>

        <div class="text-muted">
            <?= date('d-m-Y', strtotime($today)) ?>
        </div>
    </div>

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

    <!-- Statistik -->
    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-info">
                <span>Total Jadwal</span>
                <h3><?= $totalSchedules ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span>Sudah Diminum</span>
                <h3><?= $totalTaken ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span>Terlewat</span>
                <h3><?= $totalMissed ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span>Belum Dicatat</span>
                <h3><?= $totalPending ?></h3>
            </div>
        </div>

    </div>

    <!-- Daftar Jadwal -->
    <div class="card">

        <div class="mb-4">
            <h4>Jadwal Obat Hari Ini</h4>
            <p class="text-muted mb-0">
                Perbarui status konsumsi sesuai aktivitasmu.
            </p>
        </div>

        <?php if (empty($schedules)): ?>

            <div class="text-center py-5">
                <h5>Belum Ada Jadwal Hari Ini</h5>

                <p class="text-muted">
                    Tidak ada jadwal konsumsi obat yang aktif hari ini.
                </p>

                <a href="schedules.php" class="btn btn-primary">
                    Lihat Jadwal
                </a>
            </div>

        <?php else: ?>

            <div class="table-container">

                <table>
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Nama Obat</th>
                            <th>Dosis</th>
                            <th>Instruksi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($schedules as $schedule): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars(
                                            substr($schedule['schedule_time'], 0, 5)
                                        ) ?>
                                    </strong>
                                    <br>
                                    <small class="text-muted">WIB</small>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($schedule['medicine_name']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($schedule['dosage']) ?>
                                </td>

                                <td>
                                    <?= !empty($schedule['instructions'])
                                        ? htmlspecialchars($schedule['instructions'])
                                        : '-' ?>
                                </td>

                                <td>

                                    <?php if ($schedule['log_status'] === 'taken'): ?>

                                        <span class="status status-success">
                                            Sudah Diminum
                                        </span>

                                    <?php elseif ($schedule['log_status'] === 'missed'): ?>

                                        <span class="status status-danger">
                                            Terlewat
                                        </span>

                                    <?php elseif ($schedule['log_status'] === 'skipped'): ?>

                                        <span class="status status-warning">
                                            Dilewati
                                        </span>

                                    <?php else: ?>

                                        <span class="status">
                                            Belum Dicatat
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-primary btn-sm"
                                        onclick="openTrackingForm(
                                            <?= (int) $schedule['schedule_id'] ?>
                                        )"
                                    >
                                        Catat Status
                                    </button>
                                </td>

                            </tr>

                            <!-- Form pencatatan -->
                            <tr id="tracking-form-<?= (int) $schedule['schedule_id'] ?>"
                                style="display: none;">

                                <td colspan="6">

                                    <form method="POST" class="p-3">

                                        <input
                                            type="hidden"
                                            name="schedule_id"
                                            value="<?= (int) $schedule['schedule_id'] ?>"
                                        >

                                        <h6 class="mb-3">
                                            Catat Konsumsi:
                                            <?= htmlspecialchars($schedule['medicine_name']) ?>
                                        </h6>

                                        <div class="row">

                                            <div class="col-md-5 mb-3">
                                                <label class="form-label">
                                                    Status Konsumsi
                                                </label>

                                                <select
                                                    name="status"
                                                    class="form-control"
                                                    required
                                                >

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

                                            <div class="col-md-7 mb-3">
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

                                        </div>

                                        <button
                                            type="submit"
                                            class="btn btn-primary"
                                        >
                                            Simpan Catatan
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-secondary"
                                            onclick="closeTrackingForm(
                                                <?= (int) $schedule['schedule_id'] ?>
                                            )"
                                        >
                                            Batal
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>

            </div>

        <?php endif; ?>

    </div>

    <!-- Keterangan -->
    <div class="card mt-4">

        <h5>Keterangan Status</h5>

        <div class="d-flex flex-wrap gap-3 mt-3">

            <span class="status status-success">
                Sudah Diminum
            </span>

            <span class="status status-danger">
                Terlewat
            </span>

            <span class="status status-warning">
                Dilewati
            </span>

            <span class="status">
                Belum Dicatat
            </span>

        </div>

    </div>

</div>

<script>
function openTrackingForm(id) {
    document.getElementById('tracking-form-' + id).style.display = 'table-row';
}

function closeTrackingForm(id) {
    document.getElementById('tracking-form-' + id).style.display = 'none';
}
</script>

<?php require_once "../includes/footer.php"; ?>
<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('admin');

$pageTitle = "Detail Pasien";
$currentPage = "patients";

$patient_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$patient_id || $patient_id < 1) {
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

// Statistik jadwal
$totalActiveSchedules = 0;
$totalInactiveSchedules = 0;

foreach ($schedules as $schedule) {
    if ($schedule['status'] === 'active') {
        $totalActiveSchedules++;
    } else {
        $totalInactiveSchedules++;
    }
}

// Statistik riwayat
$totalTaken = 0;
$totalMissed = 0;
$totalSkipped = 0;

foreach ($logs as $log) {
    if ($log['status'] === 'taken') {
        $totalTaken++;
    } elseif ($log['status'] === 'missed') {
        $totalMissed++;
    } elseif ($log['status'] === 'skipped') {
        $totalSkipped++;
    }
}

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<div class="main-content">

    <!-- Header -->
    <div class="page-header">
        <div>
            <h1>Detail Pasien</h1>
            <p class="text-muted">
                Informasi lengkap dan aktivitas pasien MediTrack.
            </p>
        </div>

        <a href="patients.php" class="btn btn-secondary">
            Kembali ke Data Pasien
        </a>
    </div>

    <!-- Profil Pasien -->
    <div class="card mb-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">

            <div>
                <h3 class="mb-2">
                    <?= htmlspecialchars($patient['name']) ?>
                </h3>

                <p class="text-muted mb-1">
                    <?= htmlspecialchars($patient['email']) ?>
                </p>

                <small class="text-muted">
                    Terdaftar sejak
                    <?= date('d-m-Y', strtotime($patient['created_at'])) ?>
                </small>
            </div>

            <span class="status status-success">
                Akun Pasien
            </span>

        </div>

    </div>

    <!-- Statistik -->
    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-info">
                <span>Total Obat</span>
                <h3><?= count($medicines) ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span>Total Jadwal</span>
                <h3><?= count($schedules) ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span>Jadwal Aktif</span>
                <h3><?= $totalActiveSchedules ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span>Total Riwayat</span>
                <h3><?= count($logs) ?></h3>
            </div>
        </div>

    </div>

    <!-- Daftar Obat -->
    <div class="card mb-4">

        <div class="mb-4">
            <h4>Daftar Obat</h4>
            <p class="text-muted mb-0">
                Total <?= count($medicines) ?> obat yang terdaftar.
            </p>
        </div>

        <?php if (empty($medicines)): ?>

            <div class="text-center py-4">
                <h5>Belum Ada Data Obat</h5>
                <p class="text-muted mb-0">
                    Pasien belum menambahkan data obat.
                </p>
            </div>

        <?php else: ?>

            <div class="table-container">

                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Obat</th>
                            <th>Dosis</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($medicines as $index => $medicine): ?>

                            <tr>
                                <td><?= $index + 1 ?></td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($medicine['medicine_name']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($medicine['dosage']) ?>
                                </td>

                                <td>
                                    <?= date(
                                        'd-m-Y',
                                        strtotime($medicine['start_date'])
                                    ) ?>
                                </td>

                                <td>
                                    <?= !empty($medicine['end_date'])
                                        ? date('d-m-Y', strtotime($medicine['end_date']))
                                        : '-' ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>

            </div>

        <?php endif; ?>

    </div>

    <!-- Jadwal Konsumsi -->
    <div class="card mb-4">

        <div class="mb-4">
            <h4>Jadwal Konsumsi</h4>
            <p class="text-muted mb-0">
                Total <?= count($schedules) ?> jadwal konsumsi pasien.
            </p>
        </div>

        <?php if (empty($schedules)): ?>

            <div class="text-center py-4">
                <h5>Belum Ada Jadwal Konsumsi</h5>
                <p class="text-muted mb-0">
                    Pasien belum membuat jadwal konsumsi obat.
                </p>
            </div>

        <?php else: ?>

            <div class="table-container">

                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Obat</th>
                            <th>Dosis</th>
                            <th>Waktu</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($schedules as $index => $schedule): ?>

                            <tr>
                                <td><?= $index + 1 ?></td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($schedule['medicine_name']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($schedule['dosage']) ?>
                                </td>

                                <td>
                                    <?= date(
                                        'H:i',
                                        strtotime($schedule['schedule_time'])
                                    ) ?>
                                    WIB
                                </td>

                                <td>
                                    <?= date(
                                        'd-m-Y',
                                        strtotime($schedule['start_date'])
                                    ) ?>
                                </td>

                                <td>
                                    <?= !empty($schedule['end_date'])
                                        ? date('d-m-Y', strtotime($schedule['end_date']))
                                        : '-' ?>
                                </td>

                                <td>
                                    <?php if ($schedule['status'] === 'active'): ?>

                                        <span class="status status-success">
                                            Aktif
                                        </span>

                                    <?php else: ?>

                                        <span class="status">
                                            Tidak Aktif
                                        </span>

                                    <?php endif; ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>

            </div>

        <?php endif; ?>

    </div>

    <!-- Riwayat Konsumsi -->
    <div class="card mb-4">

        <div class="mb-4">
            <h4>Riwayat Konsumsi Obat</h4>
            <p class="text-muted mb-0">
                Menampilkan maksimal 50 catatan konsumsi terbaru.
            </p>
        </div>

        <!-- Statistik Riwayat -->
        <div class="stats-grid">

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
                    <span>Dilewati</span>
                    <h3><?= $totalSkipped ?></h3>
                </div>
            </div>

        </div>

        <?php if (empty($logs)): ?>

            <div class="text-center py-4">
                <h5>Belum Ada Riwayat Konsumsi</h5>
                <p class="text-muted mb-0">
                    Belum terdapat catatan konsumsi obat dari pasien.
                </p>
            </div>

        <?php else: ?>

            <div class="table-container">

                <table>
                    <thead>
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

                        <?php foreach ($logs as $index => $log): ?>

                            <tr>

                                <td><?= $index + 1 ?></td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($log['medicine_name']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($log['dosage']) ?>
                                </td>

                                <td>
                                    <?= date(
                                        'd-m-Y H:i',
                                        strtotime($log['scheduled_at'])
                                    ) ?>
                                    WIB
                                </td>

                                <td>
                                    <?= !empty($log['taken_at'])
                                        ? date('d-m-Y H:i', strtotime($log['taken_at'])) . ' WIB'
                                        : '-' ?>
                                </td>

                                <td>

                                    <?php if ($log['status'] === 'taken'): ?>

                                        <span class="status status-success">
                                            Sudah Diminum
                                        </span>

                                    <?php elseif ($log['status'] === 'missed'): ?>

                                        <span class="status status-danger">
                                            Terlewat
                                        </span>

                                    <?php elseif ($log['status'] === 'skipped'): ?>

                                        <span class="status status-warning">
                                            Dilewati
                                        </span>

                                    <?php else: ?>

                                        <span class="status">
                                            Tidak Diketahui
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>
                                    <?= !empty($log['notes'])
                                        ? htmlspecialchars($log['notes'])
                                        : '-' ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>

            </div>

        <?php endif; ?>

    </div>

    <a href="patients.php" class="btn btn-secondary">
        Kembali ke Data Pasien
    </a>

</div>

<?php require_once "../includes/footer.php"; ?>
<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('admin');

date_default_timezone_set('Asia/Jakarta');

$pageTitle = 'Aktivitas Pengguna';
$currentPage = 'activities';

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

if (!in_array($status, ['', 'taken', 'missed', 'skipped'], true)) {
    $status = '';
}

// Mengambil ringkasan aktivitas seluruh pasien
$stmt = $pdo->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'taken'), 0) AS taken,
        COALESCE(SUM(status = 'missed'), 0) AS missed,
        COALESCE(SUM(status = 'skipped'), 0) AS skipped
    FROM medication_logs ml
    JOIN users u ON ml.user_id = u.id
    WHERE u.role = 'patient'
");

$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Mengambil data aktivitas
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

$totalAktivitas = (int) $summary['total'];

$persentaseTaken = $totalAktivitas > 0
    ? round(($summary['taken'] / $totalAktivitas) * 100)
    : 0;

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<main class="main-content">

    <!-- Header -->
    <div class="page-header">

        <div>
            <h1>Aktivitas Pengguna</h1>

            <p class="text-muted">
                Pantau catatan konsumsi obat seluruh pasien MediTrack.
            </p>
        </div>

        <span class="status status-success">
            Monitoring
        </span>

    </div>

    <!-- Ringkasan -->
    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-info">
                <span>Total Aktivitas</span>
                <h3><?= $totalAktivitas ?></h3>
                <small class="text-muted">Seluruh catatan</small>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span>Sudah Diminum</span>
                <h3 class="text-success">
                    <?= (int) $summary['taken'] ?>
                </h3>
                <small class="text-muted">Status taken</small>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span>Terlewat</span>
                <h3 class="text-danger">
                    <?= (int) $summary['missed'] ?>
                </h3>
                <small class="text-muted">Status missed</small>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span>Dilewati</span>
                <h3 class="text-warning">
                    <?= (int) $summary['skipped'] ?>
                </h3>
                <small class="text-muted">Status skipped</small>
            </div>
        </div>

    </div>

    <!-- Persentase -->
    <div class="card mt-4 mb-4">

        <div class="d-flex justify-content-between align-items-center mb-2">

            <div>
                <h2 class="h5 mb-1">
                    Ringkasan Pencatatan
                </h2>

                <p class="text-muted small mb-0">
                    Persentase catatan berstatus sudah diminum.
                </p>
            </div>

            <strong class="text-success">
                <?= $persentaseTaken ?>%
            </strong>

        </div>

        <div
            class="progress"
            role="progressbar"
            aria-label="Persentase aktivitas sudah diminum"
            aria-valuenow="<?= $persentaseTaken ?>"
            aria-valuemin="0"
            aria-valuemax="100"
            style="height: 10px;"
        >
            <div
                class="progress-bar bg-success"
                style="width: <?= $persentaseTaken ?>%;"
            ></div>
        </div>

    </div>

    <!-- Filter -->
    <div class="card mb-4">

        <h2 class="h5 mb-3">
            Cari dan Filter Aktivitas
        </h2>

        <form method="GET" class="row g-3">

            <div class="col-lg-6">

                <label class="form-label">
                    Cari Pasien atau Obat
                </label>

                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Masukkan nama pasien atau obat..."
                    value="<?= htmlspecialchars($search) ?>"
                >

            </div>

            <div class="col-lg-4">

                <label class="form-label">
                    Filter Status
                </label>

                <select name="status" class="form-control">

                    <option value="">
                        Semua Status
                    </option>

                    <option
                        value="taken"
                        <?= $status === 'taken' ? 'selected' : '' ?>
                    >
                        Sudah Diminum
                    </option>

                    <option
                        value="missed"
                        <?= $status === 'missed' ? 'selected' : '' ?>
                    >
                        Terlewat
                    </option>

                    <option
                        value="skipped"
                        <?= $status === 'skipped' ? 'selected' : '' ?>
                    >
                        Dilewati
                    </option>

                </select>

            </div>

            <div class="col-lg-2 d-flex align-items-end gap-2">

                <button type="submit" class="btn btn-primary">
                    Filter
                </button>

                <a
                    href="activities.php"
                    class="btn btn-outline-secondary"
                >
                    Reset
                </a>

            </div>

        </form>

    </div>

    <!-- Tabel Aktivitas -->
    <div class="card">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">

            <div>
                <h2 class="h5 mb-1">
                    Daftar Aktivitas Pasien
                </h2>

                <p class="text-muted small mb-0">
                    Menampilkan <?= count($activities) ?> catatan aktivitas.
                </p>
            </div>

        </div>

        <div class="table-container">

            <table>

                <thead>
                    <tr>
                        <th>No.</th>
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

                <?php if (!empty($activities)): ?>

                    <?php foreach ($activities as $index => $activity): ?>

                        <?php
                        $badges = [
                            'taken' => 'status-success',
                            'missed' => 'status-danger',
                            'skipped' => 'status-warning'
                        ];

                        $labels = [
                            'taken' => 'Sudah Diminum',
                            'missed' => 'Terlewat',
                            'skipped' => 'Dilewati'
                        ];

                        $activityStatus = $activity['status'];
                        ?>

                        <tr>

                            <td>
                                <?= $index + 1 ?>
                            </td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($activity['patient_name']) ?>
                                </strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($activity['medicine_name']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($activity['dosage']) ?>
                            </td>

                            <td>
                                <?= date(
                                    'd-m-Y H:i',
                                    strtotime($activity['scheduled_at'])
                                ) ?>
                            </td>

                            <td>
                                <?= $activity['taken_at']
                                    ? date(
                                        'd-m-Y H:i',
                                        strtotime($activity['taken_at'])
                                    )
                                    : '-' ?>
                            </td>

                            <td>
                                <span class="status <?= $badges[$activityStatus] ?? '' ?>">
                                    <?= $labels[$activityStatus] ?? 'Tidak diketahui' ?>
                                </span>
                            </td>

                            <td>
                                <?= htmlspecialchars($activity['notes'] ?? '-') ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <strong>Belum ada aktivitas ditemukan.</strong>
                            <p class="text-muted small mb-0">
                                Coba ubah kata kunci pencarian atau filter status.
                            </p>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</main>

<?php require_once "../includes/footer.php"; ?>
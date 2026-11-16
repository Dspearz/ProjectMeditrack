<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('patient');

date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$pageTitle = "Riwayat Konsumsi Obat";
$currentPage = "medication_history";

// Filter
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$allowedStatuses = ['taken', 'missed', 'skipped'];

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

if (
    $startDate !== '' &&
    !DateTime::createFromFormat('Y-m-d', $startDate)
) {
    $startDate = '';
}

if (
    $endDate !== '' &&
    !DateTime::createFromFormat('Y-m-d', $endDate)
) {
    $endDate = '';
}

$conditions = ["ml.user_id = ?"];
$params = [$user_id];

if ($startDate !== '') {
    $conditions[] = "DATE(ml.scheduled_at) >= ?";
    $params[] = $startDate;
}

if ($endDate !== '') {
    $conditions[] = "DATE(ml.scheduled_at) <= ?";
    $params[] = $endDate;
}

if ($statusFilter !== '') {
    $conditions[] = "ml.status = ?";
    $params[] = $statusFilter;
}

$where = implode(" AND ", $conditions);

// Ambil riwayat konsumsi
$query = "
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
    INNER JOIN medication_schedules s
        ON ml.schedule_id = s.id
    INNER JOIN medicines m
        ON s.medicine_id = m.id
    WHERE $where
    ORDER BY ml.scheduled_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);

$histories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistik berdasarkan hasil filter
$totalHistory = count($histories);
$totalTaken = 0;
$totalMissed = 0;
$totalSkipped = 0;

foreach ($histories as $history) {
    if ($history['status'] === 'taken') {
        $totalTaken++;
    } elseif ($history['status'] === 'missed') {
        $totalMissed++;
    } elseif ($history['status'] === 'skipped') {
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
            <h1>Riwayat Konsumsi Obat</h1>
            <p class="text-muted">
                Lihat kembali catatan konsumsi obat yang telah kamu lakukan.
            </p>
        </div>
    </div>

    <!-- Statistik -->
    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-info">
                <span>Total Riwayat</span>
                <h3><?= $totalHistory ?></h3>
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
                <span>Dilewati</span>
                <h3><?= $totalSkipped ?></h3>
            </div>
        </div>

    </div>

    <!-- Filter -->
    <div class="card mb-4">

        <div class="mb-3">
            <h4>Filter Riwayat</h4>
            <p class="text-muted mb-0">
                Cari riwayat berdasarkan tanggal dan status konsumsi.
            </p>
        </div>

        <form method="GET">

            <div class="row">

                <div class="col-md-3 mb-3">
                    <label class="form-label">
                        Tanggal Mulai
                    </label>

                    <input
                        type="date"
                        name="start_date"
                        class="form-control"
                        value="<?= htmlspecialchars($startDate) ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">
                        Tanggal Akhir
                    </label>

                    <input
                        type="date"
                        name="end_date"
                        class="form-control"
                        value="<?= htmlspecialchars($endDate) ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">
                        Status Konsumsi
                    </label>

                    <select
                        name="status"
                        class="form-control"
                    >
                        <option value="">Semua Status</option>

                        <option value="taken"
                            <?= $statusFilter === 'taken' ? 'selected' : '' ?>>
                            Sudah Diminum
                        </option>

                        <option value="missed"
                            <?= $statusFilter === 'missed' ? 'selected' : '' ?>>
                            Terlewat
                        </option>

                        <option value="skipped"
                            <?= $statusFilter === 'skipped' ? 'selected' : '' ?>>
                            Dilewati
                        </option>

                    </select>
                </div>

                <div class="col-md-3 mb-3 d-flex align-items-end gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Filter
                    </button>

                    <a
                        href="medication_history.php"
                        class="btn btn-secondary"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </form>

    </div>

    <!-- Tabel Riwayat -->
    <div class="card">

        <div class="mb-4">
            <h4>Daftar Riwayat</h4>
            <p class="text-muted mb-0">
                Menampilkan <?= $totalHistory ?> catatan konsumsi obat.
            </p>
        </div>

        <?php if (empty($histories)): ?>

            <div class="text-center py-5">

                <h5>Belum Ada Riwayat Konsumsi</h5>

                <p class="text-muted">
                    Belum ada catatan yang sesuai dengan filter yang dipilih.
                </p>

                <a
                    href="medication_tracking.php"
                    class="btn btn-primary"
                >
                    Catat Konsumsi Obat
                </a>

            </div>

        <?php else: ?>

            <div class="table-container">

                <table>

                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Waktu Jadwal</th>
                            <th>Nama Obat</th>
                            <th>Dosis</th>
                            <th>Status</th>
                            <th>Waktu Dicatat</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($histories as $history): ?>

                            <tr>

                                <td>
                                    <?= date(
                                        'd-m-Y',
                                        strtotime($history['scheduled_at'])
                                    ) ?>
                                </td>

                                <td>
                                    <?= date(
                                        'H:i',
                                        strtotime($history['scheduled_at'])
                                    ) ?>
                                    WIB
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($history['medicine_name']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($history['dosage']) ?>
                                </td>

                                <td>

                                    <?php if ($history['status'] === 'taken'): ?>

                                        <span class="status status-success">
                                            Sudah Diminum
                                        </span>

                                    <?php elseif ($history['status'] === 'missed'): ?>

                                        <span class="status status-danger">
                                            Terlewat
                                        </span>

                                    <?php elseif ($history['status'] === 'skipped'): ?>

                                        <span class="status status-warning">
                                            Dilewati
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>
                                    <?php if (!empty($history['taken_at'])): ?>

                                        <?= date(
                                            'd-m-Y H:i',
                                            strtotime($history['taken_at'])
                                        ) ?>
                                        WIB

                                    <?php else: ?>

                                        <span class="text-muted">-</span>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= !empty($history['notes'])
                                        ? htmlspecialchars($history['notes'])
                                        : '<span class="text-muted">-</span>' ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

    <div class="mt-4">
        <a
            href="dashboard.php"
            class="btn btn-secondary"
        >
            Kembali ke Dashboard
        </a>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>
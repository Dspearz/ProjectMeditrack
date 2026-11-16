<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";
require_once "../includes/csrf.php";

requireRole('patient');

$user_id = $_SESSION['user_id'];

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

if (!in_array($status, ['', 'active', 'completed'], true)) {
    $status = '';
}

$allowedSort = [
    'newest' => 'created_at DESC',
    'oldest' => 'created_at ASC',
    'name' => 'medicine_name ASC'
];

$orderBy = $allowedSort[$sort] ?? 'created_at DESC';

$sql = "
    SELECT *,
        CASE
            WHEN start_date > CURDATE() THEN 'upcoming'
            WHEN end_date IS NOT NULL AND end_date < CURDATE() THEN 'completed'
            ELSE 'active'
        END AS medicine_status
    FROM medicines
    WHERE user_id = ?
";

$params = [$user_id];

if ($search !== '') {
    $sql .= " AND medicine_name LIKE ?";
    $params[] = "%$search%";
}

if ($status === 'active') {
    $sql .= " AND start_date <= CURDATE()
              AND (end_date IS NULL OR end_date >= CURDATE())";
} elseif ($status === 'completed') {
    $sql .= " AND end_date IS NOT NULL
              AND end_date < CURDATE()";
}

$sql .= " ORDER BY $orderBy";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Daftar Obat';

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<main class="main-content">

    <!-- Header Halaman -->
    <div class="page-header">
        <div>
            <h1>Daftar Obat</h1>
            <p>Kelola dan pantau informasi obat yang kamu konsumsi.</p>
        </div>

        <a href="add_medicine.php" class="btn btn-primary">
            + Tambah Obat
        </a>
    </div>

    <!-- Notifikasi -->
    <?php if (isset($_GET['success'])): ?>

        <?php if ($_GET['success'] === 'added'): ?>
            <div class="alert alert-success">
                Data obat berhasil ditambahkan!
            </div>
        <?php elseif ($_GET['success'] === 'updated'): ?>
            <div class="alert alert-success">
                Data obat berhasil diperbarui!
            </div>
        <?php elseif ($_GET['success'] === 'deleted'): ?>
            <div class="alert alert-success">
                Data obat berhasil dihapus!
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'notfound'): ?>
        <div class="alert alert-warning">
            Data obat tidak ditemukan.
        </div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'invalid'): ?>
        <div class="alert alert-warning">
            ID obat tidak valid.
        </div>
    <?php endif; ?>

    <!-- Statistik Ringkas -->
    <div class="stats-grid">

        <div class="card stat-card">
            <h3>Total Obat Ditampilkan</h3>
            <div class="stat-value"><?= count($medicines) ?></div>
            <small class="text-secondary">Sesuai pencarian dan filter</small>
        </div>

        <div class="card stat-card">
            <h3>Obat Aktif</h3>
            <div class="stat-value text-success">
                <?= count(array_filter($medicines, fn($m) => $m['medicine_status'] === 'active')) ?>
            </div>
            <small class="text-secondary">Sedang berjalan</small>
        </div>

        <div class="card stat-card">
            <h3>Obat Selesai</h3>
            <div class="stat-value text-secondary">
                <?= count(array_filter($medicines, fn($m) => $m['medicine_status'] === 'completed')) ?>
            </div>
            <small class="text-secondary">Masa pengobatan selesai</small>
        </div>

        <div class="card stat-card">
            <h3>Belum Dimulai</h3>
            <div class="stat-value text-warning">
                <?= count(array_filter($medicines, fn($m) => $m['medicine_status'] === 'upcoming')) ?>
            </div>
            <small class="text-secondary">Tanggal mulai mendatang</small>
        </div>

    </div>

    <!-- Pencarian dan Filter -->
    <div class="card mb-4">

        <h2 class="h5 mb-3">Cari dan Filter Obat</h2>

        <form method="GET" class="row g-3">

            <div class="col-lg-4">
                <label class="form-label">Nama Obat</label>
                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Cari nama obat..."
                    value="<?= htmlspecialchars($search) ?>"
                >
            </div>

            <div class="col-lg-3">
                <label class="form-label">Status Obat</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>
                        Aktif
                    </option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>
                        Selesai
                    </option>
                </select>
            </div>

            <div class="col-lg-3">
                <label class="form-label">Urutkan</label>
                <select name="sort" class="form-select">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>
                        Terbaru
                    </option>
                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>
                        Terlama
                    </option>
                    <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>
                        Nama Obat A-Z
                    </option>
                </select>
            </div>

            <div class="col-lg-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    Cari
                </button>

                <a href="medicines.php" class="btn btn-outline-secondary">
                    Reset
                </a>
            </div>

        </form>
    </div>

    <!-- Tabel Obat -->
    <div class="card">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h5 mb-1">Data Obat Saya</h2>
                <small class="text-secondary">
                    <?= count($medicines) ?> data ditemukan
                </small>
            </div>
        </div>

        <?php if (count($medicines) > 0): ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Obat</th>
                            <th>Dosis</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($medicines as $medicine): ?>

                            <tr>
                                <td>
                                    <strong>
                                        <?= htmlspecialchars($medicine['medicine_name']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($medicine['dosage']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($medicine['start_date']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($medicine['end_date'] ?? '-') ?>
                                </td>

                                <td>
                                    <?php if ($medicine['medicine_status'] === 'active'): ?>
                                        <span class="status status-success">Aktif</span>
                                    <?php elseif ($medicine['medicine_status'] === 'completed'): ?>
                                        <span class="status status-danger">Selesai</span>
                                    <?php else: ?>
                                        <span class="status status-warning">Belum Dimulai</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="d-flex gap-2">

                                        <a
                                            href="edit_medicine.php?id=<?= urlencode($medicine['id']) ?>"
                                            class="btn btn-sm btn-warning">
                                            Edit
                                        </a>

                                        <form
                                            action="delete_medicine.php"
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus obat ini?')">

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= htmlspecialchars($medicine['id']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= htmlspecialchars(generateCSRFToken()) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger">
                                                Hapus
                                            </button>

                                        </form>

                                    </div>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>

        <?php else: ?>

            <div class="text-center py-5">
                <div class="mb-3">
                    <span style="font-size: 45px;">💊</span>
                </div>

                <h3 class="h5">Belum Ada Data Obat</h3>

                <p class="text-secondary">
                    Tidak ada obat yang sesuai dengan pencarian atau filter.
                </p>

                <a href="medicines.php" class="btn btn-outline-primary">
                    Reset Pencarian
                </a>
            </div>

        <?php endif; ?>

    </div>

</main>

<?php require_once "../includes/footer.php"; ?>
<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('patient');

$user_id = $_SESSION['user_id'];

// Mengambil input pencarian dan filter
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Validasi filter
if (!in_array($status, ['', 'active', 'completed'])) {
    $status = '';
}

$allowedSort = [
    'newest' => 'created_at DESC',
    'oldest' => 'created_at ASC',
    'name' => 'medicine_name ASC'
];

$orderBy = $allowedSort[$sort] ?? 'created_at DESC';

// Query dasar
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

// Pencarian nama obat
if ($search !== '') {
    $sql .= " AND medicine_name LIKE ?";
    $params[] = "%$search%";
}

// Filter status
if ($status === 'active') {
    $sql .= " AND start_date <= CURDATE()
              AND (end_date IS NULL OR end_date >= CURDATE())";
} elseif ($status === 'completed') {
    $sql .= " AND end_date IS NOT NULL
              AND end_date < CURDATE()";
}

// Pengurutan
$sql .= " ORDER BY $orderBy";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Data Obat - MediTrack</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Data Obat</h2>
            <p class="text-muted">
                Kelola daftar obat yang sedang kamu konsumsi.
            </p>
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
    <?php endif; ?>

    <!-- Form Pencarian dan Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <form method="GET" class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Cari Nama Obat</label>
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Masukkan nama obat..."
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>

                <div class="col-md-3">
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

                <div class="col-md-3">
                    <label class="form-label">Urutkan</label>
                    <select name="sort" class="form-select">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>
                            Terbaru
                        </option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>
                            Terlama
                        </option>
                        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>
                            Nama Obat (A-Z)
                        </option>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        Cari
                    </button>

                    <a href="medicines.php" class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>

            </form>

        </div>
    </div>

    <!-- Tabel Data Obat -->
    <div class="card shadow-sm">
        <div class="card-body">

            <h5 class="mb-3">
                Daftar Obat (<?= count($medicines) ?>)
            </h5>

            <?php if (count($medicines) > 0): ?>

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead class="table-light">
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
                                        <?= htmlspecialchars($medicine['medicine_name']) ?>
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

                                            <span class="badge bg-success">
                                                Aktif
                                            </span>

                                        <?php elseif ($medicine['medicine_status'] === 'completed'): ?>

                                            <span class="badge bg-secondary">
                                                Selesai
                                            </span>

                                        <?php else: ?>

                                            <span class="badge bg-warning text-dark">
                                                Belum Dimulai
                                            </span>

                                        <?php endif; ?>
                                    </td>

                                    <td>

                                        <a
                                            href="edit_medicine.php?id=<?= $medicine['id'] ?>"
                                            class="btn btn-sm btn-warning">
                                            Edit
                                        </a>

                                        <form
                                            action="delete_medicine.php"
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus obat ini?')"
                                            class="d-inline">

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= htmlspecialchars($medicine['id']) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger">
                                                Hapus
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="text-center py-4">

                    <p class="text-muted">
                        Tidak ada data obat yang sesuai.
                    </p>

                    <a href="medicines.php" class="btn btn-outline-primary">
                        Reset Pencarian
                    </a>

                </div>

            <?php endif; ?>

        </div>
    </div>

    <a href="dashboard.php" class="btn btn-outline-secondary mt-3">
        Kembali ke Dashboard
    </a>

</div>

</body>
</html>
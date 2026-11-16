<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('patient');

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT * FROM medicines
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);

$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (isset($_GET['success']) && $_GET['success'] === 'updated'): ?>
    <div class="alert alert-success">
        Data obat berhasil diperbarui!
    </div>
<?php endif; ?>

<?php if (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
    <div class="alert alert-success">
        Data obat berhasil dihapus!
    </div>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'notfound'): ?>
    <div class="alert alert-warning">
        Data obat tidak ditemukan.
    </div>
<?php endif; ?>

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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Data Obat</h2>
            <p class="text-muted">Kelola daftar obat yang sedang kamu konsumsi.</p>
        </div>
        <a href="add_medicine.php" class="btn btn-primary">
            + Tambah Obat
        </a>
    </div>

<?php if (isset($_GET['success']) && $_GET['success'] === 'added'): ?>
    <div class="alert alert-success">
        Data obat berhasil ditambahkan!
    </div>
<?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (count($medicines) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nama Obat</th>
                                <th>Dosis</th>
                                <th>Tanggal Mulai</th>
                                <th>Tanggal Selesai</th>
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
                                        <a href="edit_medicine.php?id=<?= $medicine['id'] ?>"
                                           class="btn btn-sm btn-warning">Edit</a>

                                       <form action="delete_medicine.php" method="POST"
      onsubmit="return confirm('Yakin ingin menghapus obat ini?')"
      class="d-inline">

    <input type="hidden" name="id"
           value="<?= htmlspecialchars($medicine['id']) ?>">

    <button type="submit" class="btn btn-sm btn-danger">
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
                    <p class="text-muted">Belum ada data obat.</p>
                    <a href="add_medicine.php" class="btn btn-primary">
                        Tambahkan Obat Pertama
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
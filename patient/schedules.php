<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('patient');

$user_id = $_SESSION['user_id'];

// Mengambil jadwal beserta nama obat
$stmt = $pdo->prepare("
    SELECT
        medication_schedules.*,
        medicines.medicine_name,
        medicines.dosage
    FROM medication_schedules
    INNER JOIN medicines
        ON medication_schedules.medicine_id = medicines.id
    WHERE medication_schedules.user_id = ?
    ORDER BY medication_schedules.schedule_time ASC
");

$stmt->execute([$user_id]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Obat - MediTrack</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Jadwal Konsumsi Obat</h2>
            <p class="text-muted">
                Kelola jadwal konsumsi obat harianmu.
            </p>
        </div>

        <a href="add_schedule.php" class="btn btn-primary">
            + Tambah Jadwal
        </a>
    </div>

<?php if (isset($_GET['success']) && $_GET['success'] === 'added'): ?>
    <div class="alert alert-success">
        Jadwal konsumsi obat berhasil ditambahkan!
    </div>
<?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <?php if (count($schedules) > 0): ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">

                        <thead>
                            <tr>
                                <th>Aksi</th>
                                <th>Nama Obat</th>
                                <th>Dosis</th>
                                <th>Waktu</th>
                                <th>Tanggal Mulai</th>
                                <th>Tanggal Selesai</th>
                                <th>Status</th>
                                
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($schedules as $schedule): ?>
                                
                                <tr>
                                    <td>
    <a href="edit_schedule.php?id=<?= $schedule['id'] ?>"
       class="btn btn-sm btn-warning">
        Edit
    </a>
     <form action="delete_schedule.php"
          method="POST"
          class="d-inline"
          onsubmit="return confirm('Yakin ingin menghapus jadwal ini?')">

        <input type="hidden" name="id"
               value="<?= (int)$schedule['id'] ?>">

        <button type="submit" class="btn btn-sm btn-danger">
            Hapus
        </button>
    </form>
</td>
                                    <td>
                                        <?= htmlspecialchars($schedule['medicine_name']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($schedule['dosage']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(substr($schedule['schedule_time'], 0, 5)) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($schedule['start_date']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($schedule['end_date'] ?? '-') ?>
                                    </td>

                                    <td>
                                        <?php if ($schedule['status'] === 'active'): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Tidak Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>

            <?php else: ?>

                <div class="text-center py-4">
                    <p class="text-muted">
                        Belum ada jadwal konsumsi obat.
                    </p>

                    <a href="add_schedule.php" class="btn btn-primary">
                        Buat Jadwal Pertama
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
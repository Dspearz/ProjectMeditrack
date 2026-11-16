<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('patient');

$user_id = $_SESSION['user_id'];
$pageTitle = 'Jadwal Obat';

// Mengambil seluruh jadwal beserta informasi obat
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

// Menghitung statistik jadwal
$totalJadwal = count($schedules);

$jadwalAktif = count(array_filter(
    $schedules,
    fn($schedule) => $schedule['status'] === 'active'
));

$jadwalNonaktif = $totalJadwal - $jadwalAktif;

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<main class="main-content">

    <!-- Header -->
    <div class="page-header">
        <div>
            <h1>Jadwal Konsumsi Obat</h1>
            <p>Atur dan pantau waktu konsumsi obat secara teratur.</p>
        </div>

        <a href="add_schedule.php" class="btn btn-primary">
            + Tambah Jadwal
        </a>
    </div>

    <!-- Notifikasi -->
    <?php if (isset($_GET['success'])): ?>

        <?php if ($_GET['success'] === 'added'): ?>
            <div class="alert alert-success">
                Jadwal konsumsi obat berhasil ditambahkan!
            </div>
        <?php elseif ($_GET['success'] === 'updated'): ?>
            <div class="alert alert-success">
                Jadwal konsumsi obat berhasil diperbarui!
            </div>
        <?php elseif ($_GET['success'] === 'deleted'): ?>
            <div class="alert alert-success">
                Jadwal konsumsi obat berhasil dihapus!
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <!-- Statistik -->
    <div class="stats-grid">

        <div class="card stat-card">
            <h3>Total Jadwal</h3>
            <div class="stat-value"><?= $totalJadwal ?></div>
            <small class="text-secondary">Seluruh jadwal terdaftar</small>
        </div>

        <div class="card stat-card">
            <h3>Jadwal Aktif</h3>
            <div class="stat-value text-success"><?= $jadwalAktif ?></div>
            <small class="text-secondary">Jadwal berjalan</small>
        </div>

        <div class="card stat-card">
            <h3>Jadwal Tidak Aktif</h3>
            <div class="stat-value text-secondary"><?= $jadwalNonaktif ?></div>
            <small class="text-secondary">Jadwal dinonaktifkan</small>
        </div>

    </div>

    <!-- Daftar Jadwal -->
    <div class="card">

        <div class="mb-3">
            <h2 class="h5 mb-1">Daftar Jadwal Saya</h2>
            <small class="text-secondary">
                Berikut seluruh jadwal konsumsi obat yang telah dibuat.
            </small>
        </div>

        <?php if ($totalJadwal > 0): ?>

            <div class="table-container">

                <table>
                    <thead>
                        <tr>
                            <th>Nama Obat</th>
                            <th>Dosis</th>
                            <th>Waktu Konsumsi</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($schedules as $schedule): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($schedule['medicine_name']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($schedule['dosage']) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars(substr($schedule['schedule_time'], 0, 5)) ?>
                                    </strong>
                                    <small class="text-secondary"> WIB</small>
                                </td>

                                <td>
                                    <?= htmlspecialchars($schedule['start_date']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($schedule['end_date'] ?? '-') ?>
                                </td>

                                <td>
                                    <?php if ($schedule['status'] === 'active'): ?>
                                        <span class="status status-success">
                                            Aktif
                                        </span>
                                    <?php else: ?>
                                        <span class="status status-danger">
                                            Tidak Aktif
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="d-flex gap-2">

                                        <a
                                            href="edit_schedule.php?id=<?= urlencode($schedule['id']) ?>"
                                            class="btn btn-sm btn-warning">
                                            Edit
                                        </a>

                                        <form
                                            action="delete_schedule.php"
                                            method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Yakin ingin menghapus jadwal ini?')">

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int) $schedule['id'] ?>"
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
                    <span style="font-size: 45px;">⏰</span>
                </div>

                <h3 class="h5">Belum Ada Jadwal Obat</h3>

                <p class="text-secondary">
                    Kamu belum membuat jadwal konsumsi obat.
                    Yuk, buat jadwal pertamamu!
                </p>

                <a href="add_schedule.php" class="btn btn-primary">
                    + Buat Jadwal Pertama
                </a>

            </div>

        <?php endif; ?>

    </div>

</main>

<?php require_once "../includes/footer.php"; ?>
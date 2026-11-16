<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('patient');

$user_id = $_SESSION['user_id'];
$errors = [];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: schedules.php");
    exit;
}

// Ambil jadwal milik pasien
$stmt = $pdo->prepare("
    SELECT *
    FROM medication_schedules
    WHERE id = ? AND user_id = ?
");
$stmt->execute([$id, $user_id]);

$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    header("Location: schedules.php");
    exit;
}

// Ambil obat milik pasien
$stmt = $pdo->prepare("
    SELECT id, medicine_name, dosage
    FROM medicines
    WHERE user_id = ?
    ORDER BY medicine_name ASC
");
$stmt->execute([$user_id]);

$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fungsi validasi tanggal
function validScheduleDate($date) {
    $d = DateTime::createFromFormat('!Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Proses pembaruan jadwal
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $medicine_id = filter_input(
        INPUT_POST,
        'medicine_id',
        FILTER_VALIDATE_INT
    );

    $schedule_time = $_POST['schedule_time'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $instructions = trim($_POST['instructions'] ?? '');
    $status = $_POST['status'] ?? '';

    // Validasi kepemilikan obat
    $check = $pdo->prepare("
        SELECT id FROM medicines
        WHERE id = ? AND user_id = ?
    ");

    $check->execute([$medicine_id, $user_id]);

    if (!$medicine_id || !$check->fetch()) {
        $errors[] = "Pilihan obat tidak valid.";
    }

    // Validasi waktu
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $schedule_time)) {
        $errors[] = "Waktu konsumsi tidak valid.";
    }

    // Validasi tanggal
    if (!validScheduleDate($start_date)) {
        $errors[] = "Tanggal mulai tidak valid.";
    }

    if ($end_date !== '' && !validScheduleDate($end_date)) {
        $errors[] = "Tanggal selesai tidak valid.";
    }

    if (
        $end_date !== '' &&
        validScheduleDate($start_date) &&
        validScheduleDate($end_date) &&
        $end_date < $start_date
    ) {
        $errors[] = "Tanggal selesai tidak boleh sebelum tanggal mulai.";
    }

    // Validasi status
    if (!in_array($status, ['active', 'inactive'], true)) {
        $errors[] = "Status jadwal tidak valid.";
    }

    // Update database
    if (empty($errors)) {

        $update = $pdo->prepare("
            UPDATE medication_schedules
            SET medicine_id = ?,
                schedule_time = ?,
                start_date = ?,
                end_date = ?,
                instructions = ?,
                status = ?
            WHERE id = ? AND user_id = ?
        ");

        $update->execute([
            $medicine_id,
            $schedule_time,
            $start_date,
            $end_date !== '' ? $end_date : null,
            $instructions,
            $status,
            $id,
            $user_id
        ]);

        header("Location: schedules.php?success=updated");
        exit;
    }
}

$pageTitle = "Edit Jadwal Obat";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<main class="main-content">

    <div class="page-header">
        <div>
            <h1>Edit Jadwal Obat</h1>
            <p class="text-muted">
                Perbarui informasi jadwal konsumsi obat.
            </p>
        </div>

        <a href="schedules.php" class="btn btn-outline-secondary">
            Kembali
        </a>
    </div>

    <div class="card">
        <div class="card-body p-4">

            <div class="mb-4">
                <h5>Informasi Jadwal</h5>
                <p class="text-muted mb-0">
                    Ubah informasi jadwal sesuai kebutuhan.
                </p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <strong>Periksa kembali data kamu:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (empty($medicines)): ?>

                <div class="alert alert-warning">
                    Belum ada data obat. Tambahkan obat terlebih dahulu.
                </div>

                <a href="add_medicine.php" class="btn btn-primary">
                    Tambah Data Obat
                </a>

            <?php else: ?>

                <form method="POST">

                    <div class="form-group mb-3">
                        <label for="medicine_id" class="form-label">
                            Pilih Obat <span class="text-danger">*</span>
                        </label>

                        <select
                            name="medicine_id"
                            id="medicine_id"
                            class="form-control"
                            required
                        >

                            <?php foreach ($medicines as $medicine): ?>
                                <option
                                    value="<?= $medicine['id'] ?>"
                                    <?= (($_POST['medicine_id'] ?? $schedule['medicine_id']) == $medicine['id']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars(
                                        $medicine['medicine_name'] .
                                        ' - ' .
                                        $medicine['dosage']
                                    ) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="schedule_time" class="form-label">
                            Waktu Konsumsi <span class="text-danger">*</span>
                        </label>

                        <input
                            type="time"
                            name="schedule_time"
                            id="schedule_time"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['schedule_time'] ?? substr($schedule['schedule_time'], 0, 5)) ?>"
                            required
                        >
                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="start_date" class="form-label">
                                    Tanggal Mulai <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="date"
                                    name="start_date"
                                    id="start_date"
                                    class="form-control"
                                    value="<?= htmlspecialchars($_POST['start_date'] ?? $schedule['start_date']) ?>"
                                    required
                                >
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="end_date" class="form-label">
                                    Tanggal Selesai
                                </label>

                                <input
                                    type="date"
                                    name="end_date"
                                    id="end_date"
                                    class="form-control"
                                    value="<?= htmlspecialchars($_POST['end_date'] ?? $schedule['end_date'] ?? '') ?>"
                                >

                                <small class="text-muted">
                                    Kosongkan jika tanggal selesai belum ditentukan.
                                </small>
                            </div>
                        </div>

                    </div>

                    <div class="form-group mb-3">
                        <label for="instructions" class="form-label">
                            Instruksi Tambahan
                        </label>

                        <textarea
                            name="instructions"
                            id="instructions"
                            class="form-control"
                            rows="4"
                            placeholder="Contoh: Dikonsumsi setelah makan."
                        ><?= htmlspecialchars($_POST['instructions'] ?? $schedule['instructions'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label for="status" class="form-label">
                            Status Jadwal <span class="text-danger">*</span>
                        </label>

                        <select
                            name="status"
                            id="status"
                            class="form-control"
                            required
                        >
                            <option
                                value="active"
                                <?= (($_POST['status'] ?? $schedule['status']) === 'active') ? 'selected' : '' ?>
                            >
                                Aktif
                            </option>

                            <option
                                value="inactive"
                                <?= (($_POST['status'] ?? $schedule['status']) === 'inactive') ? 'selected' : '' ?>
                            >
                                Tidak Aktif
                            </option>
                        </select>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex flex-wrap gap-2">

                        <button type="submit" class="btn btn-primary">
                            Simpan Perubahan
                        </button>

                        <a href="schedules.php" class="btn btn-outline-secondary">
                            Batal
                        </a>

                    </div>

                </form>

            <?php endif; ?>

        </div>
    </div>

</main>

<?php require_once "../includes/footer.php"; ?>
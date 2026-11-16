<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('patient');

$user_id = $_SESSION['user_id'];
$errors = [];

// Ambil daftar obat milik pasien
$stmt = $pdo->prepare("
    SELECT id, medicine_name, dosage
    FROM medicines
    WHERE user_id = ?
    ORDER BY medicine_name ASC
");
$stmt->execute([$user_id]);
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fungsi validasi tanggal
function validDate($date) {
    $d = DateTime::createFromFormat('!Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Proses formulir
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

    // Validasi obat
    $checkMedicine = $pdo->prepare("
        SELECT id FROM medicines
        WHERE id = ? AND user_id = ?
    ");

    $checkMedicine->execute([$medicine_id, $user_id]);

    if (!$medicine_id || !$checkMedicine->fetch()) {
        $errors[] = "Obat tidak valid atau tidak ditemukan.";
    }

    // Validasi waktu
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $schedule_time)) {
        $errors[] = "Waktu konsumsi tidak valid.";
    }

    // Validasi tanggal
    if (!validDate($start_date)) {
        $errors[] = "Tanggal mulai tidak valid.";
    }

    if (
        $end_date !== '' &&
        !validDate($end_date)
    ) {
        $errors[] = "Tanggal selesai tidak valid.";
    }

    if (
        $end_date !== '' &&
        validDate($start_date) &&
        validDate($end_date) &&
        $end_date < $start_date
    ) {
        $errors[] = "Tanggal selesai tidak boleh sebelum tanggal mulai.";
    }

    if (empty($errors)) {

        $insert = $pdo->prepare("
            INSERT INTO medication_schedules
            (
                user_id,
                medicine_id,
                schedule_time,
                start_date,
                end_date,
                instructions,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");

        $insert->execute([
            $user_id,
            $medicine_id,
            $schedule_time,
            $start_date,
            $end_date !== '' ? $end_date : null,
            $instructions
        ]);

        header("Location: schedules.php?success=added");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tambah Jadwal - MediTrack</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="mb-4">
        <h2>Tambah Jadwal Obat</h2>
        <p class="text-muted">
            Atur waktu dan periode konsumsi obat.
        </p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (empty($medicines)): ?>

                <div class="alert alert-warning">
                    Kamu belum memiliki data obat.
                    Tambahkan data obat terlebih dahulu.
                </div>

                <a href="add_medicine.php" class="btn btn-primary">
                    Tambah Data Obat
                </a>

                <a href="schedules.php" class="btn btn-secondary">
                    Kembali
                </a>

            <?php else: ?>

                <form method="POST">

                    <div class="mb-3">
                        <label class="form-label">Pilih Obat</label>

                        <select name="medicine_id" class="form-select" required>
                            <option value="">-- Pilih Obat --</option>

                            <?php foreach ($medicines as $medicine): ?>
                                <option
                                    value="<?= $medicine['id'] ?>"
                                    <?= (($_POST['medicine_id'] ?? '') == $medicine['id']) ? 'selected' : '' ?>
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

                    <div class="mb-3">
                        <label class="form-label">Waktu Konsumsi</label>

                        <input
                            type="time"
                            name="schedule_time"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['schedule_time'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Mulai</label>

                            <input
                                type="date"
                                name="start_date"
                                class="form-control"
                                value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>"
                                required
                            >
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Selesai</label>

                            <input
                                type="date"
                                name="end_date"
                                class="form-control"
                                value="<?= htmlspecialchars($_POST['end_date'] ?? '') ?>"
                            >

                            <small class="text-muted">
                                Kosongkan jika belum ditentukan.
                            </small>
                        </div>

                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Instruksi Tambahan
                        </label>

                        <textarea
                            name="instructions"
                            class="form-control"
                            rows="3"
                            placeholder="Contoh: Setelah makan."
                        ><?= htmlspecialchars($_POST['instructions'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex gap-2">

                        <button type="submit" class="btn btn-primary">
                            Simpan Jadwal
                        </button>

                        <a href="schedules.php" class="btn btn-secondary">
                            Batal
                        </a>

                    </div>

                </form>

            <?php endif; ?>

        </div>
    </div>

</div>

</body>
</html>
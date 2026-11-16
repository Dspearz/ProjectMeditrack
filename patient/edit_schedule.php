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
");
$stmt->execute([$user_id]);

$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

function validScheduleDate($date) {
    $d = DateTime::createFromFormat('!Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

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

    // Pastikan obat milik pasien
    $check = $pdo->prepare("
        SELECT id FROM medicines
        WHERE id = ? AND user_id = ?
    ");
    $check->execute([$medicine_id, $user_id]);

    if (!$medicine_id || !$check->fetch()) {
        $errors[] = "Pilihan obat tidak valid.";
    }

    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $schedule_time)) {
        $errors[] = "Waktu konsumsi tidak valid.";
    }

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

    if (!in_array($status, ['active', 'inactive'], true)) {
        $errors[] = "Status jadwal tidak valid.";
    }

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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Jadwal - MediTrack</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <h2>Edit Jadwal Obat</h2>
    <p class="text-muted">Perbarui informasi jadwal konsumsi obat.</p>

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

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Pilih Obat</label>

                    <select name="medicine_id" class="form-select" required>
                        <?php foreach ($medicines as $medicine): ?>
                            <option
                                value="<?= $medicine['id'] ?>"
                                <?= (($_POST['medicine_id'] ?? $schedule['medicine_id']) == $medicine['id']) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($medicine['medicine_name'] . ' - ' . $medicine['dosage']) ?>
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
                        value="<?= htmlspecialchars($_POST['schedule_time'] ?? substr($schedule['schedule_time'], 0, 5)) ?>"
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
                            value="<?= htmlspecialchars($_POST['start_date'] ?? $schedule['start_date']) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Selesai</label>
                        <input
                            type="date"
                            name="end_date"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['end_date'] ?? $schedule['end_date'] ?? '') ?>"
                        >
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Instruksi Tambahan</label>
                    <textarea
                        name="instructions"
                        class="form-control"
                        rows="3"
                    ><?= htmlspecialchars($_POST['instructions'] ?? $schedule['instructions'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status Jadwal</label>
                    <select name="status" class="form-select" required>
                        <option value="active"
                            <?= (($_POST['status'] ?? $schedule['status']) === 'active') ? 'selected' : '' ?>>
                            Aktif
                        </option>

                        <option value="inactive"
                            <?= (($_POST['status'] ?? $schedule['status']) === 'inactive') ? 'selected' : '' ?>>
                            Tidak Aktif
                        </option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    Simpan Perubahan
                </button>

                <a href="schedules.php" class="btn btn-secondary">
                    Batal
                </a>

            </form>

        </div>
    </div>

</div>

</body>
</html>
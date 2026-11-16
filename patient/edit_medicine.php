<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('patient');

$user_id = $_SESSION['user_id'];
$errors = [];

// Ambil ID obat dari URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: medicines.php");
    exit;
}

// Ambil data obat milik pasien yang sedang login
$stmt = $pdo->prepare("
    SELECT * FROM medicines
    WHERE id = ? AND user_id = ?
");
$stmt->execute([$id, $user_id]);

$medicine = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$medicine) {
    header("Location: medicines.php");
    exit;
}

// Proses pembaruan data
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $medicine_name = trim($_POST['medicine_name'] ?? '');
    $dosage = trim($_POST['dosage'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    if ($medicine_name === '') {
        $errors[] = "Nama obat wajib diisi.";
    }

    if ($dosage === '') {
        $errors[] = "Dosis obat wajib diisi.";
    }

    if ($start_date === '') {
        $errors[] = "Tanggal mulai wajib diisi.";
    }

    if (
        $end_date !== '' &&
        $start_date !== '' &&
        $end_date < $start_date
    ) {
        $errors[] = "Tanggal selesai tidak boleh sebelum tanggal mulai.";
    }

    if (empty($errors)) {
        $update = $pdo->prepare("
            UPDATE medicines
            SET medicine_name = ?,
                dosage = ?,
                description = ?,
                start_date = ?,
                end_date = ?
            WHERE id = ? AND user_id = ?
        ");

        $update->execute([
            $medicine_name,
            $dosage,
            $description,
            $start_date,
            $end_date !== '' ? $end_date : null,
            $id,
            $user_id
        ]);

        header("Location: medicines.php?success=updated");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Obat - MediTrack</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="mb-4">
        <h2>Edit Data Obat</h2>
        <p class="text-muted">
            Perbarui informasi obat yang telah tersimpan.
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

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Nama Obat</label>
                    <input
                        type="text"
                        name="medicine_name"
                        class="form-control"
                        value="<?= htmlspecialchars($_POST['medicine_name'] ?? $medicine['medicine_name']) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Dosis Obat</label>
                    <input
                        type="text"
                        name="dosage"
                        class="form-control"
                        value="<?= htmlspecialchars($_POST['dosage'] ?? $medicine['dosage']) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Deskripsi / Aturan Penggunaan</label>
                    <textarea
                        name="description"
                        class="form-control"
                        rows="3"
                    ><?= htmlspecialchars($_POST['description'] ?? $medicine['description']) ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Mulai</label>
                        <input
                            type="date"
                            name="start_date"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['start_date'] ?? $medicine['start_date']) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Selesai</label>
                        <input
                            type="date"
                            name="end_date"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['end_date'] ?? $medicine['end_date'] ?? '') ?>"
                        >
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Simpan Perubahan
                    </button>

                    <a href="medicines.php" class="btn btn-secondary">
                        Batal
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>

</body>
</html>
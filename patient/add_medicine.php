<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('patient');

$user_id = $_SESSION['user_id'];
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $medicine_name = trim($_POST['medicine_name'] ?? '');
    $dosage = trim($_POST['dosage'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    // Validasi input
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

    // Simpan data ke database
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO medicines
            (user_id, medicine_name, dosage, description, start_date, end_date)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $user_id,
            $medicine_name,
            $dosage,
            $description,
            $start_date,
            $end_date !== '' ? $end_date : null
        ]);

        header("Location: medicines.php?success=added");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Obat - MediTrack</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="mb-4">
        <h2>Tambah Data Obat</h2>
        <p class="text-muted">
            Masukkan informasi obat yang sedang kamu konsumsi.
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
                        placeholder="Contoh: Paracetamol"
                        value="<?= htmlspecialchars($_POST['medicine_name'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Dosis Obat</label>
                    <input
                        type="text"
                        name="dosage"
                        class="form-control"
                        placeholder="Contoh: 500 mg"
                        value="<?= htmlspecialchars($_POST['dosage'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Deskripsi / Aturan Penggunaan</label>
                    <textarea
                        name="description"
                        class="form-control"
                        rows="3"
                        placeholder="Contoh: Dikonsumsi setelah makan sesuai resep."
                    ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
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

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Simpan Obat
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
<?php
require_once "../includes/auth_check.php";
require_once "../includes/csrf.php";
require_once "../config/database.php";

requireRole('patient');

$user_id = $_SESSION['user_id'];
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    validateCSRFToken();

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

$pageTitle = "Tambah Obat";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<main class="main-content">

    <div class="page-header">
        <div>
            <h1>Tambah Data Obat</h1>
            <p class="text-muted">
                Tambahkan informasi obat yang sedang kamu konsumsi.
            </p>
        </div>

        <a href="medicines.php" class="btn btn-outline-secondary">
            Kembali
        </a>
    </div>

    <div class="card">
        <div class="card-body p-4">

            <div class="mb-4">
                <h5>Informasi Obat</h5>
                <p class="text-muted mb-0">
                    Lengkapi informasi berikut untuk mencatat obat baru.
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

            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(generateCSRFToken()) ?>"
                >

                <div class="form-group mb-3">
                    <label for="medicine_name" class="form-label">
                        Nama Obat <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        id="medicine_name"
                        name="medicine_name"
                        class="form-control"
                        placeholder="Contoh: Paracetamol"
                        value="<?= htmlspecialchars($_POST['medicine_name'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="form-group mb-3">
                    <label for="dosage" class="form-label">
                        Dosis Obat <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        id="dosage"
                        name="dosage"
                        class="form-control"
                        placeholder="Contoh: 500 mg"
                        value="<?= htmlspecialchars($_POST['dosage'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="form-group mb-3">
                    <label for="description" class="form-label">
                        Deskripsi / Aturan Penggunaan
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        class="form-control"
                        rows="4"
                        placeholder="Contoh: Dikonsumsi setelah makan sesuai resep."
                    ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="start_date" class="form-label">
                                Tanggal Mulai <span class="text-danger">*</span>
                            </label>

                            <input
                                type="date"
                                id="start_date"
                                name="start_date"
                                class="form-control"
                                value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>"
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
                                id="end_date"
                                name="end_date"
                                class="form-control"
                                value="<?= htmlspecialchars($_POST['end_date'] ?? '') ?>"
                            >

                            <small class="text-muted">
                                Kosongkan jika tanggal selesai belum ditentukan.
                            </small>
                        </div>
                    </div>

                </div>

                <hr class="my-4">

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i>
                        Simpan Obat
                    </button>

                    <a href="medicines.php" class="btn btn-outline-secondary">
                        Batal
                    </a>
                </div>

            </form>

        </div>
    </div>

</main>

<?php require_once "../includes/footer.php"; ?>
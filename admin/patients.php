<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('admin');

$pageTitle = "Data Pasien";
$currentPage = "patients";

$search = trim($_GET['search'] ?? '');

// Statistik seluruh pasien
$statsQuery = $pdo->query("
    SELECT
        COUNT(DISTINCT u.id) AS total_patients,
        COUNT(DISTINCT m.id) AS total_medicines
    FROM users u
    LEFT JOIN medicines m ON u.id = m.user_id
    WHERE u.role = 'patient'
");

$stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

// Query daftar pasien
$sql = "
    SELECT
        u.id,
        u.name,
        u.email,
        u.created_at,
        COUNT(DISTINCT m.id) AS total_medicines
    FROM users u
    LEFT JOIN medicines m ON u.id = m.user_id
    WHERE u.role = 'patient'
";

$params = [];

if ($search !== '') {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= "
    GROUP BY u.id, u.name, u.email, u.created_at
    ORDER BY u.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<div class="main-content">

    <!-- Header -->
    <div class="page-header">
        <div>
            <h1>Data Pasien</h1>
            <p class="text-muted">
                Kelola dan pantau data akun pasien MediTrack.
            </p>
        </div>
    </div>

    <!-- Statistik -->
    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-info">
                <span>Total Pasien</span>
                <h3><?= (int) $stats['total_patients'] ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span>Total Obat Terdaftar</span>
                <h3><?= (int) $stats['total_medicines'] ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span>Hasil Pencarian</span>
                <h3><?= count($patients) ?></h3>
            </div>
        </div>

    </div>

    <!-- Pencarian -->
    <div class="card mb-4">

        <div class="mb-3">
            <h4>Pencarian Pasien</h4>
            <p class="text-muted mb-0">
                Cari pasien berdasarkan nama atau alamat email.
            </p>
        </div>

        <form method="GET">

            <div class="row">

                <div class="col-md-9 mb-2">
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Masukkan nama atau email pasien..."
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>

                <div class="col-md-3 mb-2 d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Cari Pasien
                    </button>

                    <a
                        href="patients.php"
                        class="btn btn-secondary"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </form>

    </div>

    <!-- Daftar Pasien -->
    <div class="card">

        <div class="mb-4">
            <h4>Daftar Pasien</h4>
            <p class="text-muted mb-0">
                Menampilkan <?= count($patients) ?> pasien.
            </p>
        </div>

        <?php if (empty($patients)): ?>

            <div class="text-center py-5">
                <h5>Data Pasien Tidak Ditemukan</h5>

                <p class="text-muted">
                    Tidak ada pasien yang sesuai dengan pencarian.
                </p>

                <a
                    href="patients.php"
                    class="btn btn-primary"
                >
                    Tampilkan Semua Pasien
                </a>
            </div>

        <?php else: ?>

            <div class="table-container">

                <table>

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Pasien</th>
                            <th>Email</th>
                            <th>Total Obat</th>
                            <th>Tanggal Terdaftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($patients as $index => $patient): ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($patient['name']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($patient['email']) ?>
                                </td>

                                <td>
                                    <span class="status status-success">
                                        <?= (int) $patient['total_medicines'] ?> Obat
                                    </span>
                                </td>

                                <td>
                                    <?= date(
                                        'd-m-Y',
                                        strtotime($patient['created_at'])
                                    ) ?>
                                </td>

                                <td>
                                    <a
                                        href="patient_detail.php?id=<?= (int) $patient['id'] ?>"
                                        class="btn btn-primary btn-sm"
                                    >
                                        Lihat Detail
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>

<?php require_once "../includes/footer.php"; ?>
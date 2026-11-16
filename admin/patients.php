<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('admin');

$search = trim($_GET['search'] ?? '');

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
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Data Pasien - MediTrack</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2>Data Pasien</h2>
            <p class="text-muted">
                Kelola dan pantau akun pasien MediTrack.
            </p>
        </div>

        <a href="dashboard.php" class="btn btn-outline-secondary">
            Kembali
        </a>

    </div>

    <!-- Pencarian -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <form method="GET" class="row g-2">

                <div class="col-md-10">
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Cari nama atau email pasien..."
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>

                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">
                        Cari Pasien
                    </button>
                </div>

            </form>

        </div>
    </div>

    <!-- Daftar Pasien -->
    <div class="card shadow-sm">

        <div class="card-body">

            <h5 class="mb-3">
                Daftar Pasien (<?= count($patients) ?>)
            </h5>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light">
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

                    <?php if (count($patients) > 0): ?>

                        <?php foreach ($patients as $index => $patient): ?>

                            <tr>

                                <td><?= $index + 1 ?></td>

                                <td>
                                    <?= htmlspecialchars($patient['name']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($patient['email']) ?>
                                </td>

                                <td>
                                    <span class="badge bg-primary">
                                        <?= $patient['total_medicines'] ?> Obat
                                    </span>
                                </td>

                                <td>
                    
                                    <?= date(
                                        'd-m-Y',
                                        strtotime($patient['created_at'])
                                    ) ?>
                                </td>
                                
                                 <td>
    <a href="patient_detail.php?id=<?= $patient['id'] ?>"
       class="btn btn-sm btn-info">
        Detail
    </a>
</td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Data pasien tidak ditemukan.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</body>
</html>
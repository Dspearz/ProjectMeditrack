<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';

$menuItems = $isAdmin
    ? [
        ['dashboard.php', 'Dashboard'],
        ['patients.php', 'Data Pasien'],
        ['activities.php', 'Aktivitas Pasien']
    ]
    : [
        ['dashboard.php', 'Dashboard'],
        ['medicines.php', 'Daftar Obat'],
        ['schedules.php', 'Jadwal Obat'],
        ['medication_tracking.php', 'Tracking Obat'],
        ['medication_history.php', 'Riwayat Pengobatan']
    ];

$basePath = $isAdmin ? '/MediTrack/admin/' : '/MediTrack/patient/';
?>

<aside class="sidebar">
    <div class="brand">
        Medi<span>Track</span>
    </div>

    <div class="mb-4 px-2">
        <div class="fw-semibold">
            <?= htmlspecialchars($_SESSION['name'] ?? 'Pengguna') ?>
        </div>
        <small class="text-secondary">
            <?= $isAdmin ? 'Administrator' : 'Pasien' ?>
        </small>
    </div>

    <nav class="sidebar-menu">
        <?php foreach ($menuItems as $item): ?>
            <a
                href="<?= $basePath . $item[0] ?>"
                class="<?= $currentPage === $item[0] ? 'active' : '' ?>"
            >
                <?= $item[1] ?>
            </a>
        <?php endforeach; ?>

        <a href="/MediTrack/auth/logout.php" class="text-danger">
            Keluar
        </a>
    </nav>
</aside>
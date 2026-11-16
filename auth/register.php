<?php
require_once "../config/database.php";

session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$error = "";

$name = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (
        $name === "" ||
        $email === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {
        $error = "Semua kolom wajib diisi.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";

    } elseif (strlen($password) < 8) {
        $error = "Password minimal 8 karakter.";

    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok.";

    } else {

        $check = $pdo->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $check->execute([$email]);

        if ($check->fetch()) {

            $error = "Email sudah terdaftar.";

        } else {

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password, role)
                 VALUES (?, ?, ?, 'patient')"
            );

            $stmt->execute([
                $name,
                $email,
                $hashed_password
            ]);

            header("Location: login.php?registered=1");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registrasi - MediTrack</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: #f1f5f9;
            font-family: Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px;
        }

        .register-wrapper {
            width: 100%;
            max-width: 1050px;
            min-height: 650px;
            display: grid;
            grid-template-columns: 0.9fr 1.1fr;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 45px rgba(15, 23, 42, 0.10);
        }

        .register-brand {
            background: linear-gradient(145deg, #1e40af, #2563eb, #60a5fa);
            color: white;
            padding: 55px 45px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .brand-logo {
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .brand-content {
            margin: 50px 0;
        }

        .brand-content h1 {
            font-size: 35px;
            font-weight: 800;
            line-height: 1.3;
            margin-bottom: 20px;
        }

        .brand-content p {
            color: #dbeafe;
            font-size: 15px;
            line-height: 1.8;
        }

        .brand-feature {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 20px;
            color: #eff6ff;
            font-size: 14px;
        }

        .feature-icon {
            width: 35px;
            height: 35px;
            background: rgba(255,255,255,0.18);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-footer {
            font-size: 12px;
            color: #dbeafe;
        }

        .register-form-section {
            padding: 45px 55px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-heading h2 {
            font-size: 29px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .form-heading p {
            color: #64748b;
            margin-bottom: 25px;
        }

        .form-label {
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }

        .form-control {
            padding: 12px 15px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-control {
            padding-right: 90px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: #2563eb;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .password-info {
            font-size: 12px;
            color: #64748b;
            margin-top: 6px;
        }

        .password-match {
            font-size: 12px;
            margin-top: 6px;
        }

        .btn-register {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 10px;
            background: #2563eb;
            color: white;
            font-weight: 700;
            margin-top: 12px;
            transition: 0.2s;
        }

        .btn-register:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .login-link {
            text-align: center;
            margin-top: 22px;
            color: #64748b;
            font-size: 14px;
        }

        .login-link a {
            color: #2563eb;
            font-weight: 700;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 10px;
            font-size: 14px;
        }

        @media (max-width: 768px) {

            body {
                padding: 15px;
            }

            .register-wrapper {
                grid-template-columns: 1fr;
            }

            .register-brand {
                padding: 30px;
            }

            .brand-content {
                margin: 30px 0;
            }

            .brand-content h1 {
                font-size: 27px;
            }

            .register-form-section {
                padding: 35px 25px;
            }
        }
    </style>

</head>

<body>

<div class="register-wrapper">

    <!-- Branding -->
    <section class="register-brand">

        <div class="brand-logo">
            MediTrack<span style="color:#bfdbfe;">.</span>
        </div>

        <div class="brand-content">

            <h1>
                Mulai Perjalanan Pengobatan yang Lebih Teratur.
            </h1>

            <p>
                Buat akun MediTrack dan mulai kelola informasi obat,
                jadwal konsumsi, serta riwayat pengobatanmu
                dengan lebih mudah.
            </p>

            <div class="brand-feature">
                <div class="feature-icon">✓</div>
                <span>Registrasi akun dengan mudah</span>
            </div>

            <div class="brand-feature">
                <div class="feature-icon">◷</div>
                <span>Kelola jadwal konsumsi obat</span>
            </div>

            <div class="brand-feature">
                <div class="feature-icon">▤</div>
                <span>Pantau riwayat pengobatan</span>
            </div>

        </div>

        <div class="brand-footer">
            © <?= date('Y') ?> MediTrack. All rights reserved.
        </div>

    </section>

    <!-- Form Registrasi -->
    <section class="register-form-section">

        <div class="form-heading">

            <h2>Buat Akun Baru</h2>

            <p>
                Lengkapi informasi berikut untuk mendaftar.
            </p>

        </div>

        <?php if ($error): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">

                <label class="form-label">
                    Nama Lengkap
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    placeholder="Masukkan nama lengkap"
                    value="<?= htmlspecialchars($name) ?>"
                    autocomplete="name"
                    required
                >

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Alamat Email
                </label>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="nama@email.com"
                    value="<?= htmlspecialchars($email) ?>"
                    autocomplete="email"
                    required
                >

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Password
                </label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        placeholder="Minimal 8 karakter"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword('password', this)"
                    >
                        Lihat
                    </button>

                </div>

                <div class="password-info">
                    Password harus terdiri dari minimal 8 karakter.
                </div>

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Konfirmasi Password
                </label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        name="confirm_password"
                        id="confirm_password"
                        class="form-control"
                        placeholder="Masukkan ulang password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword('confirm_password', this)"
                    >
                        Lihat
                    </button>

                </div>

                <div id="passwordMatch" class="password-match"></div>

            </div>

            <button type="submit" class="btn-register">
                Daftar Sekarang
            </button>

        </form>

        <div class="login-link">

            Sudah punya akun?

            <a href="login.php">
                Login di sini
            </a>

        </div>

    </section>

</div>

<script>

    function togglePassword(id, button) {

        const password = document.getElementById(id);

        if (password.type === 'password') {
            password.type = 'text';
            button.textContent = 'Sembunyikan';
        } else {
            password.type = 'password';
            button.textContent = 'Lihat';
        }

    }

    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const passwordMatch = document.getElementById('passwordMatch');

    function checkPasswordMatch() {

        if (confirmInput.value === '') {
            passwordMatch.textContent = '';
            return;
        }

        if (passwordInput.value === confirmInput.value) {
            passwordMatch.textContent = 'Password cocok.';
            passwordMatch.style.color = '#16a34a';
        } else {
            passwordMatch.textContent = 'Password belum cocok.';
            passwordMatch.style.color = '#dc2626';
        }

    }

    passwordInput.addEventListener('input', checkPasswordMatch);
    confirmInput.addEventListener('input', checkPasswordMatch);

</script>

</body>
</html>
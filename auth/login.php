<?php
require_once "../config/database.php";

// Konfigurasi session
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$error = "";
$success = "";

if (isset($_GET['registered'])) {
    $success = "Registrasi berhasil. Silakan login.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        "SELECT * FROM users WHERE email = ?"
    );

    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        header("Location: ../index.php");
        exit;

    } else {
        $error = "Email atau password salah.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - MediTrack</title>

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

        .login-wrapper {
            width: 100%;
            max-width: 1000px;
            min-height: 580px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 45px rgba(15, 23, 42, 0.10);
        }

        .login-brand {
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
            font-size: 36px;
            font-weight: 800;
            line-height: 1.25;
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
            font-size: 18px;
        }

        .brand-footer {
            font-size: 12px;
            color: #dbeafe;
        }

        .login-form-section {
            padding: 55px 50px;
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
            margin-bottom: 30px;
        }

        .form-label {
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }

        .form-control {
            padding: 13px 15px;
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
            padding-right: 75px;
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

        .btn-login {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 10px;
            background: #2563eb;
            color: white;
            font-weight: 700;
            margin-top: 10px;
            transition: 0.2s;
        }

        .btn-login:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .register-link {
            text-align: center;
            margin-top: 25px;
            color: #64748b;
            font-size: 14px;
        }

        .register-link a {
            color: #2563eb;
            font-weight: 700;
            text-decoration: none;
        }

        .register-link a:hover {
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

            .login-wrapper {
                grid-template-columns: 1fr;
            }

            .login-brand {
                padding: 30px;
            }

            .brand-content {
                margin: 30px 0;
            }

            .brand-content h1 {
                font-size: 27px;
            }

            .login-form-section {
                padding: 35px 25px;
            }
        }
    </style>

</head>

<body>

<div class="login-wrapper">

    <!-- Branding -->
    <section class="login-brand">

        <div class="brand-logo">
            MediTrack<span style="color:#bfdbfe;">.</span>
        </div>

        <div class="brand-content">

            <h1>
                Kelola Pengobatanmu dengan Lebih Teratur.
            </h1>

            <p>
                MediTrack membantu pasien mengelola informasi obat,
                jadwal konsumsi, dan riwayat pengobatan dalam satu
                platform yang mudah digunakan.
            </p>

            <div class="brand-feature">
                <div class="feature-icon">✓</div>
                <span>Manajemen data obat</span>
            </div>

            <div class="brand-feature">
                <div class="feature-icon">◷</div>
                <span>Pemantauan jadwal konsumsi</span>
            </div>

            <div class="brand-feature">
                <div class="feature-icon">▤</div>
                <span>Riwayat aktivitas pengobatan</span>
            </div>

        </div>

        <div class="brand-footer">
            © <?= date('Y') ?> MediTrack. All rights reserved.
        </div>

    </section>

    <!-- Form Login -->
    <section class="login-form-section">

        <div class="form-heading">

            <h2>Selamat Datang!</h2>

            <p>
                Silakan login untuk melanjutkan ke MediTrack.
            </p>

        </div>

        <?php if ($success): ?>

            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>

        <?php endif; ?>

        <?php if ($error): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="mb-4">

                <label class="form-label">
                    Alamat Email
                </label>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="nama@email.com"
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
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword()"
                    >
                        Lihat
                    </button>

                </div>

            </div>

            <button type="submit" class="btn-login">
                Login ke MediTrack
            </button>

        </form>

        <div class="register-link">

            Belum punya akun?

            <a href="register.php">
                Daftar sekarang
            </a>

        </div>

    </section>

</div>

<script>
    function togglePassword() {
        const password = document.getElementById('password');
        const button = document.querySelector('.toggle-password');

        if (password.type === 'password') {
            password.type = 'text';
            button.textContent = 'Sembunyikan';
        } else {
            password.type = 'password';
            button.textContent = 'Lihat';
        }
    }
</script>

</body>
</html>
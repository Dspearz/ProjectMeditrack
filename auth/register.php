<?php
require_once "../config/database.php";

session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$error = "";

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
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Register - MediTrack</title>
</head>
<body>

    <h2>Daftar Akun MediTrack</h2>

    <?php if ($error): ?>
        <p style="color:red">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <label>Nama Lengkap</label><br>
        <input type="text" name="name"
               value="<?= htmlspecialchars($name ?? '') ?>"
               required>
        <br><br>

        <label>Email</label><br>
        <input type="email" name="email"
               value="<?= htmlspecialchars($email ?? '') ?>"
               required>
        <br><br>

        <label>Password</label><br>
        <input type="password" name="password"
               minlength="8" required>
        <br><br>

        <label>Konfirmasi Password</label><br>
        <input type="password" name="confirm_password"
               minlength="8" required>
        <br><br>

        <button type="submit">Daftar</button>

    </form>

    <p>Sudah punya akun?
        <a href="login.php">Login di sini</a>
    </p>

</body>
</html>
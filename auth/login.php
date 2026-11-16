<?php
require_once "../config/database.php";

session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$error = "";

if (isset($_GET['registered'])) {
    $success = "Registrasi berhasil. Silakan login.";
} else {
    $success = "";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        "SELECT * FROM users WHERE email = ?"
    );

    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify(
        $password,
        $user['password']
    )) {

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
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Login - MediTrack</title>
</head>
<body>

    <h2>Login MediTrack</h2>

    <?php if ($success): ?>
        <p style="color:green">
            <?= htmlspecialchars($success) ?>
        </p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color:red">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <label>Email</label><br>
        <input type="email" name="email" required>
        <br><br>

        <label>Password</label><br>
        <input type="password" name="password" required>
        <br><br>

        <button type="submit">Login</button>

    </form>

    <p>Belum punya akun?
        <a href="register.php">Daftar sekarang</a>
    </p>

</body>
</html>

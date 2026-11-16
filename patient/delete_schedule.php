<?php
require_once "../config/database.php";
require_once "../includes/auth_check.php";

requireRole('patient');

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: schedules.php");
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: schedules.php");
    exit;
}

$stmt = $pdo->prepare("
    DELETE FROM medication_schedules
    WHERE id = ? AND user_id = ?
");

$stmt->execute([$id, $user_id]);

if ($stmt->rowCount() > 0) {
    header("Location: schedules.php?success=deleted");
} else {
    header("Location: schedules.php?error=notfound");
}

exit;
?>
<?php
session_start();
require_once '../inc/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nrp = $_POST['nrp'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM personel WHERE nrp = ?");
    $stmt->execute([$nrp]);
    $personel = $stmt->fetch();

    if ($personel && password_verify($password, $personel['password'])) {
        $_SESSION['personel_id'] = $personel['id'];
        $_SESSION['nama'] = $personel['nama'];
        header("Location: absensi_selector.php");
        exit;
    } else {
        header("Location: login_personel.php?error=NRP atau password salah");
        exit;
    }
} else {
    header("Location: login_personel.php");
    exit;
}

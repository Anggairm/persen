<?php
session_start();
require_once 'inc/db.php'; // Pastikan file db.php ada dan koneksi $pdo aktif

// Tangkap data dari form login
$nrp = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Cek admin berdasarkan NRP
$stmt = $pdo->prepare("SELECT * FROM personel WHERE nrp = ? AND role = 'admin'");
$stmt->execute([$nrp]);
$admin = $stmt->fetch();

if ($admin && password_verify($password, $admin['password'])) {
    // Simpan sesi admin
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_nama'] = $admin['nama'];
    $_SESSION['admin_nrp']  = $admin['nrp'];

    // Redirect ke dashboard
    header("Location: admin/dashboard.php");
    exit;
} else {
    // Gagal login
    echo "<script>alert('NRP atau Password salah'); window.location.href='login_admin.php';</script>";
    exit;
}

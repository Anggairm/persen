<?php
session_start();
header('Content-Type: application/json');
require_once '../inc/db.php';

date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['personel_id'])) {
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['qr'])) {
    echo json_encode(['success' => false, 'message' => 'Data QR tidak ditemukan']);
    exit;
}

$qr = trim($input['qr']);
$tanggal = date('Y-m-d');
$keluar = date('H:i:s');
$personel_id = $_SESSION['personel_id'];

// Cek apakah QR sesuai
if ($qr !== $tanggal) {
    echo json_encode(['success' => false, 'message' => 'QR tidak valid']);
    exit;
}

// Cek apakah sudah absen pulang hari ini
$stmt = $pdo->prepare("SELECT id FROM absensi 
                       WHERE personel_id = ? AND tanggal = ? AND keluar <> '00:00:00'");
$stmt->execute([$personel_id, $tanggal]);
$sudah_absen_pulang = $stmt->fetchColumn();

if ($sudah_absen_pulang) {
    echo json_encode(['success' => false, 'message' => 'Anda sudah absen pulang hari ini']);
    exit;
}

// Update jam keluar di absensi yang sudah ada
$stmt = $pdo->prepare("UPDATE absensi SET keluar = ? WHERE personel_id = ? AND tanggal = ? AND keluar = '00:00:00'");
$update_success = $stmt->execute([$keluar, $personel_id, $tanggal]);

if ($update_success && $stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'Absen Keluar berhasil']);
} else {
    echo json_encode(['success' => false, 'message' => 'Data absensi masuk belum ada']);
}
exit;

<?php
session_start();
header('Content-Type: application/json');
require_once '../inc/db.php';

// Cek apakah user sudah login dan role-nya personel
if (!isset($_SESSION['personel_id'])) {
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit;
}

// Ambil input JSON dari fetch
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['qr'])) {
    echo json_encode(['success' => false, 'message' => 'Data QR tidak ditemukan']);
    exit;
}

$qr = trim($input['qr']);
$tanggal = date('Y-m-d');
$jam = date('H:i:s');
$personel_id = $_SESSION['personel_id'];

// Cek apakah QR sesuai (misalnya berisi tanggal hari ini)
if ($qr !== $tanggal) {
    echo json_encode(['success' => false, 'message' => 'QR tidak valid']);
    exit;
}

// Cek apakah sudah absen sebelumnya hari ini
$stmt = $pdo->prepare("SELECT COUNT(*) FROM absensi WHERE personel_id = ? AND tanggal = ?");
$stmt->execute([$personel_id, $tanggal]);
$sudah_absen = $stmt->fetchColumn();

if ($sudah_absen) {
    echo json_encode(['success' => false, 'message' => 'Anda sudah absen hari ini']);
    exit;
}

// Simpan data absensi
$stmt = $pdo->prepare("INSERT INTO absensi (personel_id, tanggal, jam, status) VALUES (?, ?, ?, 'HADIR')");
$stmt->execute([$personel_id, $tanggal, $jam]);

echo json_encode(['success' => true, 'message' => 'Absen berhasil']);
exit;

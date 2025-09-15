<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
header('Content-Type: application/json; charset=utf-8');

require_once '../inc/db.php';

// ====================
// Validasi sesi login
// ====================
if (!isset($_SESSION['personel_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit;
}

// ====================
// Ambil & validasi input
// ====================
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['qr'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Data QR tidak ditemukan']);
    exit;
}

$qr = trim($input['qr']);
$tanggal = date('Y-m-d');
$jam_keluar = date('H:i:s');
$personel_id = $_SESSION['personel_id'];

// Pastikan QR sesuai tanggal hari ini
if ($qr !== $tanggal) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'QR tidak valid']);
    exit;
}

try {
    // ====================
    // Cek apakah sudah absen masuk hari ini
    // ====================
    $stmt = $pdo->prepare("SELECT id, keluar FROM absensi WHERE personel_id = ? AND tanggal = ?");
    $stmt->execute([$personel_id, $tanggal]);
    $absen = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$absen) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Data absensi masuk belum ada']);
        exit;
    }

    // ====================
    // Cek apakah sudah pernah absen keluar
    // ====================
    if (!empty($absen['keluar'])) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Anda sudah absen pulang hari ini']);
        exit;
    }

    // ====================
    // Update jam keluar
    // ====================
    $stmt = $pdo->prepare("UPDATE absensi 
                           SET keluar = ? 
                           WHERE id = ?");
    $stmt->execute([$jam_keluar, $absen['id']]);

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Absen keluar berhasil']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat menyimpan absen keluar',
        'debug' => $e->getMessage()
    ]);
}
exit;

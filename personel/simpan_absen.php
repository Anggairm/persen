<?php
session_start();
header('Content-Type: application/json');
require_once '../inc/db.php';

date_default_timezone_set('Asia/Jakarta');
$debugMode = true;

// Pastikan login
if (!isset($_SESSION['personel_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit;
}

// Ambil input JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['qr'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Data QR tidak ditemukan']);
    exit;
}

$qr = trim($input['qr']);
$tanggal = date('Y-m-d');
$jam = date('H:i:s');
$personel_id = $_SESSION['personel_id'];

// Validasi format QR
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $qr)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Format QR tidak valid']);
    exit;
}

// Validasi nilai QR
if ($qr !== $tanggal) {
    $response = [
        'success' => false,
        'message' => 'QR tidak valid'
    ];
    if ($debugMode) {
        $response['debug'] = [
            'qr' => $qr,
            'tanggal_server' => $tanggal,
            'timezone' => date_default_timezone_get(),
            'server_time' => date('Y-m-d H:i:s')
        ];
    }
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Cek apakah sudah absen hari ini
$stmt = $pdo->prepare("SELECT COUNT(*) FROM absensi WHERE personel_id = ? AND tanggal = ?");
$stmt->execute([$personel_id, $tanggal]);
if ($stmt->fetchColumn()) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Anda sudah absen hari ini']);
    exit;
}

// Simpan data absensi
$stmt = $pdo->prepare("INSERT INTO absensi (personel_id, tanggal, jam, status) VALUES (?, ?, ?, 'HADIR')");
$stmt->execute([$personel_id, $tanggal, $jam]);

http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Absen berhasil']);
exit;

<?php
session_start();
require_once '../inc/db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get JSON input
$json_input = file_get_contents('php://input');
$input_data = json_decode($json_input, true);

if (!$input_data || !isset($input_data['personel_id']) || !isset($input_data['tanggal'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

$personel_id = intval($input_data['personel_id']);
$tanggal = $input_data['tanggal'];

try {
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        throw new Exception('Invalid date format');
    }
    
    // Check if record exists
    $check_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM absensi 
        WHERE personel_id = ? AND tanggal = ?
    ");
    $check_stmt->execute([$personel_id, $tanggal]);
    
    if ($check_stmt->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'message' => 'Data absensi tidak ditemukan']);
        exit;
    }
    
    // Get personel info for logging
    $personel_stmt = $pdo->prepare("SELECT nama FROM personel WHERE id = ?");
    $personel_stmt->execute([$personel_id]);
    $personel_nama = $personel_stmt->fetchColumn();
    
    // Delete the attendance record
    $delete_stmt = $pdo->prepare("
        DELETE FROM absensi 
        WHERE personel_id = ? AND tanggal = ?
    ");
    
    if ($delete_stmt->execute([$personel_id, $tanggal])) {
        echo json_encode([
            'success' => true, 
            'message' => 'Data absensi berhasil dihapus',
            'personel_nama' => $personel_nama
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus data absensi']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
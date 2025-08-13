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

if (!$input_data || !isset($input_data['data']) || !isset($input_data['tanggal'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

$data_array = $input_data['data'];
$tanggal = $input_data['tanggal'];

if (empty($data_array)) {
    echo json_encode(['success' => false, 'message' => 'No data to save']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $saved_count = 0;
    $updated_count = 0;
    
    foreach ($data_array as $item) {
        if (!isset($item['id']) || !isset($item['kategori']) || !isset($item['keterangan'])) {
            continue;
        }
        
        $personel_id = intval($item['id']);
        $kategori = trim($item['kategori']);
        $keterangan = trim($item['keterangan']);
        $is_existing = isset($item['isExisting']) ? $item['isExisting'] : false;
        
        if ($is_existing) {
            // Update existing record (hapus updated_at)
            $update_stmt = $pdo->prepare("
                UPDATE absensi 
                SET kategori = ?, keterangan = ?
                WHERE personel_id = ? AND tanggal = ? AND status = 'TIDAK HADIR'
            ");
            
            if ($update_stmt->execute([$kategori, $keterangan, $personel_id, $tanggal])) {
                $updated_count++;
            }
        } else {
            // Check if record already exists
            $check_stmt = $pdo->prepare("
                SELECT COUNT(*) FROM absensi 
                WHERE personel_id = ? AND tanggal = ?
            ");
            $check_stmt->execute([$personel_id, $tanggal]);
            
            if ($check_stmt->fetchColumn() > 0) {
                // Update existing record (hapus updated_at)
                $update_stmt = $pdo->prepare("
                    UPDATE absensi 
                    SET status = 'TIDAK HADIR', kategori = ?, keterangan = ?
                    WHERE personel_id = ? AND tanggal = ?
                ");
                
                if ($update_stmt->execute([$kategori, $keterangan, $personel_id, $tanggal])) {
                    $updated_count++;
                }
            } else {
                // Insert new record (hapus created_at dan updated_at)
                $insert_stmt = $pdo->prepare("
                    INSERT INTO absensi (personel_id, tanggal, status, kategori, keterangan) 
                    VALUES (?, ?, 'TIDAK HADIR', ?, ?)
                ");
                
                if ($insert_stmt->execute([$personel_id, $tanggal, $kategori, $keterangan])) {
                    $saved_count++;
                }
            }
        }
    }
    
    $pdo->commit();
    
    $total_processed = $saved_count + $updated_count;
    $message = "Data berhasil diproses: {$saved_count} data baru, {$updated_count} data diupdate";
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'count' => $total_processed,
        'new' => $saved_count,
        'updated' => $updated_count
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
<?php
session_start();
require_once '../inc/db.php';

// Debug log
error_log('POST data: ' . print_r($_POST, true));

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if ($_POST && isset($_POST['data']) && !empty($_POST['data'])) {
    $data = json_decode($_POST['data'], true);
    $tanggal = date('Y-m-d');
    
    // Debug log
    error_log('Decoded data: ' . print_r($data, true));
    
    if (!$data || !is_array($data)) {
        $_SESSION['error_message'] = 'Data tidak valid!';
        header('Location: dashboard.php');
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        $success_count = 0;
        
        foreach ($data as $item) {
            if (!isset($item['id']) || !isset($item['keterangan']) || !isset($item['kategori'])) {
                continue;
            }
            
            $personel_id = $item['id'];
            $kategori = $item['kategori'];
            $keterangan = $item['keterangan'];
            $isExisting = isset($item['isExisting']) ? $item['isExisting'] : false;
            
            error_log("Processing: ID=$personel_id, Kategori=$kategori, Keterangan=$keterangan, IsExisting=" . ($isExisting ? 'yes' : 'no'));
            
            if ($isExisting) {
                // Update existing record
                $update_stmt = $pdo->prepare("
                    UPDATE absensi 
                    SET kategori = ?, keterangan = ? 
                    WHERE personel_id = ? AND tanggal = ? AND status = 'TIDAK HADIR'
                ");
                $result = $update_stmt->execute([$kategori, $keterangan, $personel_id, $tanggal]);
                if ($result) $success_count++;
            } else {
                // Check if record already exists for today
                $check_stmt = $pdo->prepare("SELECT id, status FROM absensi WHERE personel_id = ? AND tanggal = ?");
                $check_stmt->execute([$personel_id, $tanggal]);
                $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    // Update existing record
                    $update_stmt = $pdo->prepare("
                        UPDATE absensi 
                        SET status = 'TIDAK HADIR', kategori = ?, keterangan = ? 
                        WHERE personel_id = ? AND tanggal = ?
                    ");
                    $result = $update_stmt->execute([$kategori, $keterangan, $personel_id, $tanggal]);
                } else {
                    // Insert new record
                    $insert_stmt = $pdo->prepare("
                        INSERT INTO absensi (personel_id, tanggal, jam, status, kategori, keterangan) 
                        VALUES (?, ?, NOW(), 'TIDAK HADIR', ?, ?)
                    ");
                    $result = $insert_stmt->execute([$personel_id, $tanggal, $kategori, $keterangan]);
                }
                
                if ($result) $success_count++;
            }
        }
        
        $pdo->commit();
        
        // Set success message
        $_SESSION['success_message'] = "Berhasil menyimpan $success_count data keterangan!";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Database error: ' . $e->getMessage());
        $_SESSION['error_message'] = 'Terjadi kesalahan: ' . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = 'Tidak ada data yang dikirim!';
    error_log('No data received in POST');
}

header('Location: dashboard.php');
exit;
?>
<?php
session_start();
require_once '../inc/db.php';

// Cek login & role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../login.php');
    exit;
}

// Ambil body JSON atau form
$rawBody = file_get_contents('php://input');
$payload = $rawBody ? json_decode($rawBody, true) : null;

if ($payload === null && isset($_POST['data'])) {
    $decoded = json_decode($_POST['data'], true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $payload = ['data' => $decoded, 'tanggal' => $_POST['tanggal'] ?? date('Y-m-d')];
    }
}

if (!$payload || empty($payload['data'])) {
    die("Invalid input data");
}

$data_array = $payload['data'];
$tanggal = $payload['tanggal'] ?? date('Y-m-d');

// 🔹 Ambil deletedIds dari form
$deletedIds = [];
if (isset($_POST['deletedIds']) && $_POST['deletedIds'] !== '') {
    $decodedDeleted = json_decode($_POST['deletedIds'], true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $deletedIds = $decodedDeleted;
    }
}

try {
    $pdo->beginTransaction();

    // === PROSES SIMPAN / UPDATE ===
    foreach ($data_array as $item) {
        if (!isset($item['id'], $item['kategori'], $item['keterangan']))
            continue;

        $personel_id = intval($item['id']);
        $kategori = trim($item['kategori']);
        $keterangan = trim($item['keterangan']);
        $is_existing = $item['isExisting'] ?? false;

        if ($is_existing) {
            $update_stmt = $pdo->prepare("
                UPDATE absensi 
                SET kategori = ?, keterangan = ?
                WHERE personel_id = ? AND tanggal = ? AND status = 'TIDAK HADIR'
            ");
            $update_stmt->execute([$kategori, $keterangan, $personel_id, $tanggal]);
        } else {
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM absensi WHERE personel_id = ? AND tanggal = ?");
            $check_stmt->execute([$personel_id, $tanggal]);

            if ($check_stmt->fetchColumn() > 0) {
                $update_stmt = $pdo->prepare("
                    UPDATE absensi 
                    SET status = 'TIDAK HADIR', kategori = ?, keterangan = ?
                    WHERE personel_id = ? AND tanggal = ?
                ");
                $update_stmt->execute([$kategori, $keterangan, $personel_id, $tanggal]);
            } else {
                $insert_stmt = $pdo->prepare("
                    INSERT INTO absensi (personel_id, tanggal, status, kategori, keterangan) 
                    VALUES (?, ?, 'TIDAK HADIR', ?, ?)
                ");
                $insert_stmt->execute([$personel_id, $tanggal, $kategori, $keterangan]);
            }
        }
    }

    // === PROSES HAPUS ===
    if (!empty($deletedIds)) {
        $delete_stmt = $pdo->prepare("
            DELETE FROM absensi WHERE personel_id = ? AND tanggal = ? AND status = 'TIDAK HADIR'
        ");
        foreach ($deletedIds as $delId) {
            $delete_stmt->execute([$delId, $tanggal]);
        }
    }

    $pdo->commit();

    $_SESSION['success_message'] = "Perubahan berhasil disimpan.";
    header("Location: ../admin/dashboard.php");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    header("Location: ../admin/dashboard.php");
    exit;
}

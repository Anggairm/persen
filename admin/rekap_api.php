<?php
session_start();
require_once '../inc/db.php';

$role = $_SESSION['role'];
$satker = $_SESSION['satker'];

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$filterSatker = $_GET['satker'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$query = "
    SELECT a.personel_id, a.tanggal, a.status, a.kategori, a.keterangan, 
           a.jam AS jam_masuk, a.keluar AS jam_keluar,
           p.id, p.nrp, p.nama, p.pangkat, p.korps, p.satker
    FROM personel p
    LEFT JOIN absensi a ON a.personel_id = p.id AND a.tanggal = ?
";
$params = [$tanggal];

if ($role === 'admin') {
    $query .= " WHERE p.satker = ?";
    $params[] = $satker;
} elseif ($filterSatker) {
    $query .= " WHERE p.satker = ?";
    $params[] = $filterSatker;
}

if ($filterStatus) {
    $query .= $role === 'superadmin' || $filterSatker ? " AND" : " WHERE";
    $query .= " a.status = ?";
    $params[] = $filterStatus;
}

$query .= " ORDER BY p.nama ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($data);

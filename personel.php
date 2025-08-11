<?php
require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_GET['hapus'])) {
    $stmt = $pdo->prepare("DELETE FROM personel WHERE id=?");
    $stmt->execute([$_GET['hapus']]);
    header("Location: personel.php");
    exit;
}

// Impor dari Excel
if (isset($_POST['aksi']) && $_POST['aksi'] == 'impor' && isset($_FILES['file_excel'])) {
    $file = $_FILES['file_excel']['tmp_name'];
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    foreach ($rows as $i => $row) {
        if ($i == 0) continue; // skip header
        [$nrp, $nama, $pangkat, $korps, $jabatan, $satker, $password, $role] = $row;
        $stmt = $pdo->prepare("INSERT INTO personel (nrp, nama, pangkat, korps, jabatan, satker, password, role)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $nrp, $nama, $pangkat, $korps, $jabatan, $satker,
            password_hash($password, PASSWORD_DEFAULT), $role ?? 'user'
        ]);
    }
    header("Location: personel.php");
    exit;
}

$personel = $pdo->query("SELECT * FROM personel ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
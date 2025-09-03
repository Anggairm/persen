<?php
require_once '../inc/auth_admin.php';
require_once '../inc/db.php';
require '../excel/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_POST['upload'])) {
    $file = $_FILES['file']['tmp_name'];

    if (!$file) {
        echo "File tidak ditemukan!";
        exit;
    }

    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet()->toArray();

    foreach ($sheet as $i => $row) {
        if ($i == 0)
            continue; // skip header

        $nama = $row[0];
        $nrp = $row[1];
        $pangkat = $row[2];
        $korps = $row[3];
        $jabatan = $row[4];
        $satker = $row[5];
        $password_plain = $row[6];
        $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO personel (nama, nrp, pangkat, korps, jabatan, satker, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, 'user')");
        $stmt->execute([$nama, $nrp, $pangkat, $korps, $jabatan, $satker, $password_hash]);
    }

    echo "<script>alert('Data berhasil diunggah.'); window.location = 'dashboard.php';</script>";
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Upload Personel - PERSEN</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" type="image/png" href="../assets/css/logo.png">
</head>

<body>
    <div class="container">
        <h2>Upload Data Personel (Excel)</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="file" accept=".xlsx,.xls" required>
            <button type="submit" name="upload">Upload</button>
        </form>
        <p><strong>Format kolom Excel:</strong><br>
            nama, nrp, pangkat, korps, jabatan, satker, password</p>
    </div>
</body>

</html>
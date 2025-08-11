<?php
require_once '../inc/auth_admin.php';
require_once '../inc/db.php';

$stmt = $pdo->query("SELECT * FROM personel ORDER BY nama ASC");
$personel = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Personel - PERSEN</title>
</head>
<body>
    <h2>Data Personel</h2>

    <a href="upload_personel.php"><button>Upload Data Excel</button></a>
    <br><br>

    <table border="1" cellpadding="5">
        <tr>
            <th>NRP</th>
            <th>Nama</th>
            <th>Role</th>
        </tr>
        <?php foreach ($personel as $p): ?>
        <tr>
            <td><?= $p['nrp'] ?></td>
            <td><?= $p['nama'] ?></td>
            <td><?= $p['role'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>

<?php
require_once '../inc/auth_admin.php';
require_once '../inc/db.php';

$jenis = $_GET['jenis'] ?? 'dd';
$tanggal = date('Y-m-d');

// Ambil semua personel
$stmt = $pdo->query("SELECT * FROM personel WHERE role = 'user'");
$personel = $stmt->fetchAll();

// Cek siapa yang belum absen
$stmt2 = $pdo->prepare("SELECT personel_id FROM absensi WHERE tanggal = ?");
$stmt2->execute([$tanggal]);
$sudah_absen = $stmt2->fetchAll(PDO::FETCH_COLUMN);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail Kategori <?= strtoupper($jenis) ?> - ABSENSI PERSONEL</title>
</head>
<body>
    <h2>Detail Kategori: <?= strtoupper($jenis) ?></h2>

    <form method="post" action="">
        <table border="1" cellpadding="5">
            <tr>
                <th>NRP</th>
                <th>Nama</th>
                <th>Keterangan</th>
            </tr>
            <?php foreach ($personel as $p): ?>
                <?php if (!in_array($p['id'], $sudah_absen)): ?>
                <tr>
                    <td><?= $p['nrp'] ?></td>
                    <td><?= $p['nama'] ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="personel_id" value="<?= $p['id'] ?>">
                            <input type="text" name="keterangan" placeholder="Masukkan keterangan">
                            <button type="submit" name="simpan">Simpan</button>
                        </form>
                    </td>
                </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </table>
    </form>

    <?php
    // Proses simpan
    if (isset($_POST['simpan'])) {
        $id = $_POST['personel_id'];
        $ket = $_POST['keterangan'];

        $stmt = $pdo->prepare("INSERT INTO absensi (personel_id, tanggal, jam, status, keterangan) VALUES (?, ?, NOW(), ?, ?)");
        $stmt->execute([$id, $tanggal, $jenis, $ket]);

        echo "<p style='color:green'>Data berhasil disimpan. <a href='detail_kategori.php?jenis=$jenis'>Refresh</a></p>";
    }
    ?>
</body>
</html>

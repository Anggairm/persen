<?php
session_start();
require_once '../../inc/db.php';

// Pastikan user adalah admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Ambil tanggal hari ini
$tanggal = date('Y-m-d');

// Ambil personel yang belum absen hari ini
$sql = "SELECT * FROM personel WHERE id NOT IN (
            SELECT personel_id FROM absensi WHERE tanggal = ?
        )";
$stmt = $pdo->prepare($sql);
$stmt->execute([$tanggal]);
$personel_belum_absen = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $personel_id = $_POST['personel_id'];
    $keterangan = $_POST['keterangan'];

    $stmt = $pdo->prepare("INSERT INTO absensi (personel_id, tanggal, jam, status, keterangan) VALUES (?, ?, NOW(), ?, ?)");
    $stmt->execute([$personel_id, $tanggal, 'CUTI', $keterangan]);

    header("Location: cuti.php?success=1");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>CUTI - PERSEN</title>
    <link rel="stylesheet" href="../../assets/css/keterangan.css">
</head>
<body>
    <div class="container">
        <h2>Input Personel CUTI</h2>

        <?php if (isset($_GET['success'])): ?>
            <p class="success">Data berhasil disimpan!</p>
        <?php endif; ?>

        <form method="post">
            <label for="personel_id">Pilih Personel</label>
            <select name="personel_id" required>
                <option value="">-- Pilih --</option>
                <?php foreach ($personel_belum_absen as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= $p['nrp'] ?> - <?= $p['nama'] ?></option>
                <?php endforeach; ?>
            </select>

            <label for="keterangan">Keterangan</label>
            <textarea name="keterangan" required></textarea>

            <button type="submit">Simpan</button>
        </form>
    </div>
</body>
</html>

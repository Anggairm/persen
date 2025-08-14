<?php
session_start();
require_once '../inc/db.php';

// Pastikan sudah start session dan koneksi database
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../login.php');
    exit;
}

$tanggal = date('Y-m-d');
$role = $_SESSION['role'];
$satker = $_SESSION['satker'];

// ====================
// Ambil daftar personel sesuai role
// ====================
$queryPersonel = "SELECT * FROM personel";
$paramsPersonel = [];

if ($role === 'admin') {
    $queryPersonel .= " WHERE satker = ?";
    $paramsPersonel[] = $satker;
}

$queryPersonel .= " ORDER BY nama ASC";

$stmt = $pdo->prepare($queryPersonel);
$stmt->execute($paramsPersonel);
$personel_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ====================
// Ambil absensi hari ini sesuai role
// ====================
$queryAbsensi = "
    SELECT a.personel_id, a.status, a.kategori, a.keterangan, a.jam, a.keluar,
           p.nrp, p.nama, p.pangkat, p.korps, p.satker
    FROM absensi a
    JOIN personel p ON a.personel_id = p.id
    WHERE a.tanggal = ?
";
$paramsAbsensi = [$tanggal];

if ($role === 'admin') {
    $queryAbsensi .= " AND p.satker = ?";
    $paramsAbsensi[] = $satker;
}

$queryAbsensi .= " ORDER BY p.nama ASC";

$absensi_stmt = $pdo->prepare($queryAbsensi);
$absensi_stmt->execute($paramsAbsensi);
$absensi_list = $absensi_stmt->fetchAll(PDO::FETCH_ASSOC);

// ====================
// Buat map absensi agar mudah akses
// ====================
$absensi_map = [];
foreach ($absensi_list as $a) {
    $absensi_map[$a['personel_id']] = [
        'status' => $a['status'],
        'kategori' => $a['kategori'],
        'keterangan' => $a['keterangan'],
        'jam_masuk' => $a['jam'],
        'jam_keluar' => $a['keluar']
    ];
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Rekap Harian Absensi</title>
    <link rel="stylesheet" href="../assets/css/rekap.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .tanggal {
            text-align: center;
            font-size: 16px;
            color: #555;
            margin-bottom: 25px;
        }

        .rekap-table {
            width: 100%;
            border-collapse: collapse;
        }

        .rekap-table th,
        .rekap-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }

        .rekap-table th {
            background-color: #1976d2;
            color: #fff;
            font-weight: 600;
        }

        .rekap-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .status-hadir {
            color: green;
            font-weight: bold;
        }

        .status-tidak {
            color: red;
            font-weight: bold;
        }

        .status-kosong {
            color: #888;
            font-style: italic;
        }

        .dashboard-button {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 30px;
            text-decoration: none;
            background-color: #1976d2;
            color: white;
            border-radius: 8px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .dashboard-button:hover {
            background-color: #125ea6;
        }

        @media (max-width: 768px) {

            .rekap-table th,
            .rekap-table td {
                padding: 8px;
                font-size: 14px;
            }

            h1 {
                font-size: 22px;
            }

            .dashboard-button {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Rekapitulasi Absensi Harian</h1>
        <p class="tanggal"><?= date('d F Y') ?></p>

        <table class="rekap-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>NRP</th>
                    <th>Nama</th>
                    <th>Pangkat</th>
                    <th>Korps</th>
                    <th>Satker</th>
                    <th>Absen</th>
                    <th>Masuk</th>
                    <th>Keluar</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                foreach ($personel_list as $p):
                    $id = $p['id'];
                    $absen = isset($absensi_map[$id]) ? $absensi_map[$id] : null;

                    if ($absen) {
                        if ($absen['status'] === 'HADIR') {
                            $status = '<span class="status-hadir">Hadir</span>';
                            $keterangan = '-';
                        } else {
                            $status = '<span class="status-tidak">Tidak Hadir</span>';
                            $keterangan = htmlspecialchars($absen['kategori'] . ' - ' . $absen['keterangan']);
                        }
                    } else {
                        $status = '<span class="status-kosong">-</span>';
                        $keterangan = '<span class="status-kosong">-</span>';
                    }
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($p['nrp']) ?></td>
                        <td><?= htmlspecialchars($p['nama']) ?></td>
                        <td><?= htmlspecialchars($p['pangkat']) ?></td>
                        <td><?= htmlspecialchars($p['korps']) ?></td>
                        <td><?= htmlspecialchars($p['satker']) ?></td>
                        <td><?= $status ?></td>
                        <td><?= isset($absen['jam_masuk']) ? htmlspecialchars($absen['jam_masuk']) : '-' ?></td>
                        <td><?= isset($absen['jam_keluar']) ? htmlspecialchars($absen['jam_keluar']) : '-' ?></td>
                        <td><?= $keterangan ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a href="dashboard.php" class="dashboard-button">kembali</a>
    </div>
</body>

</html>
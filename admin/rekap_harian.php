<?php
session_start();
require_once '../inc/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$tanggal = date('Y-m-d');
$filter_satker = isset($_GET['satker']) ? $_GET['satker'] : '';
$search_nama = isset($_GET['search']) ? $_GET['search'] : '';

// Query untuk mendapatkan daftar satker
$satker_stmt = $pdo->query("SELECT DISTINCT satker FROM personel ORDER BY satker");
$satker_list = $satker_stmt->fetchAll(PDO::FETCH_COLUMN);

// Build WHERE clause untuk filter
$where_conditions = [];
$params = [];

if (!empty($filter_satker)) {
    $where_conditions[] = "p.satker = ?";
    $params[] = $filter_satker;
}

if (!empty($search_nama)) {
    $where_conditions[] = "p.nama LIKE ?";
    $params[] = '%' . $search_nama . '%';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = ' WHERE ' . implode(' AND ', $where_conditions);
}

// Ambil personel sesuai filter
$personel_query = "SELECT * FROM personel p" . $where_clause . " ORDER BY p.nama";
$personel_stmt = $pdo->prepare($personel_query);
$personel_stmt->execute($params);
$personel_list = $personel_stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil absensi hari ini untuk personel yang sesuai filter
if (!empty($personel_list)) {
    $personel_ids = array_column($personel_list, 'id');
    $placeholders = str_repeat('?,', count($personel_ids) - 1) . '?';
    
    $absensi_query = "
        SELECT a.personel_id, a.status, a.kategori, a.keterangan, a.jam, a.keluar,
               p.nrp, p.nama, p.pangkat, p.korps, p.satker
        FROM absensi a
        JOIN personel p ON a.personel_id = p.id
        WHERE a.tanggal = ? AND a.personel_id IN ($placeholders)
    ";
    $absensi_params = array_merge([$tanggal], $personel_ids);
    $absensi_stmt = $pdo->prepare($absensi_query);
    $absensi_stmt->execute($absensi_params);
    $absensi_list = $absensi_stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $absensi_list = [];
}

// Buat array absensi dengan key personel_id
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

// Hitung statistik
$total_personel = count($personel_list);
$hadir = 0;
$tidak_hadir = 0;
$belum_absen = 0;

foreach ($personel_list as $p) {
    $id = $p['id'];
    if (isset($absensi_map[$id])) {
        if ($absensi_map[$id]['status'] === 'HADIR') {
            $hadir++;
        } else {
            $tidak_hadir++;
        }
    } else {
        $belum_absen++;
    }
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
            max-width: 1200px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
        }

        .back-button-top {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 10;
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 10px;
            margin-top: 40px;
        }

        .tanggal {
            text-align: center;
            font-size: 16px;
            color: #555;
            margin-bottom: 25px;
        }

        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }

        .filter-row {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-filter,
        .btn-reset {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            color: white;
        }

        .btn-filter {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .btn-filter:hover {
            background: linear-gradient(135deg, #2980b9, #21618c);
            transform: translateY(-1px);
        }

        .btn-reset {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
        }

        .btn-reset:hover {
            background: linear-gradient(135deg, #7f8c8d, #6c7b7d);
            transform: translateY(-1px);
        }

        .stats-section {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #1976d2, #1565c0);
            border-radius: 8px;
            color: white;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 28px;
            font-weight: bold;
            display: block;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .export-section {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .export-btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.3s ease;
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .btn-pdf {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .btn-pdf:hover {
            background: linear-gradient(135deg, #c0392b, #a93226);
        }

        .btn-excel {
            background: linear-gradient(135deg, #27ae60, #229954);
        }

        .btn-excel:hover {
            background: linear-gradient(135deg, #229954, #1e8449);
        }

        .rekap-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .rekap-table th,
        .rekap-table td {
            padding: 12px 8px;
            border: 1px solid #ddd;
            text-align: center;
            font-size: 14px;
        }

        .rekap-table th {
            background: linear-gradient(135deg, #1976d2, #1565c0);
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .rekap-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .rekap-table tr:hover {
            background-color: #f0f8ff;
        }

        .status-hadir {
            color: #27ae60;
            font-weight: bold;
            background-color: #e8f5e8;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .status-tidak {
            color: #e74c3c;
            font-weight: bold;
            background-color: #fde8e8;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .status-kosong {
            color: #95a5a6;
            font-style: italic;
        }

        .dashboard-button {
            display: inline-block;
            padding: 10px 20px;
            text-decoration: none;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .dashboard-button:hover {
            background: linear-gradient(135deg, #2980b9, #21618c);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-style: italic;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .back-button-top {
                position: relative;
                top: 0;
                left: 0;
                margin-bottom: 20px;
            }

            h1 {
                margin-top: 0;
                font-size: 20px;
            }

            .filter-row {
                flex-direction: column;
            }

            .filter-buttons {
                justify-content: center;
                margin-top: 15px;
            }

            .stats-section {
                flex-direction: column;
                gap: 15px;
            }

            .rekap-table th, 
            .rekap-table td {
                padding: 8px 4px;
                font-size: 12px;
            }

            .export-btn {
                display: block;
                margin: 10px auto;
                width: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-button-top">
            <a href="dashboard.php" class="dashboard-button">Kembali</a>
        </div>
        
        <h1>Rekapitulasi Absensi Harian</h1>
        <p class="tanggal"><?= date('d F Y') ?></p>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="satker">Filter Satker:</label>
                        <select name="satker" id="satker">
                            <option value="">-- Semua Satker --</option>
                            <?php foreach ($satker_list as $satker): ?>
                                <option value="<?= htmlspecialchars($satker) ?>" 
                                        <?= ($filter_satker === $satker) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($satker) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="search">Cari Nama Personel:</label>
                        <input type="text" name="search" id="search" 
                               value="<?= htmlspecialchars($search_nama) ?>" 
                               placeholder="Masukkan nama personel...">
                    </div>
                    
                    <div class="filter-buttons">
                        <button type="submit" class="btn-filter">Filter</button>
                        <a href="?" class="btn-reset">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Statistics Section -->
        <?php if ($total_personel > 0): ?>
        <div class="stats-section">
            <div class="stat-item">
                <span class="stat-number"><?= $total_personel ?></span>
                <span class="stat-label">Total Personel</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= $hadir ?></span>
                <span class="stat-label">Hadir</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= $tidak_hadir ?></span>
                <span class="stat-label">Tidak Hadir</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= $belum_absen ?></span>
                <span class="stat-label">Belum Absen</span>
            </div>
        </div>
        <?php endif; ?>

        <div class="export-section">
            <h3 style="margin-top: 0; color: #2c3e50;">Export Data</h3>
            <a href="export_pdf.php?tanggal=<?= $tanggal ?>&satker=<?= urlencode($filter_satker) ?>&search=<?= urlencode($search_nama) ?>" class="export-btn btn-pdf">
                Export PDF
            </a>
            <a href="export_excel.php?tanggal=<?= $tanggal ?>&satker=<?= urlencode($filter_satker) ?>&search=<?= urlencode($search_nama) ?>" class="export-btn btn-excel">
                Export Excel
            </a>
        </div>

        <?php if ($total_personel > 0): ?>
        <table class="rekap-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>NRP</th>
                    <th>Nama</th>
                    <th>Pangkat</th>
                    <th>Korps</th>
                    <th>Satker</th>
                    <th>Status</th>
                    <th>Jam Masuk</th>
                    <th>Jam Keluar</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1; 
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
                        $jam_masuk = $absen['jam_masuk'] ? htmlspecialchars($absen['jam_masuk']) : '-';
                        $jam_keluar = $absen['jam_keluar'] ? htmlspecialchars($absen['jam_keluar']) : '-';
                    } else {
                        $status = '<span class="status-kosong">-</span>';
                        $jam_masuk = '-';
                        $jam_keluar = '-';
                        $keterangan = '<span class="status-kosong">-</span>';
                    }
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($p['nrp']) ?></td>
                    <td style="text-align: left;"><?= htmlspecialchars($p['nama']) ?></td>
                    <td><?= htmlspecialchars($p['pangkat']) ?></td>
                    <td><?= htmlspecialchars($p['korps']) ?></td>
                    <td><?= htmlspecialchars($p['satker']) ?></td>
                    <td><?= $status ?></td>
                    <td><?= $jam_masuk ?></td>
                    <td><?= $jam_keluar ?></td>
                    <td style="text-align: left;"><?= $keterangan ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-data">
            <p>Tidak ada data personel yang sesuai dengan filter yang dipilih.</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
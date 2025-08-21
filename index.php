<?php
session_start();
require_once 'inc/db.php';

date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: login.php');
    exit;
}

$tanggal = date('Y-m-d');
$role = $_SESSION['role'];
$satker = $_SESSION['satker'];

$filterWhere = "";
$filterJoin = "";
$params = [];

if ($role === 'admin') {
    // Admin hanya lihat sesuai satker
    $filterWhere = " WHERE p.satker = ? ";
    $filterJoin = " AND p.satker = ? ";
    $params[] = $satker;
}

// Total personel
$sqlTotal = "SELECT COUNT(*) FROM personel p" . $filterWhere;
$stmt = $pdo->prepare($sqlTotal);
$stmt->execute($params);
$total_personel = $stmt->fetchColumn();

// Yang benar-benar HADIR (bukan TIDAK HADIR)
$sqlHadir = "
    SELECT COUNT(*) 
    FROM absensi a
    JOIN personel p ON p.id = a.personel_id
    WHERE a.tanggal = ? AND a.status = 'HADIR'
    $filterJoin
";
$paramsHadir = array_merge([$tanggal], ($role === 'admin' ? [$satker] : []));
$stmt = $pdo->prepare($sqlHadir);
$stmt->execute($paramsHadir);
$jumlah_hadir = $stmt->fetchColumn();

// // Total personel
// $total_stmt = $pdo->query("SELECT COUNT(*) FROM personel");
// $total_personel = $total_stmt->fetchColumn();

// // Sudah absen
// $hadir_stmt = $pdo->prepare("SELECT COUNT(*) FROM absensi WHERE tanggal = ?");
// $hadir_stmt->execute([$tanggal]);
// $jumlah_hadir = $hadir_stmt->fetchColumn();

// Belum absen
$jumlah_kurang = $total_personel - $jumlah_hadir;

// Buat QR code (link ke scan)
$qr_data = $tanggal; // Ganti dengan domain asli

require 'assets/phpqrcode/qrlib.php';
$qr_file = 'assets/img/qrcode.png';

// Hapus QR lama agar selalu fresh
if (file_exists($qr_file)) {
    unlink($qr_file);
}

// Buat QR baru
QRcode::png($qr_data, $qr_file, QR_ECLEVEL_H, 8);

// Array bulan Indonesia
$bulan_indonesia = [
    1 => 'JANUARI',
    2 => 'FEBRUARI',
    3 => 'MARET',
    4 => 'APRIL',
    5 => 'MEI',
    6 => 'JUNI',
    7 => 'JULI',
    8 => 'AGUSTUS',
    9 => 'SEPTEMBER',
    10 => 'OKTOBER',
    11 => 'NOVEMBER',
    12 => 'DESEMBER'
];

// Array hari Indonesia
$hari_indonesia = [
    'Sunday' => 'MINGGU',
    'Monday' => 'SENIN',
    'Tuesday' => 'SELASA',
    'Wednesday' => 'RABU',
    'Thursday' => 'KAMIS',
    'Friday' => 'JUMAT',
    'Saturday' => 'SABTU'
];

$allSatkerOptions = [
    'SAHLIKASAU',
    'DISOPSLATAU',
    'PUSKODALAU',
    'DISPENAU',
    'INSPEKTORAT JENDERAL',
    'PUSLAIKLAMBANGJA',
    'DISPAMSANAU',
    'SOPSAU',
    'DISBANGOPSAU',
    'SKOMLEKAU',
    'SRENAAU',
    'DISADAAU',
    'SLOGAU',
    'DISAERO',
    'DISKOMLEKAU',
    'DISKONSAU',
    'DENMABESAU',
    'DISINFOLAHTAAU',
    'DISKUAU',
    'SETUMAU',
    'DISWATPERS',
    'SINTELAU',
    'DISDIKAU',
    'DISKESAU',
    'SPERSAU',
    'DISMINPERSAU'
];

$hari_en = date('l');
$hari = $hari_indonesia[$hari_en];
$tanggal_format = date('j');
$bulan = $bulan_indonesia[date('n')];
$tahun = date('Y');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIAPERS - Absensi Hari Ini</title>
    <meta http-equiv="refresh" content="120"> <!-- Refresh otomatis setiap 60 detik -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial Black', Arial, sans-serif;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0)), url('assets/img/login.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            min-height: 100vh;
            color: white;
            overflow-x: hidden;
        }



        .header {
            text-align: center;
            padding: 10px 20px;
            background: linear-gradient(45deg, rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.1));
            backdrop-filter: blur(10px);
        }

        .main-title {
            font-size: clamp(3rem, 8vw, 6rem);
            font-weight: 900;
            letter-spacing: 6px;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #ffffff, #e6e6e6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            font-size: clamp(1rem, 2.5vw, 1.5rem);
            font-weight: bold;
            letter-spacing: 2px;
            opacity: 0.95;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            gap: 40px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .left-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .date-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .date-text {
            font-size: clamp(1.2rem, 3vw, 1.8rem);
            font-weight: bold;
            letter-spacing: 1px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .stat-box {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 10px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }

        .stat-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }

        .stat-box:hover::before {
            left: 100%;
        }

        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }

        .stat-box.total {
            border-left: 50px solid #4a90e2;
        }

        .stat-box.kurang {
            border-left: 50px solid #e74c3c;
        }

        .stat-box.hadir {
            border-left: 50px solid #27ae60;
        }

        .stat-title {
            font-size: clamp(3rem, 2vw, 1.2rem);
            font-weight: bold;
            margin-bottom: 15px;
            letter-spacing: 1px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .stat-number {
            font-size: clamp(2rem, 4vw, 2.5rem);
            font-weight: 900;
            background: linear-gradient(45deg, #ffffff, #e6e6e6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .qr-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .qr-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 30px;
            padding: 30px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }

        .qr-container:hover {
            transform: scale(1.02);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }

        .qr-container img {
            width: clamp(220px, 20vw, 295px);
            height: clamp(220px, 20vw, 295px);
            border-radius: 10px;
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .content {
                flex-direction: column;
                gap: 30px;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }

            .main-title {
                letter-spacing: 4px;
            }

            .subtitle {
                letter-spacing: 2px;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 30px 15px;
            }

            .content {
                padding: 15px;
                gap: 25px;
            }

            .date-section,
            .stat-box,
            .qr-container {
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .main-title {
                letter-spacing: 2px;
            }

            .subtitle {
                letter-spacing: 1px;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 20px 10px;
            }

            .content {
                padding: 10px;
                gap: 20px;
            }

            .date-section,
            .stat-box,
            .qr-container {
                padding: 15px;
                border-radius: 15px;
            }

            .qr-container {
                padding: 15px;
            }
        }

        /* Animation */
        .container {
            animation: fadeIn 1s ease-out;
        }

        .stat-box {
            animation: slideUp 0.8s ease-out forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        .stat-box:nth-child(1) {
            animation-delay: 0.1s;
        }

        .stat-box:nth-child(2) {
            animation-delay: 0.2s;
        }

        .stat-box:nth-child(3) {
            animation-delay: 0.3s;
        }

        .qr-container {
            animation: zoomIn 1s ease-out 0.4s forwards;
            opacity: 0;
            transform: scale(0.8);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes zoomIn {
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Loading animation for QR */
        .qr-container::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }

        .scroll-container {
            overflow: hidden;
            white-space: nowrap;
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 0px;
            padding: 15px;
            color: white;
            font-family: sans-serif;
        }

        .scroll-row {
            display: inline-block;
            margin-right: 50px;
            /* jarak antar baris */
            animation: scrollRight 60s linear infinite;
        }

        /* Animasi geser */
        @keyframes scrollRight {
            0% {
                transform: translateX(100vw);
                /* mulai dari luar kanan */
            }

            100% {
                transform: translateX(-100%);
                /* keluar ke kiri */
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header"
            style="display: flex; align-items: center; justify-content: center; gap: 30px; text-align: center;">
            <img src="assets/css/logo.png" alt="Logo" style="width: 120px; height: 120px;">
            <div>
                <h1 class="main-title" style="margin: 0;">SIAPERS AMPUH</h1>
                <p class="subtitle" style="margin: 0;">APLIKASI ABSENSI PERSONEL TNI AU AMPUH</p>
            </div>
        </div>


        <div class="content">
            <div class="left-section">
                <div class="date-section">
                    <div class="date-text"><?= $hari ?>, <?= $tanggal_format ?> <?= $bulan ?> <?= $tahun ?></div>
                </div>

                <div class="stats-grid">
                    <div class="stat-box total">
                        <h2 class="stat-title">JUMLAH</h2>
                        <div class="stat-number"><?= $total_personel ?></div>
                    </div>
                    <div class="stat-box kurang">
                        <h2 class="stat-title">KURANG</h2>
                        <div class="stat-number"><?= $jumlah_kurang ?></div>
                    </div>
                    <div class="stat-box hadir">
                        <h2 class="stat-title">HADIR</h2>
                        <div class="stat-number"><?= $jumlah_hadir ?></div>
                    </div>
                </div>
            </div>

            <div class="qr-section">
                <div class="qr-container">
                    <!-- Tambahkan timestamp agar QR tidak di-cache browser -->
                    <img src="<?= $qr_file ?>?v=<?= time() ?>" alt="QR Code Absensi">
                </div>
            </div>
        </div>
    </div>

    <div class="scroll-container"
        style="width: 100%; overflow: hidden; white-space: nowrap; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 0px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); paddingTop: 50px; color:white; font-size:18px;">

        <div class="scroll-content" style="display: inline-block; animation: scrollRight 60s linear infinite;">
            <?php
            $satkerCounts = [];
            $stmt = $pdo->prepare("SELECT satker, COUNT(*) as count FROM personel GROUP BY satker");
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $satkerCounts[$row['satker']] = $row['count'];
            }

            // Ambil jumlah hadir per satker
            $stmtHadir = $pdo->prepare("
            SELECT p.satker, COUNT(*) as hadir_count 
            FROM absensi a
            JOIN personel p ON p.id = a.personel_id
            WHERE a.tanggal = ? AND a.status = 'HADIR'
            GROUP BY p.satker
        ");
            $stmtHadir->execute([$tanggal]);
            $hadirCounts = $stmtHadir->fetchAll(PDO::FETCH_KEY_PAIR);

            // Tampilkan data satker dalam format teks berjalan
            $no = 1;
            foreach ($allSatkerOptions as $satker) {
                $count = isset($satkerCounts[$satker]) ? $satkerCounts[$satker] : 0;
                $hadirCount = isset($hadirCounts[$satker]) ? $hadirCounts[$satker] : 0;
                echo "{$satker} {$hadirCount}/{$count} &nbsp; | &nbsp; ";
                $no++;
            }
            ?>
        </div>
    </div>


    <script>
        // Atur waktu refresh dalam menit
        let refreshMinutes = 2;
        let refreshTimer = refreshMinutes * 60; // konversi ke detik

        setInterval(() => {
            refreshTimer--;
            if (refreshTimer <= 0) {
                document.body.style.opacity = '0.8';
                setTimeout(() => {
                    location.reload();
                }, 500);
            }
        }, 1000);

        // Efek animasi angka
        document.querySelectorAll('.stat-number').forEach((el, index) => {
            const finalValue = parseInt(el.textContent);
            let currentValue = 0;
            const increment = Math.ceil(finalValue / 30);

            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= finalValue) {
                    currentValue = finalValue;
                    clearInterval(timer);
                }
                el.textContent = currentValue;
            }, 50 + (index * 20));
        });
    </script>

</body>

</html>
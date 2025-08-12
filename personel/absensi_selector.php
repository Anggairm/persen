<?php
session_start();
if (!isset($_SESSION['personel_id'])) {
    header('Location: login_personel.php');
    exit;
}

date_default_timezone_set("Asia/Jakarta");
$jam_sekarang = date("H:i");

$disable_masuk  = ($jam_sekarang < "08:00"); // disable jika lewat jam 8
$enable_keluar  = ($jam_sekarang >= "08:00"); // enable jika jam >= 15
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan QR Code</title>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .scan-container {
            background: white;
            border-radius: 16px;
            padding: 32px 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 480px;
            width: 100%;
            text-align: center;
        }

        .header {
            margin-bottom: 24px;
        }

        h2 {
            color: #2d3748;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .welcome-text {
            color: #718096;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .logout-form {
            margin-bottom: 28px;
        }

        .logout-button {
            background: #e53e3e;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            outline: none;
        }

        .logout-button:hover {
            background: #c53030;
        }

        .logout-button:active {
            transform: translateY(1px);
        }

        .scanner-wrapper {
            background: #f7fafc;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
        }

        #reader {
            width: 100%;
            max-width: 320px;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
        }

        #reader video {
            border-radius: 8px;
        }

        #result {
            min-height: 24px;
            padding: 12px;
            background: #edf2f7;
            border-radius: 8px;
            color: #4a5568;
            font-size: 14px;
            font-weight: 500;
            border: 2px solid transparent;
        }

        #result.success {
            background: #c6f6d5;
            color: #22543d;
            border-color: #9ae6b4;
        }

        #result.error {
            background: #fed7d7;
            color: #742a2a;
            border-color: #fc8181;
        }

        .scan-instruction {
            color: #718096;
            font-size: 14px;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        /* Action Buttons (Absen Masuk & Absen Keluar) */
        .button-container {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin: 20px 0;
        }

        .action-button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 40%;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            color: white;
            outline: none;
            text-decoration: none;
        }

        .action-button i {
            margin-right: 8px;
        }

        .absen-masuk {
            background: #4299e1;
        }

        .absen-masuk:hover {
            background: #3182ce;
        }

        .absen-keluar {
            background: #e53e3e;
        }

        .absen-keluar:hover {
            background: #c53030;
        }

        /* Mobile responsiveness */
        @media (max-width: 480px) {
            .scan-container {
                padding: 24px 16px;
                margin: 16px;
            }

            h2 {
                font-size: 24px;
            }

            .welcome-text {
                font-size: 15px;
            }

            #reader {
                max-width: 280px;
            }

            /* Flexbox untuk tombol absen agar vertikal di mobile */
            .button-container {
                flex-direction: column;
                gap: 12px;
            }

            .action-button {
                width: 100%;
                justify-content: center;
            }
        }

        /* QR Scanner styling overrides */
        #reader__header_message {
            display: none !important;
        }

        #reader__dashboard_section {
            margin-top: 12px !important;
        }

        #reader__dashboard_section button {
            background: #4299e1 !important;
            border: none !important;
            border-radius: 6px !important;
            padding: 8px 16px !important;
            color: white !important;
            font-size: 13px !important;
            font-weight: 500 !important;
        }

        #reader__dashboard_section button:hover {
            background: #3182ce !important;
        }
    </style>
</head>
<body>
    <div class="scan-container">
        <div class="header">
            <h2>Pilih Jenis Absensi</h2>
            <p class="welcome-text">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?></p>
        </div>

        <!-- Action Buttons (Absen Masuk & Absen Keluar) -->
       <div class="button-container">
            <form method="POST" action="scan.php">
                <button type="submit" 
                        name="absen_masuk"
                        class="action-button absen-masuk <?php echo $disable_masuk ? 'disabled-link' : ''; ?>"
                        <?php echo $disable_masuk ? 'disabled' : ''; ?>>
                    <i class="fas fa-sign-in-alt"></i> Absen Masuk
                </button>
            </form>

            <form method="POST" action="scan_keluar.php">
                <button type="submit" 
                        name="absen_keluar"
                        class="action-button absen-keluar <?php echo !$enable_keluar ? 'disabled-link' : ''; ?>"
                        <?php echo !$enable_keluar ? 'disabled' : ''; ?>>
                    <i class="fas fa-sign-out-alt"></i> Absen Keluar
                </button>
            </form>
        </div>

        <form class="logout-form" action="logout.php" method="POST">
            <button type="submit" class="logout-button">Logout</button>
        </form>
    </div>

    <!-- Include Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
</body>
</html>

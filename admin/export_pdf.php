<?php
// Prevent any output before PDF generation
ob_start();
error_reporting(0);

session_start();
require_once '../inc/db.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Get date parameter
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Fetch all personnel
$personel_stmt = $pdo->query("SELECT * FROM personel ORDER BY nama");
$personel_list = $personel_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch attendance data
$absensi_stmt = $pdo->prepare("
    SELECT a.personel_id, a.status, a.kategori, a.keterangan, a.jam, a.keluar,
           p.nrp, p.nama, p.pangkat, p.korps, p.satker
    FROM absensi a
    JOIN personel p ON a.personel_id = p.id
    WHERE a.tanggal = ?
");
$absensi_stmt->execute([$tanggal]);
$absensi_list = $absensi_stmt->fetchAll(PDO::FETCH_ASSOC);

// Create attendance map
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

// Calculate statistics
$total_personel = count($personel_list);
$hadir = $tidak_hadir = $belum_absen = 0;

foreach ($personel_list as $p) {
    $id = $p['id'];
    $absen = isset($absensi_map[$id]) ? $absensi_map[$id] : null;
    
    if ($absen) {
        if ($absen['status'] === 'HADIR') {
            $hadir++;
        } else {
            $tidak_hadir++;
        }
    } else {
        $belum_absen++;
    }
}

// Try to load TCPDF
try {
    if (file_exists('../vendor/autoload.php')) {
        require_once '../vendor/autoload.php';
        $tcpdf_class = class_exists('TCPDF') ? 'TCPDF' : (class_exists('\TCPDF') ? '\TCPDF' : null);
    } elseif (file_exists('../vendor/tecnickcom/tcpdf/tcpdf.php')) {
        require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';
        $tcpdf_class = 'TCPDF';
    } else {
        throw new Exception('TCPDF not found');
    }

    if (!$tcpdf_class) {
        throw new Exception('TCPDF class not available');
    }

    // Clear any previous output
    ob_end_clean();

    // Create PDF instance
    $pdf = new $tcpdf_class('L', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Sistem Absensi');
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle('Rekap Absensi Harian - ' . date('d F Y', strtotime($tanggal)));
    $pdf->SetSubject('Rekapitulasi Absensi Harian');

    // Set margins and auto page break
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(true, 20);

    // Add page
    $pdf->AddPage();

    // Title
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 12, 'REKAPITULASI ABSENSI HARIAN', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 14);
    $pdf->Cell(0, 8, 'Tanggal: ' . date('d F Y', strtotime($tanggal)), 0, 1, 'C');
    $pdf->Ln(8);

    // Table header
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(25, 118, 210);
    $pdf->SetTextColor(255, 255, 255);

    $pdf->Cell(12, 10, 'No', 1, 0, 'C', true);
    $pdf->Cell(22, 10, 'NRP', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'Nama', 1, 0, 'C', true);
    $pdf->Cell(25, 10, 'Pangkat', 1, 0, 'C', true);
    $pdf->Cell(18, 10, 'Korps', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'Satker', 1, 0, 'C', true);
    $pdf->Cell(25, 10, 'Status', 1, 0, 'C', true);
    $pdf->Cell(20, 10, 'Masuk', 1, 0, 'C', true);
    $pdf->Cell(20, 10, 'Keluar', 1, 0, 'C', true);
    $pdf->Cell(50, 10, 'Keterangan', 1, 1, 'C', true);

    // Table content
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);
    $no = 1;

    foreach ($personel_list as $p) {
        $id = $p['id'];
        $absen = isset($absensi_map[$id]) ? $absensi_map[$id] : null;

        if ($absen) {
            if ($absen['status'] === 'HADIR') {
                $status = 'Hadir';
                $keterangan = '-';
                $pdf->SetFillColor(232, 245, 232); // Light green
            } else {
                $status = 'Tidak Hadir';
                $keterangan = $absen['kategori'] . ' - ' . $absen['keterangan'];
                $pdf->SetFillColor(253, 232, 232); // Light red
            }
            $jam_masuk = $absen['jam_masuk'] ?: '-';
            $jam_keluar = $absen['jam_keluar'] ?: '-';
        } else {
            $status = 'Belum Absen';
            $jam_masuk = '-';
            $jam_keluar = '-';
            $keterangan = '-';
            $pdf->SetFillColor(245, 245, 245); // Light gray
        }

        $pdf->Cell(12, 8, $no++, 1, 0, 'C', true);
        $pdf->Cell(22, 8, $p['nrp'], 1, 0, 'C', true);
        $pdf->Cell(40, 8, $p['nama'], 1, 0, 'L', true);
        $pdf->Cell(25, 8, $p['pangkat'], 1, 0, 'C', true);
        $pdf->Cell(18, 8, $p['korps'], 1, 0, 'C', true);
        $pdf->Cell(30, 8, $p['satker'], 1, 0, 'C', true);
        $pdf->Cell(25, 8, $status, 1, 0, 'C', true);
        $pdf->Cell(20, 8, $jam_masuk, 1, 0, 'C', true);
        $pdf->Cell(20, 8, $jam_keluar, 1, 0, 'C', true);
        $pdf->Cell(50, 8, $keterangan, 1, 1, 'L', true);
    }

    // Statistics section
    $pdf->Ln(10);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'STATISTIK ABSENSI', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(50, 8, 'Total Personel', 1, 0, 'L', true);
    $pdf->Cell(20, 8, $total_personel, 1, 1, 'C', true);
    
    $pdf->SetFillColor(232, 245, 232);
    $pdf->Cell(50, 8, 'Hadir', 1, 0, 'L', true);
    $pdf->Cell(20, 8, $hadir, 1, 1, 'C', true);
    
    $pdf->SetFillColor(253, 232, 232);
    $pdf->Cell(50, 8, 'Tidak Hadir', 1, 0, 'L', true);
    $pdf->Cell(20, 8, $tidak_hadir, 1, 1, 'C', true);
    
    $pdf->SetFillColor(245, 245, 245);
    $pdf->Cell(50, 8, 'Belum Absen', 1, 0, 'L', true);
    $pdf->Cell(20, 8, $belum_absen, 1, 1, 'C', true);

    // Output PDF
    $filename = 'rekap_absensi_' . date('Y-m-d', strtotime($tanggal)) . '.pdf';
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $pdf->Output($filename, 'D');

} catch (Exception $e) {
    // Fallback: redirect to simple PDF version
    ob_end_clean();
    header('Location: export_pdf_simple.php?tanggal=' . urlencode($tanggal));
}

exit;
?>
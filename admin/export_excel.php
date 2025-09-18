<?php
// Prevent any output before Excel generation
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

// Try to load PhpSpreadsheet
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
} else {
    throw new Exception('PhpSpreadsheet not found');
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

try {
    // Clear any previous output
    ob_end_clean();

    // Create new spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Rekap Absensi');

    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('Sistem Absensi')
        ->setTitle('Rekap Absensi Harian')
        ->setSubject('Rekapitulasi Absensi Harian')
        ->setDescription('Data absensi harian tanggal ' . date('d F Y', strtotime($tanggal)));

    // Main title
    $sheet->setCellValue('A1', 'REKAPITULASI ABSENSI HARIAN');
    $sheet->mergeCells('A1:J1');
    $sheet->getStyle('A1')->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 16,
            'color' => ['rgb' => 'FFFFFF']
        ],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2C3E50']]
    ]);

    // Date
    $sheet->setCellValue('A2', 'Tanggal: ' . date('d F Y', strtotime($tanggal)));
    $sheet->mergeCells('A2:J2');
    $sheet->getStyle('A2')->applyFromArray([
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'font' => ['size' => 12]
    ]);

    // Empty row
    $sheet->setCellValue('A3', '');

    // Table headers
    $headers = ['No', 'NRP', 'Nama', 'Pangkat', 'Korps', 'Satker', 'Status', 'Jam Masuk', 'Jam Keluar', 'Keterangan'];
    $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
    
    foreach ($headers as $index => $header) {
        $sheet->setCellValue($columns[$index] . '4', $header);
    }

    // Style headers
    $sheet->getStyle('A4:J4')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1976D2']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THICK]]
    ]);

    // Data rows
    $row = 5;
    $no = 1;

    foreach ($personel_list as $p) {
        $id = $p['id'];
        $absen = isset($absensi_map[$id]) ? $absensi_map[$id] : null;

        if ($absen) {
            if ($absen['status'] === 'HADIR') {
                $status = 'Hadir';
                $keterangan = '-';
                $fillColor = 'E8F5E8'; // Light green
            } else {
                $status = 'Tidak Hadir';
                $keterangan = $absen['kategori'] . ' - ' . $absen['keterangan'];
                $fillColor = 'FDE8E8'; // Light red
            }
            $jam_masuk = $absen['jam_masuk'] ?: '-';
            $jam_keluar = $absen['jam_keluar'] ?: '-';
        } else {
            $status = 'Belum Absen';
            $jam_masuk = '-';
            $jam_keluar = '-';
            $keterangan = '-';
            $fillColor = 'F5F5F5'; // Light gray
        }

        // Set cell values
        $sheet->setCellValue('A' . $row, $no++);
        $sheet->setCellValue('B' . $row, $p['nrp']);
        $sheet->setCellValue('C' . $row, $p['nama']);
        $sheet->setCellValue('D' . $row, $p['pangkat']);
        $sheet->setCellValue('E' . $row, $p['korps']);
        $sheet->setCellValue('F' . $row, $p['satker']);
        $sheet->setCellValue('G' . $row, $status);
        $sheet->setCellValue('H' . $row, $jam_masuk);
        $sheet->setCellValue('I' . $row, $jam_keluar);
        $sheet->setCellValue('J' . $row, $keterangan);

        // Style row
        $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fillColor]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
        ]);

        // Left align name and keterangan
        $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('J' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $row++;
    }

    // Statistics section
    $row += 2;
    $sheet->setCellValue('A' . $row, 'STATISTIK ABSENSI');
    $sheet->mergeCells('A' . $row . ':B' . $row);
    $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 12,
            'color' => ['rgb' => 'FFFFFF']
        ],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '34495E']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THICK]]
    ]);

    $row++;
    $stats = [
        ['Total Personel', $total_personel],
        ['Hadir', $hadir],
        ['Tidak Hadir', $tidak_hadir],
        ['Belum Absen', $belum_absen]
    ];

    foreach ($stats as $stat) {
        $sheet->setCellValue('A' . $row, $stat[0]);
        $sheet->setCellValue('B' . $row, $stat[1]);
        
        $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']]
        ]);
        
        $row++;
    }

    // Auto-size columns
    foreach ($columns as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    // Set minimum column widths
    $sheet->getColumnDimension('C')->setWidth(25); // Nama
    $sheet->getColumnDimension('J')->setWidth(30); // Keterangan

    // Set row heights
    $sheet->getDefaultRowDimension()->setRowHeight(18);
    $sheet->getRowDimension('1')->setRowHeight(25);
    $sheet->getRowDimension('2')->setRowHeight(20);

    // Generate filename
    $filename = 'rekap_absensi_' . date('Y-m-d', strtotime($tanggal)) . '.xlsx';

    // Set headers for Excel download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Create writer and output
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

} catch (Exception $e) {
    // Fallback: redirect to CSV export
    ob_end_clean();
    header('Location: export_csv.php?tanggal=' . urlencode($tanggal));
}

exit;
?>
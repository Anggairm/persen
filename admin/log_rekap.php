<?php
session_start();
require_once '../inc/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../login.php');
    exit;
}

$role = $_SESSION['role'];
$satker = $_SESSION['satker'];

// Ambil semua absensi join personel
$query = "
    SELECT a.personel_id, a.tanggal, a.status, a.kategori, a.keterangan, a.jam AS jam_masuk, a.keluar AS jam_keluar,
           p.id, p.nrp, p.nama, p.pangkat, p.korps, p.satker
    FROM personel p
    LEFT JOIN absensi a ON a.personel_id = p.id AND a.tanggal = ?
";
$params = [date('Y-m-d')]; // default ambil hari ini

if ($role === 'admin') {
    $query .= " WHERE p.satker = ?";
    $params[] = $satker;
}
$query .= " ORDER BY a.tanggal DESC, p.nama ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$personel_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// mapping absensi by personel_id
$absensi_map = [];
foreach ($personel_list as $row) {
    if (!empty($row['status'])) {
        $absensi_map[$row['personel_id']] = $row;
    }
}

// Ambil opsi satker untuk filter
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
$satkerOptions = $role === 'superadmin' ? $allSatkerOptions : [$satker];
?>
<!DOCTYPE html>
<html>

<head>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <link rel="icon" type="image/png" href="../assets/css/logo.png">
    <meta charset="UTF-8">
    <title>Log Rekap Harian</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            line-height: 1.6;
        }

        /* Container dan layout */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }

        /* Header controls */
        .header-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
        }

        .header-controls>div {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Filter dan Search */
        .filter-search-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 150px;
        }

        .filter-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #495057;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: end;
        }

        /* Stats info */
        .stats-info {
            background: #e7f3ff;
            border: 1px solid #b8daff;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 20px;
            color: #004085;
            font-size: 14px;
        }

        /* Modal styling - DIPERBAIKI */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .overlay.active {
            display: flex !important;
        }

        .modal {
            background: #fff;
            width: 500px;
            max-width: 90vw;
            max-height: 90vh;
            overflow-y: auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            background: none;
            border: none;
            font-size: 24px;
            color: #6c757d;
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background-color: #f8f9fa;
            color: #495057;
        }

        .modal h2 {
            margin: 0 0 25px 0;
            color: #343a40;
            font-size: 24px;
            font-weight: 600;
        }

        .modal form {
            display: grid;
            gap: 15px;
        }

        .modal label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 5px;
            display: block;
        }

        .modal input,
        .modal select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s ease;
            box-sizing: border-box;
        }

        .modal input:focus,
        .modal select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        /* Button styling */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            min-width: 80px;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background-color: #e0a800;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid #ced4da;
            color: #495057;
        }

        .btn-outline:hover {
            background-color: #f8f9fa;
            border-color: #adb5bd;
        }

        /* Table styling */
        .table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            padding: 15px 12px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .action-buttons .btn {
            padding: 6px 12px;
            font-size: 12px;
            min-width: 60px;
        }

        /* Import form styling */
        .import-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .import-form input[type="file"] {
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            background: white;
        }

        /* Badge styling */
        .badge {
            display: inline-block;
            padding: 4px 8px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 12px;
            text-transform: uppercase;
        }

        .badge-primary {
            background-color: #007bff;
            color: white;
        }

        .badge-danger {
            background-color: #dc3545;
            color: white;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }


        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: #495057;
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
            color: gray;
            font-style: italic;
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

        th {
            position: relative;
            cursor: pointer;
            white-space: nowrap;
            /* cegah teks turun ke bawah */
            padding-right: 20px;
            /* beri ruang untuk panah */
        }

        th.sorted-asc::after,
        th.sorted-desc::after {
            content: "";
            position: absolute;
            right: 6px;
            /* letakkan di ujung kanan */
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.8em;
            pointer-events: none;
        }

        th.sorted-asc::after {
            content: "▲";
        }

        th.sorted-desc::after {
            content: "▼";
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .header-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-row {
                flex-direction: column;
            }

            .filter-group {
                min-width: 100%;
            }

            .modal {
                width: 95vw;
                padding: 20px;
                margin: 10px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }

            .action-buttons .btn {
                width: 100%;
            }

            table {
                font-size: 12px;
            }

            th,
            td {
                padding: 8px 6px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Log Rekap Harian</h1>

        <!-- Filter -->
        <div class="filter-search-container">
            <div class="filter-row">
                <div class="filter-actions">
                    <button class="btn btn-primary" onclick="window.history.back()">Kembali</button>
                </div>

                <div class="filter-group">
                    <label>Tanggal</label>
                    <!-- kasih value default hari ini -->
                    <input type="date" id="filterTanggal" value="<?= date('Y-m-d') ?>">
                </div>

                <div class="filter-group">
                    <label>Satker</label>
                    <select id="filterSatker">
                        <option value="">Semua</option>
                        <?php foreach ($satkerOptions as $s): ?>
                            <option value="<?= $s ?>"><?= $s ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Status</label>
                    <select id="filterStatus">
                        <option value="">Semua</option>
                        <option value="HADIR">Hadir</option>
                        <option value="IZIN">Izin</option>
                        <option value="SAKIT">Sakit</option>
                        <option value="TANPA KETERANGAN">Tanpa Keterangan</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button class="btn btn-secondary" onclick="clearAllFilters()">Reset Filter</button>
                    <button onclick="exportTableToExcel('rekapTable', 'rekap_absensi')" class="btn btn-success">Export
                        Excel</button>
                    <button onclick="exportTableToPDF()" class="btn btn-danger">Export PDF</button>
                </div>

            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table id="rekapTable" class="rekap-table">
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
                <tbody id="rekapTableBody">
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
                            $jamMasuk = $absen['jam_masuk'] ?? '';
                            $jamKeluar = $absen['jam_keluar'] ?? '';
                        } else {
                            $status = '<span class="status-kosong">-</span>';
                            $keterangan = '<span class="status-kosong">-</span>';
                            $jamMasuk = '';
                            $jamKeluar = '';
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
                            <td><?= $jamMasuk ?></td>
                            <td><?= $jamKeluar ?></td>
                            <td><?= $keterangan ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>
            <div id="emptyState" class="empty-state" style="display:none;">
                <h3>Tidak ada data</h3>
            </div>
        </div>
    </div>

    <script>
        let originalData = <?= json_encode($personel_list) ?>;
        let filteredData = [...originalData];

        async function applyFilters() {
            const tgl = document.getElementById('filterTanggal').value;
            const satker = document.getElementById('filterSatker').value;
            const status = document.getElementById('filterStatus').value;

            const params = new URLSearchParams({ tanggal: tgl, satker, status });
            const res = await fetch('rekap_api.php?' + params.toString());
            const data = await res.json();

            filteredData = data;
            renderTable();
        }


        function renderTable() {
            const tbody = document.getElementById('rekapTableBody');
            const emptyState = document.getElementById('emptyState');
            tbody.innerHTML = '';

            if (filteredData.length === 0) {
                emptyState.style.display = 'block';
                return;
            }
            emptyState.style.display = 'none';

            filteredData.forEach((r, i) => {
                let statusText, ketText, jamMasuk, jamKeluar;
                if (r.status === 'HADIR') {
                    statusText = '<span class="status-hadir">Hadir</span>';
                    ketText = '-';
                } else if (r.status) {
                    statusText = '<span class="status-tidak">Tidak Hadir</span>';
                    ketText = `${r.kategori ?? ''} - ${r.keterangan ?? ''}`;
                } else {
                    statusText = '<span class="status-kosong">-</span>';
                    ketText = '<span class="status-kosong">-</span>';
                }
                jamMasuk = r.jam_masuk ?? '';
                jamKeluar = r.jam_keluar ?? '';

                tbody.innerHTML += `
          <tr>
            <td>${i + 1}</td>
            <td>${r.nrp}</td>
            <td>${r.nama}</td>
            <td>${r.pangkat}</td>
            <td>${r.korps}</td>
            <td>${r.satker}</td>
            <td>${statusText}</td>
            <td>${jamMasuk}</td>
            <td>${jamKeluar}</td>
            <td>${ketText}</td>
          </tr>
        `;
            });
        }

        // Export ke Excel
        function exportTableToExcel(filename = '') {
            if (filteredData.length === 0) {
                alert("Tidak ada data untuk diexport!");
                return;
            }
            const ws = XLSX.utils.json_to_sheet(filteredData.map((r, i) => ({
                No: i + 1,
                NRP: r.nrp,
                Nama: r.nama,
                Pangkat: r.pangkat,
                Korps: r.korps,
                Satker: r.satker,
                Absen: r.status ?? "-",
                Masuk: r.jam_masuk ?? "",
                Keluar: r.jam_keluar ?? "",
                Keterangan: r.keterangan ?? "-"
            })));

            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Rekap");
            XLSX.writeFile(wb, (filename || 'rekap_absensi') + "_" + document.getElementById('filterTanggal').value + ".xlsx");
        }


        // Export ke PDF
        function exportTableToPDF() {
            if (filteredData.length === 0) {
                alert("Tidak ada data untuk diexport!");
                return;
            }

            const { jsPDF } = window.jspdf;
            var doc = new jsPDF('l', 'pt', 'a4');
            const tgl = document.getElementById('filterTanggal').value;

            doc.text("Rekapitulasi Absensi Harian - " + tgl, 40, 40);

            const body = filteredData.map((r, i) => [
                i + 1,
                r.nrp,
                r.nama,
                r.pangkat,
                r.korps,
                r.satker,
                r.status ?? "-",
                r.jam_masuk ?? "",
                r.jam_keluar ?? "",
                r.keterangan ?? "-"
            ]);

            doc.autoTable({
                head: [["No", "NRP", "Nama", "Pangkat", "Korps", "Satker", "Absen", "Masuk", "Keluar", "Keterangan"]],
                body: body,
                startY: 60,
                styles: { fontSize: 8 }
            });

            doc.save("rekap_absensi_" + tgl + ".pdf");
        }


        function clearAllFilters() {
            // kembalikan tanggal ke hari ini
            document.getElementById('filterTanggal').value = '<?= date('Y-m-d') ?>';

            // kosongkan filter satker & status
            document.getElementById('filterSatker').value = '';
            document.getElementById('filterStatus').value = '';

            // jalankan filter ulang
            applyFilters();
        }


        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('filterTanggal').addEventListener('change', applyFilters);
            document.getElementById('filterSatker').addEventListener('change', applyFilters);
            document.getElementById('filterStatus').addEventListener('change', applyFilters);

            // langsung filter berdasarkan tanggal hari ini
            applyFilters();
        });

        document.addEventListener('DOMContentLoaded', function () {
            const table = document.getElementById('rekapTable');
            const headers = table.querySelectorAll('th');
            let sortDirection = {};

            const pangkatOrder = [
                'MARSEKAL', 'MARSDYA', 'MARSDA', 'MARSMA',
                'KOLONEL', 'LETKOL', 'MAYOR', 'KAPTEN', 'LETTU', 'LETDA',
                'PELTU', 'PELDA', 'SERMA', 'SERKA', 'SERTU', 'SERDA',
                'KOPKA', 'KOPTU', 'KOPDA', 'PRAKA', 'PRATU', 'PRADA', 'PNS'
            ];

            headers.forEach((header, index) => {
                if (header.textContent.trim() === 'Aksi') return;

                header.style.cursor = 'pointer';
                header.addEventListener('click', function () {
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));

                    const isAsc = !(sortDirection[index] === 'asc');
                    sortDirection[index] = isAsc ? 'asc' : 'desc';

                    rows.sort((a, b) => {
                        let aText = a.children[index].textContent.trim().toUpperCase();
                        let bText = b.children[index].textContent.trim().toUpperCase();

                        // --- 🔥 urutan khusus untuk kolom PANGKAT ---
                        if (header.textContent.trim() === 'Pangkat') {
                            const aIndex = pangkatOrder.indexOf(aText);
                            const bIndex = pangkatOrder.indexOf(bText);
                            return isAsc ? aIndex - bIndex : bIndex - aIndex;
                        }

                        // --- default: cek apakah angka ---
                        const aNum = parseFloat(aText.replace(/[^0-9.-]+/g, ""));
                        const bNum = parseFloat(bText.replace(/[^0-9.-]+/g, ""));
                        const isNumber = !isNaN(aNum) && !isNaN(bNum);

                        if (isNumber) {
                            return isAsc ? aNum - bNum : bNum - aNum;
                        } else {
                            return isAsc
                                ? aText.localeCompare(bText)
                                : bText.localeCompare(aText);
                        }
                    });

                    tbody.innerHTML = '';
                    rows.forEach(r => tbody.appendChild(r));

                    headers.forEach(h => h.classList.remove('sorted-asc', 'sorted-desc'));
                    header.classList.add(isAsc ? 'sorted-asc' : 'sorted-desc');
                });
            });
        });

    </script>
</body>

</html>
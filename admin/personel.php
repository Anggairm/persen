<?php
session_start();
require_once '../inc/db.php';
require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

// Cek role admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../login.php');
    exit;
}

$tanggal = date('Y-m-d');
$role = $_SESSION['role'];
$satker = $_SESSION['satker'];

$filterWhere = "";
$filterJoin = "";
$params = [];

// Tambah personel
if (isset($_POST['aksi']) && $_POST['aksi'] == 'tambah') {
    $stmt = $pdo->prepare("INSERT INTO personel (nrp, nama, pangkat, korps, jabatan, satker, password, role)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['nrp'],
        $_POST['nama'],
        $_POST['pangkat'],
        $_POST['korps'],
        $_POST['jabatan'],
        $_POST['satker'],
        password_hash($_POST['password'], PASSWORD_DEFAULT),
        $_POST['role'] ?? 'user'
    ]);
    header("Location: personel.php");
    exit;
}

//Kembali
if (isset($_POST['aksi']) && $_POST['aksi'] == 'kembali') {
    header("Location: dashboard.php");
    exit;
}

// Edit personel
if (isset($_POST['aksi']) && $_POST['aksi'] == 'edit') {
    $id = $_POST['id'];
    $sql = "UPDATE personel SET nrp=?, nama=?, pangkat=?, korps=?, jabatan=?, satker=?, role=?";
    $params = [$_POST['nrp'], $_POST['nama'], $_POST['pangkat'], $_POST['korps'], $_POST['jabatan'], $_POST['satker'], $_POST['role']];

    if (!empty($_POST['password'])) {
        $sql .= ", password=?";
        $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }

    $sql .= " WHERE id=?";
    $params[] = $id;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    header("Location: personel.php");
    exit;
}

// Hapus personel
if (isset($_GET['hapus'])) {
    $stmt = $pdo->prepare("DELETE FROM personel WHERE id=?");
    $stmt->execute([$_GET['hapus']]);
    header("Location: personel.php");
    exit;
}

// Impor dari Excel
if (isset($_POST['aksi']) && $_POST['aksi'] == 'impor' && isset($_FILES['file_excel'])) {
    $file = $_FILES['file_excel']['tmp_name'];
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    foreach ($rows as $i => $row) {
        if ($i == 0)
            continue; // skip header
        [$nrp, $nama, $pangkat, $korps, $jabatan, $satker, $password, $role] = $row;
        $stmt = $pdo->prepare("INSERT INTO personel (nrp, nama, pangkat, korps, jabatan, satker, password, role)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $nrp,
            $nama,
            $pangkat,
            $korps,
            $jabatan,
            $satker,
            password_hash($password, PASSWORD_DEFAULT),
            $role ?? 'user'
        ]);
    }
    header("Location: personel.php");
    exit;
}

// Ambil semua personel
$query = "SELECT * FROM personel";
$params = [];

if ($role === 'admin') {
    $query .= " WHERE satker = ?";
    $params[] = $satker;
}

$query .= " ORDER BY nama ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$personel = $stmt->fetchAll(PDO::FETCH_ASSOC);
$pangkatOptions = [
    'MARSEKAL',
    'MARSDYA',
    'MARSDA',
    'MARSMA',
    'KOLONEL',
    'LETKOL',
    'MAYOR',
    'KAPTEN',
    'LETTU',
    'LETDA',
    'PELTU',
    'PELDA',
    'SERMA',
    'SERKA',
    'SERTU',
    'SERDA',
    'KOPKA',
    'KOPTU',
    'KOPDA',
    'PRAKA',
    'PRATU',
    'PRADA'
];

$korpsOptions = [
    'PNB',
    'NAV',
    'TEK',
    'LEK',
    'KAL',
    'ADM',
    'PAS',
    'POM',
    'KES',
    'SUS',
    'KUM'
];

// Semua opsi satker
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

$allRoleOptions = ['user', 'admin', 'superadmin'];

// Filter sesuai role
if ($role === 'superadmin') {
    $satkerOptions = $allSatkerOptions;
    $roleOptions = $allRoleOptions;
} else {
    $satkerOptions = [$satker]; // hanya satkernya sendiri
    $roleOptions = ['user', 'admin']; // tanpa superadmin
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Manajemen Personel</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/css/logo.png">
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
        <h1>Manajemen Personel</h1>

        <!-- Stats info -->
        <div class="stats-info">
            Total Personel: <strong id="totalPersonel"><?= count($personel) ?></strong> |
            Ditampilkan: <strong id="displayedCount"><?= count($personel) ?></strong>
        </div>

        <!-- Header controls -->
        <div class="header-controls">
            <div>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="aksi" value="kembali">
                    <button type="submit" class="btn btn-secondary">Kembali</button>
                </form>
            </div>

            <div>
                <button class="btn btn-success" onclick="bukaModalTambah()">+ Tambah Personel</button>
            </div>

            <div class="import-form">
                <form method="post" enctype="multipart/form-data"
                    style="display: flex; gap: 10px; align-items: center;">
                    <input type="hidden" name="aksi" value="impor">
                    <input type="file" name="file_excel" accept=".xlsx,.xls" required>
                    <button class="btn btn-warning" type="submit">Import Excel</button>
                </form>
            </div>
        </div>

        <!-- Filter dan Search -->
        <div class="filter-search-container">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Filter :</label>
                    <input type="text" id="searchInput" placeholder="Ketik nama atau NRP...">
                </div>

                <div class="filter-group">
                    <label></label>
                    <select id="filterPangkat">
                        <option value="">Semua Pangkat</option>
                        <?php foreach ($pangkatOptions as $p): ?>
                            <option value="<?= $p ?>"><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label></label>
                    <select id="filterKorps">
                        <option value="">Semua Korps</option>
                        <?php foreach ($korpsOptions as $k): ?>
                            <option value="<?= $k ?>"><?= $k ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label></label>
                    <select id="filterSatker">
                        <option value="">Semua Satker</option>
                        <?php foreach ($satkerOptions as $s): ?>
                            <option value="<?= $s ?>"><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label></label>
                    <select id="filterRole">
                        <option value="">Semua Role</option>
                        <?php foreach ($roleOptions as $r): ?>
                            <option value="<?= $r ?>"><?= $r ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-actions">
                    <button class="btn btn-outline" onclick="clearAllFilters()">Reset Filter</button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table id="personelTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>NRP</th>
                        <th>Nama</th>
                        <th>Pangkat</th>
                        <th>Korps</th>
                        <th>Jabatan</th>
                        <th>Satker</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="personelTableBody">
                    <?php foreach ($personel as $index => $p): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($p['nrp']) ?></td>
                            <td><?= htmlspecialchars($p['nama']) ?></td>
                            <td><?= htmlspecialchars($p['pangkat']) ?></td>
                            <td><?= htmlspecialchars($p['korps']) ?></td>
                            <td><?= htmlspecialchars($p['jabatan']) ?></td>
                            <td><?= htmlspecialchars($p['satker']) ?></td>
                            <td>
                                <span class="badge badge-<?=
                                    $p['role'] === 'superadmin' ? 'warning' :
                                    ($p['role'] === 'admin' ? 'danger' : 'primary')
                                    ?>">
                                    <?= htmlspecialchars($p['role']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-warning" onclick='bukaModalEdit(<?= json_encode($p) ?>)'>✏️
                                        Edit</button>
                                    <a href="?hapus=<?= $p['id'] ?>"
                                        onclick="return confirm('Apakah Anda yakin ingin menghapus personel ini?\n\nNama: <?= htmlspecialchars($p['nama']) ?>\nNRP: <?= htmlspecialchars($p['nrp']) ?>\n\nData yang dihapus tidak dapat dikembalikan!')"
                                        class="btn btn-danger">Hapus</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>

            <!-- Empty state -->
            <div id="emptyState" class="empty-state" style="display: none;">
                <h3>Tidak ada data yang ditemukan</h3>
                <p>Coba ubah kriteria pencarian atau filter Anda</p>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="overlay" id="overlay">
        <div class="modal" id="modalForm">
            <button class="modal-close" onclick="tutupModal()">&times;</button>
            <h2 id="modalTitle">Tambah Personel</h2>
            <form method="POST">
                <input type="hidden" name="aksi" id="aksiInput" value="tambah">
                <input type="hidden" name="id" id="idInput">

                <label for="nrpInput">NRP:</label>
                <input type="number" name="nrp" id="nrpInput" required>

                <label for="namaInput">Nama Lengkap:</label>
                <input type="text" name="nama" id="namaInput" required>

                <label for="pangkatInput">Pangkat:</label>
                <select name="pangkat" id="pangkatInput" required>
                    <?php foreach ($pangkatOptions as $p): ?>
                        <option value="<?= $p ?>"><?= $p ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="korpsInput">Korps:</label>
                <select name="korps" id="korpsInput" required>
                    <?php foreach ($korpsOptions as $k): ?>
                        <option value="<?= $k ?>"><?= $k ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="jabatanInput">Jabatan:</label>
                <input type="text" name="jabatan" id="jabatanInput">

                <label for="satkerInput">Satker:</label>
                <select name="satker" id="satkerInput" required>
                    <?php foreach ($satkerOptions as $s): ?>
                        <option value="<?= $s ?>"><?= $s ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="passwordInput">Password:</label>
                <input type="password" name="password" id="passwordInput" placeholder="Masukkan password">

                <label for="roleInput">Role:</label>
                <select name="role" id="roleInput" required>
                    <?php foreach ($roleOptions as $r): ?>
                        <option value="<?= $r ?>"><?= $r ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="modal-actions">
                    <button type="submit" class="btn btn-success">Simpan</button>
                    <button type="button" class="btn btn-secondary" onclick="tutupModal()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Global variables
        let originalData = <?= json_encode($personel) ?>;
        let filteredData = [...originalData];

        console.log('JavaScript loading...');
        console.log('Data personel:', originalData.length, 'records');

        // MODAL FUNCTIONS - VERSI YANG SUDAH DIPERBAIKI
        function bukaModalTambah() {
            console.log('bukaModalTambah dipanggil');

            document.getElementById('modalTitle').innerHTML = "Tambah Personel Baru";
            document.getElementById('aksiInput').value = "tambah";
            document.getElementById('idInput').value = "";

            // Clear all inputs
            document.getElementById('nrpInput').value = "";
            document.getElementById('namaInput').value = "";
            document.getElementById('jabatanInput').value = "";
            document.getElementById('passwordInput').value = "";

            // Reset selects
            document.getElementById('pangkatInput').selectedIndex = 0;
            document.getElementById('korpsInput').selectedIndex = 0;
            document.getElementById('satkerInput').selectedIndex = 0;
            document.getElementById('roleInput').value = "user";

            // Set password required
            document.getElementById('passwordInput').required = true;
            document.getElementById('passwordInput').placeholder = "Masukkan password";

            bukaModal();
        }

        function bukaModalEdit(data) {
            console.log('bukaModalEdit dipanggil dengan data:', data);

            document.getElementById('modalTitle').innerHTML = "Edit Personel";
            document.getElementById('aksiInput').value = "edit";
            document.getElementById('idInput').value = data.id;
            document.getElementById('nrpInput').value = data.nrp;
            document.getElementById('namaInput').value = data.nama;
            document.getElementById('pangkatInput').value = data.pangkat;
            document.getElementById('korpsInput').value = data.korps;
            document.getElementById('jabatanInput').value = data.jabatan;
            document.getElementById('satkerInput').value = data.satker;
            document.getElementById('passwordInput').value = "";
            document.getElementById('roleInput').value = data.role;

            // Password not required for edit
            document.getElementById('passwordInput').required = false;
            document.getElementById('passwordInput').placeholder = "Kosongkan jika tidak ingin mengubah password";

            bukaModal();
        }

        function bukaModal() {
            console.log('bukaModal dipanggil');

            const overlay = document.getElementById('overlay');
            const modal = document.getElementById('modalForm');

            if (!overlay) {
                console.error('Overlay tidak ditemukan!');
                alert('Error: Overlay tidak ditemukan!');
                return;
            }

            if (!modal) {
                console.error('Modal tidak ditemukan!');
                alert('Error: Modal tidak ditemukan!');
                return;
            }

            // Show modal
            document.body.style.overflow = 'hidden';
            overlay.style.display = 'flex';
            overlay.classList.add('active');

            // Focus first input
            setTimeout(function () {
                const firstInput = document.getElementById('nrpInput');
                if (firstInput) {
                    firstInput.focus();
                }
            }, 200);

            console.log('Modal seharusnya sudah terbuka');
        }

        function tutupModal() {
            console.log('tutupModal dipanggil');

            const overlay = document.getElementById('overlay');

            if (overlay) {
                overlay.classList.remove('active');
                overlay.style.display = 'none';
                document.body.style.overflow = '';
            }

            console.log('Modal seharusnya sudah tertutup');
        }

        // FILTER FUNCTIONS
        function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
            const filterPangkat = document.getElementById('filterPangkat').value;
            const filterKorps = document.getElementById('filterKorps').value;
            const filterSatker = document.getElementById('filterSatker').value;
            const filterRole = document.getElementById('filterRole').value;

            filteredData = originalData.filter(function (person) {
                const matchSearch = !searchTerm ||
                    person.nama.toLowerCase().includes(searchTerm) ||
                    person.nrp.toLowerCase().includes(searchTerm) ||
                    person.jabatan.toLowerCase().includes(searchTerm);

                const matchPangkat = !filterPangkat || person.pangkat === filterPangkat;
                const matchKorps = !filterKorps || person.korps === filterKorps;
                const matchSatker = !filterSatker || person.satker === filterSatker;
                const matchRole = !filterRole || person.role === filterRole;

                return matchSearch && matchPangkat && matchKorps && matchSatker && matchRole;
            });

            renderTable();
            updateStats();
        }

        function renderTable() {
            const tbody = document.getElementById('personelTableBody');
            const emptyState = document.getElementById('emptyState');

            if (filteredData.length === 0) {
                tbody.innerHTML = '';
                emptyState.style.display = 'block';
                return;
            }

            emptyState.style.display = 'none';

            let tableHTML = '';
            for (let index = 0; index < filteredData.length; index++) {
                const person = filteredData[index];
                let roleClass;

                if (person.role === 'superadmin') {
                    roleClass = 'success'; // hijau
                } else if (person.role === 'admin') {
                    roleClass = 'danger'; // merah
                } else {
                    roleClass = 'primary'; // biru
                }

                const personJSON = JSON.stringify(person).replace(/"/g, '&quot;');

                tableHTML += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${person.nrp}</td>
                        <td>${person.nama}</td>
                        <td>${person.pangkat}</td>
                        <td>${person.korps}</td>
                        <td>${person.jabatan}</td>
                        <td>${person.satker}</td>
                        <td><span class="badge badge-${roleClass}">${person.role}</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-warning" onclick='bukaModalEdit(${personJSON})'>Edit</button>
                                <a href="?hapus=${person.id}" 
                                onclick="return confirm('Apakah Anda yakin ingin menghapus personel ini?\\n\\nNama: ${person.nama}\\nNRP: ${person.nrp}\\n\\nData yang dihapus tidak dapat dikembalikan!')" 
                                class="btn btn-danger">Hapus</a>
                            </div>
                        </td>
                    </tr>
                `;
            }


            tbody.innerHTML = tableHTML;
        }

        function updateStats() {
            document.getElementById('totalPersonel').textContent = originalData.length;
            document.getElementById('displayedCount').textContent = filteredData.length;
        }

        function clearAllFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('filterPangkat').value = '';
            document.getElementById('filterKorps').value = '';
            document.getElementById('filterSatker').value = '';
            document.getElementById('filterRole').value = '';
            applyFilters();
        }

        // INITIALIZATION - KETIKA HALAMAN LOADED
        document.addEventListener('DOMContentLoaded', function () {
            console.log('DOM Content Loaded');

            // Initialize table
            renderTable();
            updateStats();

            // Search with delay
            let searchTimeout;
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(applyFilters, 300);
                });
            }

            // Filter dropdowns
            const filterElements = ['filterPangkat', 'filterKorps', 'filterSatker', 'filterRole'];
            filterElements.forEach(function (id) {
                const element = document.getElementById(id);
                if (element) {
                    element.addEventListener('change', applyFilters);
                }
            });

            // Modal event listeners
            const overlay = document.getElementById('overlay');
            const modalForm = document.getElementById('modalForm');

            if (overlay) {
                // Close modal on overlay click
                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay) {
                        tutupModal();
                    }
                });
            }

            if (modalForm) {
                // Prevent modal content click from closing
                modalForm.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            }

            // Close modal on ESC key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    const overlayElement = document.getElementById('overlay');
                    if (overlayElement && overlayElement.classList.contains('active')) {
                        tutupModal();
                    }
                }
            });

            console.log('Semua event listener sudah di-setup');
            console.log('Modal elements check:');
            console.log('   - Overlay:', overlay ? '✅' : '❌');
            console.log('   - Modal Form:', modalForm ? '✅' : '❌');
        });

        // FORM VALIDATION
        function validateForm(form) {
            const requiredFields = [
                { id: 'nrpInput', name: 'NRP' },
                { id: 'namaInput', name: 'Nama' },
                { id: 'pangkatInput', name: 'Pangkat' },
                { id: 'korpsInput', name: 'Korps' },
                { id: 'satkerInput', name: 'Satker' },
                { id: 'roleInput', name: 'Role' }
            ];

            let isValid = true;
            let errors = [];

            // Check required fields
            requiredFields.forEach(function (field) {
                const element = document.getElementById(field.id);
                if (element && !element.value.trim()) {
                    isValid = false;
                    errors.push(field.name + ' tidak boleh kosong');
                    element.style.borderColor = '#dc3545';
                } else if (element) {
                    element.style.borderColor = '#ced4da';
                }
            });

            // Check password for new personel
            const aksi = document.getElementById('aksiInput').value;
            const passwordInput = document.getElementById('passwordInput');

            if (aksi === 'tambah' && passwordInput && !passwordInput.value.trim()) {
                isValid = false;
                errors.push('Password tidak boleh kosong untuk personel baru');
                passwordInput.style.borderColor = '#dc3545';
            } else if (passwordInput) {
                passwordInput.style.borderColor = '#ced4da';
            }

            // Check NRP uniqueness
            const nrpInput = document.getElementById('nrpInput');
            const idInput = document.getElementById('idInput');

            if (nrpInput && nrpInput.value.trim()) {
                const currentNrp = nrpInput.value.trim();
                const currentId = idInput ? idInput.value : '';

                const isDuplicate = originalData.some(function (person) {
                    return person.nrp === currentNrp && person.id != currentId;
                });

                if (isDuplicate) {
                    isValid = false;
                    errors.push('NRP sudah digunakan oleh personel lain');
                    nrpInput.style.borderColor = '#dc3545';
                }
            }

            if (!isValid) {
                alert('Terdapat kesalahan:\n\n' + errors.join('\n'));
                return false;
            }

            return true;
        }

        // Override form submission
        document.addEventListener('DOMContentLoaded', function () {
            const modalForm = document.querySelector('#modalForm form');
            if (modalForm) {
                modalForm.addEventListener('submit', function (e) {
                    console.log('Form submission validation');

                    if (!validateForm(this)) {
                        e.preventDefault();
                        return false;
                    }

                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = 'Menyimpan...';
                        submitBtn.disabled = true;

                        // Restore button if form doesn't submit (shouldn't happen in normal flow)
                        setTimeout(function () {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }, 5000);
                    }

                    console.log('✅ Form validation passed, submitting...');
                    return true;
                });
            }
        });

        // ERROR HANDLING
        window.onerror = function (msg, url, line, col, error) {
            console.error('JavaScript Error:', msg, 'at line', line);
            console.error('URL:', url);
            console.error('Error object:', error);
            return false;
        };

        // Test function untuk debugging
        function testModal() {
            console.log('Testing modal...');
            bukaModalTambah();
        }

        console.log('JavaScript loaded completely!');
        console.log('Available functions:', {
            bukaModalTambah: typeof bukaModalTambah,
            bukaModalEdit: typeof bukaModalEdit,
            tutupModal: typeof tutupModal,
            applyFilters: typeof applyFilters,
            testModal: typeof testModal
        });
    </script>
</body>

</html>
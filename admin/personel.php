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
    'LEK',
    'ADM',
    'TEK',
    'TNI'
];

$satkerOptions = [
    'DISINFOLAHTAAU',
    'PUSTASISINFO',
    'SUBDISSIDUKOPS',
    'SUBDISSIDUKPERS',
    'SUKDISSIDUKLOG',
    'SUBDISDUKSISMIN',
    'BAGUM',
    'PROGAR',
    'SISPRI',
    'LATKER'
];
?>

<!DOCTYPE html>
<html>

<head>
    <title>Manajemen Personel</title>
    <link rel="stylesheet" href="../assets/css/admin_personel.css">
    <style>
        .overlay,
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 999;
        }

        .overlay.active {
            display: block;
            background: rgba(0, 0, 0, 0.4);
        }

        .modal.active {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }

        .modal {
            background: #fff;
            width: 400px;
            max-width: 90%;
            padding: 25px 30px;
            margin: 80px auto;
            border-radius: 10px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            position: relative;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal h2 {
            margin-top: 0;
            margin-bottom: 20px;
        }

        .modal label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .modal input,
        .modal select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 14px;
        }

        .modal button {
            width: 48%;
            margin-right: 2%;
        }

        .modal button:last-child {
            margin-right: 0;
        }

        @media (max-width: 500px) {
            .modal {
                width: 90%;
                margin: 50px auto;
            }

            .modal button {
                width: 100%;
                margin: 5px 0;
            }
        }

        th,
        td {
            padding: 8px;
            border: 1px solid #ccc;
            text-align: left;
        }

        .btn {
            padding: 6px 12px;
            margin: 2px;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-edit {
            background: #ffc107;
            color: black;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Manajemen Personel</h1>
        <form method="post" style="display:inline;">
            <input type="hidden" name="aksi" value="kembali">
            <button type="submit" class="btn btn-edit">Kembali</button>
        </form>

        <button class="btn btn-success" onclick="bukaModalTambah()">+ Tambah Personel</button>
        <form method="post" enctype="multipart/form-data" style="margin-top: 10px;">
            <input type="hidden" name="aksi" value="impor">
            <input type="file" name="file_excel" required>
            <button class="btn btn-edit" type="submit">Import Excel</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>NRP</th>
                    <th>Nama</th>
                    <th>Pangkat</th>
                    <th>Korps</th>
                    <th>Jabatan</th>
                    <th>Sub Dinas/Bagian</th>
                    <th>Role</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($personel as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nrp']) ?></td>
                        <td><?= htmlspecialchars($p['nama']) ?></td>
                        <td><?= htmlspecialchars($p['pangkat']) ?></td>
                        <td><?= htmlspecialchars($p['korps']) ?></td>
                        <td><?= htmlspecialchars($p['jabatan']) ?></td>
                        <td><?= htmlspecialchars($p['satker']) ?></td>
                        <td><?= htmlspecialchars($p['role']) ?></td>
                        <td>
                            <button class="btn btn-edit" onclick='bukaModalEdit(<?= json_encode($p) ?>)'>Edit</button>
                            <a href="?hapus=<?= $p['id'] ?>" onclick="return confirm('Hapus personel ini?')"
                                class="btn btn-danger">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>

    <!-- Overlay dan Modal -->
    <div class="overlay" id="overlay"></div>
    <div class="modal" id="modalForm">
        <span style="position:absolute; top:10px; right:15px; cursor:pointer; font-size:20px; color:#999;"
            onclick="tutupModal()">&times;</span>
        <h2 id="modalTitle">Tambah Personel</h2>
        <form method="POST">
            <input type="hidden" name="aksi" id="aksiInput" value="tambah">
            <input type="hidden" name="id" id="idInput">
            <label>NRP:</label><input type="text" name="nrp" id="nrpInput" required><br>
            <label>Nama:</label><input type="text" name="nama" id="namaInput" required><br>
            <label>Pangkat:</label>
            <select name="pangkat" id="pangkatInput">
                <?php foreach ($pangkatOptions as $p): ?>
                    <option value="<?= $p ?>"><?= $p ?></option>
                <?php endforeach; ?>
            </select><br>
            <label>Korps:</label>
            <select name="korps" id="korpsInput">
                <?php foreach ($korpsOptions as $k): ?>
                    <option value="<?= $k ?>"><?= $k ?></option>
                <?php endforeach; ?>
            </select><br>
            <label>Jabatan:</label><input type="text" name="jabatan" id="jabatanInput"><br>
            <label>Sub Dinas/Bagian:</label>
            <select name="satker" id="satkerInput">
                <?php foreach ($satkerOptions as $s): ?>
                    <option value="<?= $s ?>"><?= $s ?></option>
                <?php endforeach; ?>
            </select><br>
            <label>Password:</label><input type="text" name="password" id="passwordInput"
                placeholder="Kosongkan jika tidak diubah"><br>
            <label>Role:</label>
            <select name="role" id="roleInput">
                <option value="user">user</option>
                <option value="admin">admin</option>
                <option value="admin">superadmin</option>
            </select><br><br>
            <button type="submit" class="btn btn-success">Simpan</button>
            <button type="button" class="btn btn-danger" onclick="tutupModal()">Batal</button>
        </form>
    </div>

    <script>
        const modal = document.getElementById('modalForm');
        const overlay = document.getElementById('overlay');

        function bukaModalTambah() {
            document.getElementById('modalTitle').innerText = "Tambah Personel";
            document.getElementById('aksiInput').value = "tambah";
            document.getElementById('idInput').value = "";
            ['nrp', 'nama', 'jabatan', 'password'].forEach(id => document.getElementById(id + 'Input').value = "");
            document.getElementById('pangkatInput').selectedIndex = 0;
            document.getElementById('korpsInput').selectedIndex = 0;
            document.getElementById('satkerInput').selectedIndex = 0;
            document.getElementById('roleInput').value = "user";
            modal.classList.add('active');
            overlay.classList.add('active');
        }

        function bukaModalEdit(data) {
            document.getElementById('modalTitle').innerText = "Edit Personel";
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
            modal.classList.add('active');
            overlay.classList.add('active');
        }

        function tutupModal() {
            modal.classList.remove('active');
            overlay.classList.remove('active');
        }

        overlay.addEventListener('click', tutupModal);
    </script>
</body>

</html>
<?php
session_start();
require_once '../inc/db.php';

date_default_timezone_set('Asia/Jakarta');

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

// Yang TIDAK HADIR dengan keterangan
$sqlTidakHadir = "
    SELECT COUNT(*) 
    FROM absensi a
    JOIN personel p ON p.id = a.personel_id
    WHERE a.tanggal = ? AND a.status = 'TIDAK HADIR'
    $filterJoin
";
$paramsTH = array_merge([$tanggal], ($role === 'admin' ? [$satker] : []));
$stmt = $pdo->prepare($sqlTidakHadir);
$stmt->execute($paramsTH);
$jumlah_tidak_hadir = $stmt->fetchColumn();

// Yang belum absen sama sekali
$sqlSudahAbsen = "
    SELECT COUNT(*) 
    FROM absensi a
    JOIN personel p ON p.id = a.personel_id
    WHERE a.tanggal = ?
    $filterJoin
";
$paramsSudah = array_merge([$tanggal], ($role === 'admin' ? [$satker] : []));
$stmt = $pdo->prepare($sqlSudahAbsen);
$stmt->execute($paramsSudah);
$sudah_absen = $stmt->fetchColumn();
$jumlah_belum_absen = $total_personel - $sudah_absen;

// Personel belum absen (yang belum ada record absensi sama sekali)
$sqlBelum = "
    SELECT p.id, p.nama, p.pangkat, p.korps 
    FROM personel p
    WHERE p.id NOT IN (
        SELECT personel_id FROM absensi WHERE tanggal = ?
    )
";
$paramsBelum = [$tanggal];
if ($role === 'admin') {
    $sqlBelum .= " AND p.satker = ?";
    $paramsBelum[] = $satker;
}
$stmt = $pdo->prepare($sqlBelum);
$stmt->execute($paramsBelum);
$personel_belum_absen = $stmt->fetchAll(PDO::FETCH_ASSOC);

// TAMBAHAN: Ambil data personel yang sudah ada keterangan hari ini untuk menampilkan di kategori yang sesuai
$sqlKeterangan = "
    SELECT p.id, p.nama, p.pangkat, p.korps, a.kategori, a.keterangan 
    FROM personel p 
    JOIN absensi a ON p.id = a.personel_id 
    WHERE a.tanggal = ? 
      AND a.status = 'TIDAK HADIR' 
      AND a.keterangan IS NOT NULL
";
$paramsKet = [$tanggal];
if ($role === 'admin') {
    $sqlKeterangan .= " AND p.satker = ?";
    $paramsKet[] = $satker;
}
$stmt = $pdo->prepare($sqlKeterangan);
$stmt->execute($paramsKet);
$data_keterangan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kelompokkan berdasarkan kategori
foreach ($data_keterangan as $data) {
    $kategori_db = $data['kategori'];
    $kategori = '';

    // Map kategori dari database ke kategori display
    switch ($kategori_db) {
        case 'DINAS DALAM':
        case 'DINAS LUAR':
        case 'BANTUAN PERSONEL':
        case 'PENDIDIKAN':
        case 'CUTI':
        case 'IZIN':
        case 'SAKIT':
            $kategori = $kategori_db;
            break;
        default:
            $kategori = 'TANPA KETERANGAN';
    }

    if (!isset($keterangan_hari_ini[$kategori])) {
        $keterangan_hari_ini[$kategori] = [];
    }

    $keterangan_hari_ini[$kategori][] = $data;
}

// Kategori absensi
$kategori = ['DINAS DALAM', 'DINAS LUAR', 'BANTUAN PERSONEL', 'PENDIDIKAN', 'CUTI', 'IZIN', 'SAKIT', 'TANPA KETERANGAN'];
?>

<!DOCTYPE html>
<html>

<head>
    <title>Dashboard Admin - PERSEN</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        html {
            scroll-behavior: smooth;
        }

        .dashboard-button {
            display: inline-block;
            padding: 10px 18px;
            margin: 10px;
            background-color: #007bff;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .dashboard-button:hover {
            background-color: #0056b3;
        }

        .existing-entry {
            background-color: #f8f9fa;
        }

        .edit-btn {
            background-color: #ffc107;
            color: #212529;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            margin-right: 5px;
        }

        .edit-btn:hover {
            background-color: #e0a800;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Selamat Datang Admin</h1>
        <p class="tanggal"><?= date('d F Y') ?></p>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"
                style="background-color: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #c3e6cb;">
                <?= $_SESSION['success_message'] ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"
                style="background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #f5c6cb;">
                <?= $_SESSION['error_message'] ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="header">
            <div class="logout-container">
                <a href="logout.php" class="logout-button">Logout</a>
            </div>
        </div>

        <div class="statistik-horizontal">
            <div class="stat-box total">
                <h3>Jumlah</h3>
                <p><?= $total_personel ?></p>
            </div>
            <div class="stat-box hadir">
                <h3>Hadir</h3>
                <p><?= $jumlah_hadir ?></p>
            </div>
            <div class="stat-box kurang">
                <h3>Tidak Hadir</h3>
                <p><?= $jumlah_tidak_hadir ?></p>
            </div>
            <div class="stat-box kurang">
                <h3>Belum Absen</h3>
                <p><?= $jumlah_belum_absen ?></p>
            </div>
        </div>

        <div class="chart-container">
            <canvas id="absenChart"></canvas>
        </div>

        <div style="text-align: center;">
            <a href="personel.php" class="dashboard-button">KELOLA PERSONEL</a>
            <a href="rekap_harian.php" class="rekap-btn">REKAP HARIAN</a>
            <a href="../index.php" class="dashboard-button">QR DASHBOARD</a>
        </div>

        <div class="nav-buttons">
            <?php foreach ($kategori as $ket): ?>
                <button type="button"
                    onclick="scrollToSection('<?= strtolower(str_replace(' ', '-', $ket)) ?>')"><?= $ket ?></button>
            <?php endforeach; ?>
        </div>

        <form id="formKeterangan" action="simpan_keterangan.php" method="POST">
            <input type="hidden" name="data" id="keteranganData">
            <input type="hidden" name="deletedIds" id="deletedIds">

            <?php foreach ($kategori as $ket): ?>
                <div class="accordion-section" id="<?= strtolower(str_replace(' ', '-', $ket)) ?>">
                    <h2><?= $ket ?></h2>
                    <table id="tableKeterangan" border="1" cellpadding="5" cellspacing="0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody data-kategori="<?= $ket ?>">
                            <?php
                            // Tampilkan data yang sudah ada untuk hari ini
                            if (isset($keterangan_hari_ini[$ket])):
                                $no = 1;
                                foreach ($keterangan_hari_ini[$ket] as $existing):
                                    ?>
                                    <tr class="existing-entry" data-existing-id="<?= $existing['id'] ?>">
                                        <td><?= $no++ ?></td>
                                        <td data-nama="<?= htmlspecialchars($existing['pangkat'] . ' ' . $existing['korps'] . ' ' . $existing['nama']) ?>"
                                            data-id="<?= $existing['id'] ?>">
                                            <?= htmlspecialchars($existing['pangkat'] . ' ' . $existing['korps'] . ' ' . $existing['nama']) ?>
                                        </td>
                                        <td>
                                            <?php if ($ket === "TANPA KETERANGAN"): ?>
                                                <input type="text" class="input-keterangan" style="width:90%" value="TANPA KETERANGAN"
                                                    disabled>
                                            <?php else: ?>
                                                <input type="text" data-keterangan class="input-keterangan" style="width:90%"
                                                    value="<?= htmlspecialchars($existing['keterangan']) ?>"
                                                    placeholder="Isi keterangan...">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($ket !== "TANPA KETERANGAN"): ?>
                                                <button type="button" class="edit-btn" onclick="editKeterangan(this)"
                                                    style="margin-left:10px;">Edit</button>
                                            <?php endif; ?>
                                            <button type="button" class="hapus-btn" style="margin-left:10px">Hapus</button>
                                        </td>

                                    </tr>
                                    <?php
                                endforeach;
                            endif;
                            ?>

                            <tr class="dropdown-row">
                                <td>-</td>
                                <td colspan="3">
                                    <div class="dropdown-belum">
                                        <button type="button" class="dropdown-toggle">Pilih Personel ▼</button>
                                        <ul class="dropdown-menu">
                                            <?php foreach ($personel_belum_absen as $p): ?>
                                                <li data-id="<?= $p['id'] ?>">
                                                    <?= htmlspecialchars($p['pangkat'] . ' ' . $p['korps'] . ' ' . $p['nama']) ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn-simpan">Simpan</button>
        </form>
    </div>

    <script>

        document.getElementById("formKeterangan").addEventListener("submit", function (e) {
            // contoh ambil tanggal hari ini
            const today = new Date().toISOString().split("T")[0];

            // Kumpulkan data keterangan dari tabel
            let dataArray = [];

            document.querySelectorAll("tbody").forEach(tbody => {
                const kategori = tbody.getAttribute("data-kategori");

                tbody.querySelectorAll("tr.existing-entry").forEach(tr => {
                    dataArray.push({
                        id: tr.dataset.existingId,
                        isExisting: true,
                        kategori: kategori,
                        keterangan: tr.querySelector("input[data-keterangan]").value,
                        nama: tr.querySelector("td[data-nama]").dataset.nama
                    });
                });

                // Jika ada personel baru yang dipilih dari dropdown, tambahkan juga
                tbody.querySelectorAll("tr.new-entry").forEach(tr => {
                    dataArray.push({
                        id: tr.querySelector("td[data-nama]").dataset.id,
                        isExisting: false,
                        kategori: kategori,
                        keterangan: tr.querySelector("input[data-keterangan]").value,
                        nama: tr.querySelector("td[data-nama]").dataset.nama
                    });
                });
            });

            // Buat payload
            const payload = {
                tanggal: today,
                data: dataArray
            };

            // Masukkan ke hidden input
            document.getElementById("keteranganData").value = JSON.stringify(payload);
        });

        // Set untuk tracking nama yang sudah dipilih (termasuk yang existing)
        const semuaNamaTerpilih = new Set();

        // Tambahkan existing entries ke set
        document.querySelectorAll('tr[data-existing-id]').forEach(tr => {
            const id = tr.getAttribute('data-existing-id');
            semuaNamaTerpilih.add(id);
        });

        document.querySelectorAll('.dropdown-toggle').forEach(button => {
            button.addEventListener('click', function () {
                this.nextElementSibling.classList.toggle('show');
            });
        });

        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.addEventListener('click', function (e) {
                if (e.target.tagName === 'LI') {
                    const li = e.target;
                    const nama = li.textContent;
                    const id = li.dataset.id;
                    if (semuaNamaTerpilih.has(id)) return;

                    semuaNamaTerpilih.add(id);

                    const kategoriTable = li.closest('tbody');
                    const kategori = kategoriTable.getAttribute('data-kategori');
                    const existingRows = kategoriTable.querySelectorAll('tr:not(.dropdown-row)');
                    const rowCount = existingRows.length + 1;

                    const tr = document.createElement('tr');

                    // kalau kategori TANPA KETERANGAN -> isi otomatis, disable input
                    if (kategori === 'TANPA KETERANGAN') {
                        tr.innerHTML = `
                    <td>${rowCount}</td>
                    <td data-nama="${nama}" data-id="${id}">${nama}</td>
                    <td><input type="text" value="TANPA KETERANGAN" class="input-keterangan" style="width:90%" disabled></td>
                    <td><button type="button" class="hapus-btn" style="margin-left:10px">Hapus</button></td>
                `;
                    } else {
                        tr.innerHTML = `
                    <td>${rowCount}</td>
                    <td data-nama="${nama}" data-id="${id}">${nama}</td>
                    <td><input type="text" style="width:90%" data-keterangan class="input-keterangan" placeholder="Isi keterangan..."></td>
                    <td><button type="button" class="hapus-btn" style="margin-left:10px">Hapus</button></td>
                `;
                    }

                    kategoriTable.insertBefore(tr, kategoriTable.querySelector('.dropdown-row'));

                    // hapus item yang sudah dipilih dari semua dropdown
                    document.querySelectorAll(`.dropdown-menu li[data-id="${id}"]`).forEach(el => el.remove());
                }
            });
        });


        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('hapus-btn')) {
                const tr = e.target.closest('tr');
                const id = tr.querySelector('td[data-id]')?.getAttribute('data-id');

                // jika existing, tambahkan ke daftar deletedIds
                if (tr.hasAttribute('data-existing-id') && id) {
                    let deleted = JSON.parse(document.getElementById("deletedIds").value || "[]");
                    deleted.push(id);
                    document.getElementById("deletedIds").value = JSON.stringify(deleted);
                }

                tr.remove();
                updateRowNumbers();
            }
        });

        function updateRowNumbers() {
            document.querySelectorAll('tbody').forEach(tbody => {
                const rows = tbody.querySelectorAll('tr:not(.dropdown-row)');
                rows.forEach((row, index) => {
                    const noCell = row.querySelector('td:first-child');
                    if (noCell) {
                        noCell.textContent = index + 1;
                    }
                });
            });
        }

        function editKeterangan(button) {
            const input = button.closest('tr').querySelector('.input-keterangan');
            input.focus();
            input.select();
        }

        document.getElementById('formKeterangan').addEventListener('submit', function (e) {
            e.preventDefault(); // Prevent default form submission

            const hasil = [];
            document.querySelectorAll('tbody').forEach(tbody => {
                const kategori = tbody.getAttribute('data-kategori');
                tbody.querySelectorAll('tr:not(.dropdown-row)').forEach(tr => {
                    const nama = tr.querySelector('td[data-nama]')?.textContent.trim();
                    const id = tr.querySelector('td[data-id]')?.getAttribute('data-id');
                    let keteranganInput = tr.querySelector('input[data-keterangan]');
                    let keterangan = "";

                    if (keteranganInput) {
                        keterangan = keteranganInput.value.trim();
                    } else if (kategori === "TANPA KETERANGAN") {
                        keterangan = "TANPA KETERANGAN";
                    }

                    if (nama && id) {
                        hasil.push({
                            id: id,
                            nama: nama,
                            kategori: kategori,
                            keterangan: keterangan,
                            isExisting: tr.hasAttribute('data-existing-id')
                        });
                    }

                });

            });

            console.log('Data yang akan dikirim:', hasil); // Debug log
            if (hasil.length > 0) {
                document.getElementById('keteranganData').value = JSON.stringify(hasil);
                e.target.submit(); // pastikan form terkirim
            } else if (JSON.parse(document.getElementById("deletedIds").value || "[]").length > 0) {
                // kalau tidak ada data baru tapi ada data yang dihapus
                e.target.submit();
            } else {
                alert('Tidak ada data keterangan untuk disimpan atau dihapus!');
            }

        });

        function scrollToSection(id) {
            const el = document.getElementById(id);
            if (el) el.scrollIntoView({ behavior: 'smooth' });
        }

        const ctx = document.getElementById('absenChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Tidak Hadir', 'Belum Absen'],
                datasets: [{
                    data: [<?= $jumlah_hadir ?>, <?= $jumlah_tidak_hadir ?>, <?= $jumlah_belum_absen ?>],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    title: {
                        display: true,
                        text: 'Statistik Kehadiran'
                    }
                }
            }
        });

        document.addEventListener('click', function (event) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                const toggle = menu.previousElementSibling;
                if (!menu.contains(event.target) && !toggle.contains(event.target)) {
                    menu.classList.remove('show');
                }
            });
        });
    </script>

</body>

</html>
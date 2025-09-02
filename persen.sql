-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 02 Sep 2025 pada 09.40
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `persen`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `absensi`
--

CREATE TABLE `absensi` (
  `id` int(11) NOT NULL,
  `personel_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jam` time DEFAULT curtime(),
  `keluar` time NOT NULL,
  `status` enum('HADIR','TIDAK HADIR') DEFAULT 'HADIR',
  `kategori` varchar(50) DEFAULT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `absensi`
--

INSERT INTO `absensi` (`id`, `personel_id`, `tanggal`, `jam`, `keluar`, `status`, `kategori`, `keterangan`) VALUES
(2, 4, '2025-08-06', '04:33:34', '00:00:00', 'HADIR', NULL, NULL),
(3, 4, '2025-08-07', '09:23:52', '00:00:00', 'HADIR', NULL, NULL),
(5, 6, '2025-08-07', '09:29:38', '00:00:00', 'HADIR', NULL, NULL),
(6, 4, '2025-08-08', '06:05:16', '00:00:00', 'HADIR', NULL, NULL),
(7, 6, '2025-08-08', '11:08:50', '00:00:00', 'HADIR', NULL, NULL),
(12, 4, '2025-08-11', '17:11:28', '00:00:00', 'HADIR', NULL, NULL),
(14, 1, '2025-08-11', '15:49:28', '00:00:00', 'TIDAK HADIR', 'CUTI', 'hjvjhvjvhjv'),
(15, 5, '2025-08-11', '15:49:56', '00:00:00', 'TIDAK HADIR', 'IZIN', 'hvhjjhvjhvhj'),
(16, 6, '2025-08-11', '15:49:56', '00:00:00', 'TIDAK HADIR', 'SAKIT', 'vhjvj'),
(17, 10, '2025-08-11', '15:49:56', '00:00:00', 'TIDAK HADIR', 'SAKIT', 'vjvjhvjhvhjvjhvhj'),
(25, 1, '2025-08-12', '14:22:56', '00:00:00', 'TIDAK HADIR', 'DINAS DALAM', 'PIKET MURAI'),
(26, 4, '2025-08-12', '09:27:19', '14:28:58', 'HADIR', NULL, NULL),
(27, 10, '2025-08-12', '14:30:00', '00:00:00', 'TIDAK HADIR', 'DINAS LUAR', 'OPSINFO'),
(28, 6, '2025-08-12', '15:02:53', '00:00:00', 'HADIR', NULL, NULL),
(29, 1, '2025-08-19', '14:38:38', '00:00:00', 'TIDAK HADIR', 'DINAS DALAM', 'PIKET MURAI'),
(30, 5, '2025-08-19', '14:42:36', '00:00:00', 'TIDAK HADIR', 'DINAS LUAR', 'PERNIKA'),
(31, 6, '2025-08-19', '14:43:49', '00:00:00', 'TIDAK HADIR', 'BANTUAN PERSONEL', 'BP SPO'),
(39, 6, '2025-08-21', '09:22:35', '00:00:00', 'HADIR', NULL, NULL),
(41, 1, '2025-08-21', '15:08:45', '00:00:00', 'TIDAK HADIR', 'IZIN', 'MENIKAH'),
(45, 6, '2025-08-22', '09:29:48', '00:00:00', 'HADIR', NULL, NULL),
(46, 1, '2025-08-26', '16:05:25', '00:00:00', 'TIDAK HADIR', 'IZIN', 'CUTI TAHUNAN'),
(47, 6, '2025-08-26', '16:05:25', '00:00:00', 'TIDAK HADIR', 'SAKIT', 'SAKIT'),
(48, 5, '2025-08-26', '16:05:25', '00:00:00', 'TIDAK HADIR', 'TANPA KETERANGAN', 'TANPA KETERANGAN');

-- --------------------------------------------------------

--
-- Struktur dari tabel `absen_keluar`
--

CREATE TABLE `absen_keluar` (
  `id` int(32) NOT NULL,
  `personel_id` int(8) NOT NULL,
  `tanggal` date NOT NULL DEFAULT current_timestamp(),
  `jam` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `keterangan` varchar(256) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pangkat`
--

CREATE TABLE `pangkat` (
  `pangkat_id` int(16) NOT NULL,
  `nama` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pangkat`
--

INSERT INTO `pangkat` (`pangkat_id`, `nama`) VALUES
(1, 'Prajurit Dua'),
(2, 'Prajurit Satu'),
(3, 'Prajurit Kepala'),
(4, 'Kopral Dua'),
(5, 'Kopral Satu'),
(6, 'Sersan Dua'),
(7, 'Sersan Satu'),
(8, 'Sersan Kepala'),
(9, 'Sersan Mayor'),
(10, 'Pembantu Letnan Dua'),
(11, 'Pembantu Letnan Satu'),
(12, 'Letnan Dua'),
(13, 'Letnan Satu'),
(14, 'Kapten'),
(15, 'Mayor'),
(16, 'Letnan Kolonel'),
(17, 'Kolonel');

-- --------------------------------------------------------

--
-- Struktur dari tabel `personel`
--

CREATE TABLE `personel` (
  `id` int(11) NOT NULL,
  `nrp` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `pangkat` varchar(50) DEFAULT NULL,
  `korps` varchar(50) DEFAULT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `satker` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user','superadmin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `personel`
--

INSERT INTO `personel` (`id`, `nrp`, `nama`, `pangkat`, `korps`, `jabatan`, `satker`, `password`, `role`) VALUES
(1, '561289', 'Komang', 'LETDA', 'LEK', 'Pama', 'DISINFOLAHTAAU', '$2y$10$g7dRGuK7oACZnMdc2hDDzu/mx4IIRJBRhHz0wAUXK5uQq6ZrMBqV.', 'user'),
(4, '558899', 'Setyo', 'LETDA', 'LEK', 'Ps.Kaur', 'DISINFOLAHTAAU', '$2y$10$ZVAMyycInN.8ZviFL5ET/.mr96pSa2CLgGcKAWha2gzM9eZ6WiW7m', 'user'),
(5, '561972', 'Teguh', 'LETDA', 'LEK', 'Pama', 'DISINFOLAHTAAU', '$2y$10$UaFC4/QbwOvArPf.iKipWO/Ty7HT0tfRyjr9ecIOrKPiSRhV1AC8u', 'user'),
(6, '561284', 'Gutri', 'LETDA', 'LEK', 'Pama', 'DISINFOLAHTAAU', '$2y$10$6kpK7W6bYTqjlhJcO0Un.OFenhOYVrf3iVwCZDwtztHHtykEmnvz.', 'user'),
(10, '561974', 'Nathans', 'LETDA', 'LEK', 'Pama', 'DISINFOLAHTAAU', '$2y$10$YVqBhjlWLuWtcAHxIKBzWuAM7nJaLD7S4q5FoYArALOTAgBp2mNpO', 'admin'),
(125, '555337', 'Tiara Septian Adi Prakasa', 'LETDA', 'LEK', 'Kaurrops apljar subsiops pustassisinfo', 'DISINFOLAHTAAU', '$2y$10$YvmVISVKC.cHpM1AAalQYeZ5sn3DSiDitoZk4R.X/azSPj16aBhwO', 'user'),
(354, '555333', 'Ramadhan', 'LETDA', 'ADM', 'Kaur', 'DISDIKAU', '$2y$10$aFY96IKWzm6pYOrLVB1d.eE4BwjSYZTyiwYnEhzz33ag0/nYf91Am', 'user'),
(355, '123123', 'Rizal', 'LETDA', 'ADM', 'Admin Disdik', 'DISDIKAU', '$2y$10$luXdACEGDtYPR8LxAwH6reAFUeiuiyfBVwJ7MFHolVAiePw7AKRyW', 'admin'),
(356, '111222', 'SUPER ADMIN', 'MARSMA', 'LEK', 'KADISINFOLAHTAAU', 'DISINFOLAHTAAU', '$2y$10$IyBA2BHyQZt3F0OlcQc/5OdmuE/xVGQQHR69ym3tsxQ8OlMViVfl6', 'superadmin'),
(357, '561285', 'Nasution', 'LETDA', 'LEK', 'LATKER', 'DISINFOLAHTAAU', '$2y$10$FhadZ8hSHkOtNWuuhzNCduBXHdjhMMdeB.jWS0vVW3WqlTznj56Qy', 'admin');

-- --------------------------------------------------------

--
-- Struktur dari tabel `personel2`
--

CREATE TABLE `personel2` (
  `id` int(16) NOT NULL,
  `nrp` int(8) NOT NULL,
  `nama` varchar(64) NOT NULL,
  `korps` varchar(16) NOT NULL,
  `jabatan` varchar(32) NOT NULL,
  `pangkat_id` int(16) NOT NULL,
  `satker_id` int(11) NOT NULL,
  `password` varchar(32) NOT NULL,
  `role` varchar(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `satker`
--

CREATE TABLE `satker` (
  `satker_id` int(16) NOT NULL,
  `nama` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `satker`
--

INSERT INTO `satker` (`satker_id`, `nama`) VALUES
(1, 'Disinfolahtaau');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personel_id` (`personel_id`),
  ADD KEY `idx_absensi_tanggal` (`tanggal`);

--
-- Indeks untuk tabel `absen_keluar`
--
ALTER TABLE `absen_keluar`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pangkat`
--
ALTER TABLE `pangkat`
  ADD PRIMARY KEY (`pangkat_id`);

--
-- Indeks untuk tabel `personel`
--
ALTER TABLE `personel`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nrp` (`nrp`),
  ADD KEY `idx_personel_nrp` (`nrp`);

--
-- Indeks untuk tabel `personel2`
--
ALTER TABLE `personel2`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nrp` (`nrp`);

--
-- Indeks untuk tabel `satker`
--
ALTER TABLE `satker`
  ADD PRIMARY KEY (`satker_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT untuk tabel `absen_keluar`
--
ALTER TABLE `absen_keluar`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pangkat`
--
ALTER TABLE `pangkat`
  MODIFY `pangkat_id` int(16) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `personel`
--
ALTER TABLE `personel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=358;

--
-- AUTO_INCREMENT untuk tabel `personel2`
--
ALTER TABLE `personel2`
  MODIFY `id` int(16) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `satker`
--
ALTER TABLE `satker`
  MODIFY `satker_id` int(16) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`personel_id`) REFERENCES `personel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

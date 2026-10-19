-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 29, 2026 at 10:08 PM
-- Server version: 8.4.11
-- PHP Version: 8.2.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_pantau_pemilu`
--

-- --------------------------------------------------------

--
-- Table structure for table `kategori_pelanggaran`
--

CREATE TABLE `kategori_pelanggaran` (
  `id` int NOT NULL,
  `nama_kategori` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kategori_pelanggaran`
--

INSERT INTO `kategori_pelanggaran` (`id`, `nama_kategori`) VALUES
(1, 'Politik Uang'),
(2, 'Netralitas ASN'),
(3, 'APK Ilegal'),
(4, 'Intimidasi'),
(5, 'Lainnya');

-- --------------------------------------------------------

--
-- Table structure for table `laporan`
--

CREATE TABLE `laporan` (
  `id` int NOT NULL,
  `kode_unik` varchar(50) DEFAULT NULL,
  `nama_pelapor` varchar(100) DEFAULT NULL,
  `lokasi_kejadian` varchar(255) NOT NULL,
  `kecamatan` varchar(100) DEFAULT NULL,
  `desa` varchar(100) DEFAULT NULL,
  `kategori_id` int NOT NULL,
  `deskripsi` text NOT NULL,
  `bukti` varchar(255) DEFAULT NULL,
  `file_bukti` varchar(255) DEFAULT NULL,
  `status` enum('masuk','diklarifikasi','diteruskan','selesai') DEFAULT 'masuk',
  `waktu_lapor` datetime DEFAULT CURRENT_TIMESTAMP,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `laporan`
--

INSERT INTO `laporan` (`id`, `kode_unik`, `nama_pelapor`, `lokasi_kejadian`, `kecamatan`, `desa`, `kategori_id`, `deskripsi`, `bukti`, `file_bukti`, `status`, `waktu_lapor`, `latitude`, `longitude`) VALUES
(6, NULL, 'Anonim', 'TPS 9', 'Ngampel', 'Desa Ngampel', 2, 'Asn tidak netral', '30ef5559932769b092d8c7b787219535.png', NULL, 'selesai', '2026-08-20 16:08:23', '-6.9781', '110.2142'),
(7, NULL, 'wahyudi', 'TPS 06', 'Kendal', 'Patukangan', 4, 'Terjadinya pengancaman kepada salah satu keluarga', '87dc02cf06c658ebe133afb6106d2390.mp4', NULL, 'diteruskan', '2026-08-27 10:16:01', '-6.92', '110.2'),
(8, NULL, 'Anonim', 'TPS 9', 'Singorojo', 'Desa Singorojo', 1, 'terjadinya kecurangan pada pasangan paslon nomor 2', 'c3ff8aff4df286f81b4f4b4fe735c806.png', NULL, 'selesai', '2026-09-02 14:22:52', '-7.1125', '110.2114'),
(9, NULL, 'Anonim', 'Tps 1', 'Boja', 'Boja', 3, 'Adanya pelanggaran', '95479985671eabe7f93bffad532c110f.png', NULL, 'masuk', '2026-09-02 15:52:03', '-7.1012', '110.2814'),
(10, NULL, 'Anonim', 'tps5', 'Sukorejo', 'sukorejo', 1, 'ada pelanggaran', '65b0f9345ad7c5a62032603afb83620a.png', NULL, 'diklarifikasi', '2026-09-02 15:54:40', '-7.11', '109.95'),
(11, 'SIPPT-2026-0011', 'Anonim', 'tps 6', 'Cepiring', 'cepiring', 4, 'terjadinya pelanggaran', '0f631b1cae924329d3b4588651440a6b.png', NULL, 'selesai', '2026-09-02 16:06:50', '-6.9135', '110.1452'),
(12, 'SIPPT-2026-0012', 'Anonim', 'TPS 9', 'Gemuh', 'Gemuh', 1, 'adanya politik uang', '0eb2f635245f7ce72b66f5f954117059.png', NULL, 'diklarifikasi', '2026-09-04 18:26:33', '-6.9691', '110.1118'),
(13, 'SIPPT-2026-0013', 'Anonim', 'tps 5', 'Kendal', 'kendal', 1, 'terjadi serangan fajar', 'cb9770864d66b676e6faecddbbce5a01.jpg', NULL, 'diklarifikasi', '2026-09-20 10:44:44', '-6.9217', '110.204');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','petugas_kecamatan') NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$AOjgRYLpicPyQeRA7Ws.lOvIlH2Yt7KOYgLtWy9JhJS774zHmXcuu', 'admin', '2026-08-13 09:10:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `kategori_pelanggaran`
--
ALTER TABLE `kategori_pelanggaran`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `laporan`
--
ALTER TABLE `laporan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `kategori_pelanggaran`
--
ALTER TABLE `kategori_pelanggaran`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `laporan`
--
ALTER TABLE `laporan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `laporan`
--
ALTER TABLE `laporan`
  ADD CONSTRAINT `laporan_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_pelanggaran` (`id`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

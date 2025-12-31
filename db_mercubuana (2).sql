-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 31, 2025 at 07:08 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_mercubuana`
--

-- --------------------------------------------------------

--
-- Table structure for table `fasilitas`
--

CREATE TABLE `fasilitas` (
  `id` int(11) NOT NULL,
  `nama_fasilitas` varchar(100) NOT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `jumlah` int(11) DEFAULT 1,
  `kondisi` enum('baik','rusak') DEFAULT 'baik',
  `memerlukan_proposal` tinyint(1) DEFAULT 0,
  `gambar` varchar(255) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fasilitas`
--

INSERT INTO `fasilitas` (`id`, `nama_fasilitas`, `kategori`, `jumlah`, `kondisi`, `memerlukan_proposal`, `gambar`, `deskripsi`, `created_at`) VALUES
(1, 'Lapangan Mini Soccer UMB', 'Olahraga', 1, 'baik', 0, 'lapanganminsoc.jpg', 'Lapangan Mini Soccer berstandar FIFA', '2025-12-02 14:59:11'),
(2, 'Lapangan Voli', 'Olahraga', 1, 'baik', 0, 'lapanganvoli.jpg', 'Lapangan voli', '2025-12-02 14:59:11'),
(3, 'Lapangan Bowling', 'Olahraga', 2, 'baik', 0, 'lapanganbultang.jpg', 'Lapangan Bulu Tangkis', '2025-12-02 14:59:11'),
(4, 'Kolam Renang', 'Olahraga', 2, 'baik', 0, 'default-facility.jpg', 'Kolam renang untuk berenang', '2025-12-08 13:39:13'),
(6, 'Aula Rektorat', 'Ruang Acara', 1, 'baik', 1, 'default-facility.jpg', 'Aula untuk acara resmi universitas, memerlukan proposal kegiatan', '2025-12-31 08:44:11'),
(7, 'Auditorium Utama', 'Ruang Acara', 1, 'baik', 1, 'default-facility.jpg', 'Auditorium berkapasitas 500 orang, memerlukan proposal kegiatan', '2025-12-31 08:44:11'),
(8, 'Studio Multimedia', 'Multimedia', 1, 'baik', 1, 'default-facility.jpg', 'Studio multimedia untuk syuting dan kegiatan broadcasting, memerlukan proposal kegiatan', '2025-12-31 08:44:11');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_perkuliahan`
--

CREATE TABLE `jadwal_perkuliahan` (
  `id` int(11) NOT NULL,
  `ruangan_id` int(11) NOT NULL,
  `mata_kuliah` varchar(100) NOT NULL,
  `dosen` varchar(100) NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `semester` varchar(20) NOT NULL,
  `tahun_ajaran` varchar(20) NOT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal_perkuliahan`
--

INSERT INTO `jadwal_perkuliahan` (`id`, `ruangan_id`, `mata_kuliah`, `dosen`, `hari`, `jam_mulai`, `jam_selesai`, `semester`, `tahun_ajaran`, `aktif`, `created_at`) VALUES
(1, 1, 'Pemrograman Web', 'Dr. Ahmad Fauzi', 'Senin', '08:00:00', '10:00:00', 'Ganjil', '2025/2026', 1, '2025-12-29 12:13:48'),
(2, 1, 'Basis Data', 'Dr. Siti Rahma', 'Rabu', '10:00:00', '12:00:00', 'Ganjil', '2025/2026', 1, '2025-12-29 12:13:48'),
(3, 2, 'Algoritma Pemrograman', 'Prof. Budi Santoso', 'Selasa', '13:00:00', '15:00:00', 'Ganjil', '2025/2026', 1, '2025-12-29 12:13:48'),
(4, 2, 'Struktur Data', 'Dr. Maya Sari', 'Kamis', '08:00:00', '10:00:00', 'Ganjil', '2025/2026', 1, '2025-12-29 12:13:48'),
(5, 3, 'Jaringan Komputer', 'Dr. Hendra Wijaya', 'Senin', '13:00:00', '15:00:00', 'Ganjil', '2025/2026', 1, '2025-12-29 12:13:48');

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman_fasilitas`
--

CREATE TABLE `peminjaman_fasilitas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fasilitas_id` int(11) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `jumlah_pinjam` int(11) DEFAULT 1,
  `keperluan` text NOT NULL,
  `dokumen_pendukung` varchar(255) DEFAULT NULL,
  `status` enum('pending','disetujui','ditolak') DEFAULT 'pending',
  `disetujui_oleh` int(11) DEFAULT NULL,
  `nama_penyetuju` varchar(100) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `peminjaman_fasilitas`
--

INSERT INTO `peminjaman_fasilitas` (`id`, `user_id`, `fasilitas_id`, `tanggal_mulai`, `tanggal_selesai`, `jam_mulai`, `jam_selesai`, `jumlah_pinjam`, `keperluan`, `dokumen_pendukung`, `status`, `disetujui_oleh`, `nama_penyetuju`, `catatan`, `created_at`, `updated_at`) VALUES
(1, 1, 3, '2025-12-04', '2025-12-04', '16:00:00', '18:00:00', 1, 'test', NULL, 'ditolak', NULL, NULL, 'test', '2025-12-03 02:28:24', '2025-12-08 12:50:33'),
(2, 1, 3, '2025-12-10', '2025-12-10', '15:00:00', '17:00:00', 1, 'main bowling', NULL, 'disetujui', NULL, NULL, NULL, '2025-12-09 06:08:57', '2025-12-09 06:13:42'),
(3, 1, 1, '2025-12-09', '2025-12-09', '16:00:00', '18:00:00', 1, 'main minsoc ti', NULL, 'disetujui', NULL, NULL, NULL, '2025-12-09 06:27:09', '2025-12-09 06:28:08'),
(4, 1, 1, '2025-12-25', '2025-12-25', '16:00:00', '18:00:00', 1, 'test', NULL, 'disetujui', 1, 'Joko Wijaya', NULL, '2025-12-24 01:20:46', '2025-12-24 01:21:07'),
(5, 1, 1, '2025-12-24', '2025-12-24', '16:00:00', '20:00:00', 1, 'test', NULL, 'disetujui', 1, 'Joko Wijaya', NULL, '2025-12-24 01:52:25', '2025-12-24 01:55:04');

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman_ruangan`
--

CREATE TABLE `peminjaman_ruangan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ruangan_id` int(11) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `keperluan` text NOT NULL,
  `dokumen_pendukung` varchar(255) DEFAULT NULL,
  `status` enum('pending','disetujui','ditolak') DEFAULT 'pending',
  `disetujui_oleh` int(11) DEFAULT NULL,
  `nama_penyetuju` varchar(100) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `peminjaman_ruangan`
--

INSERT INTO `peminjaman_ruangan` (`id`, `user_id`, `ruangan_id`, `tanggal_mulai`, `tanggal_selesai`, `jam_mulai`, `jam_selesai`, `keperluan`, `dokumen_pendukung`, `status`, `disetujui_oleh`, `nama_penyetuju`, `catatan`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2025-12-04', '2025-12-04', '16:00:00', '18:00:00', 'test', NULL, 'disetujui', 1, 'Budiono Siregar', NULL, '2025-12-03 02:25:40', '2025-12-08 10:36:13'),
(2, 1, 7, '2025-12-10', '2025-12-10', '16:00:00', '18:00:00', 'ukm mbec', NULL, 'disetujui', 1, 'Budiono Siregar', NULL, '2025-12-09 06:07:55', '2025-12-09 06:11:05'),
(3, 1, 2, '2025-12-09', '2025-12-09', '17:00:00', '19:00:00', 'keperluan ukm', NULL, 'disetujui', 1, 'Budiono Siregar', NULL, '2025-12-09 06:26:27', '2025-12-09 06:27:39'),
(4, 1, 7, '2025-12-27', '2025-12-28', '08:22:00', '09:22:00', 'meeting', NULL, 'ditolak', NULL, NULL, 'sudah di book', '2025-12-24 01:22:25', '2025-12-24 01:28:48'),
(5, 1, 2, '2025-12-24', '2025-12-24', '10:00:00', '11:50:00', 'test', NULL, 'disetujui', 1, 'Budiono Siregar', NULL, '2025-12-24 01:50:27', '2025-12-24 01:54:00'),
(6, 1, 2, '2026-01-02', '2026-01-04', '08:00:00', '12:00:00', 'rapat himpunan', NULL, 'disetujui', 1, 'Budiono Siregar', NULL, '2025-12-24 01:59:17', '2025-12-28 15:21:26'),
(7, 1, 1, '2026-01-01', '2026-01-01', '08:00:00', '22:00:00', 'kjdhaskjhjdh', NULL, 'disetujui', 1, 'Budiono Siregar', NULL, '2025-12-31 08:01:31', '2025-12-31 08:02:26'),
(8, 1, 2, '2026-01-01', '2026-01-01', '16:00:00', '18:00:00', 'test', NULL, 'disetujui', 1, 'Budiono Siregar', NULL, '2025-12-31 08:06:09', '2025-12-31 08:08:10'),
(9, 2, 2, '2026-01-01', '2026-01-01', '16:00:00', '18:00:00', 'test', NULL, 'disetujui', 1, 'Budiono Siregar', NULL, '2025-12-31 08:07:28', '2025-12-31 08:08:21');

-- --------------------------------------------------------

--
-- Table structure for table `ruangan`
--

CREATE TABLE `ruangan` (
  `id` int(11) NOT NULL,
  `nama_ruangan` varchar(100) NOT NULL,
  `gedung` varchar(50) DEFAULT NULL,
  `kapasitas` int(11) DEFAULT NULL,
  `fasilitas` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status` enum('tersedia','maintenance') DEFAULT 'tersedia',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ruangan`
--

INSERT INTO `ruangan` (`id`, `nama_ruangan`, `gedung`, `kapasitas`, `fasilitas`, `gambar`, `status`, `created_at`) VALUES
(1, 'Ruang B-401', 'Gedung B', 40, 'Proyektor, AC, Whiteboard, Kursi dan meja', 'ruang_a201.jpg', 'tersedia', '2025-12-02 14:59:08'),
(2, 'Ruang B-301', 'Gedung B', 40, 'Proyektor, AC, Whiteboard, Kursi dan meja', 'ruang_b301.jpg', 'tersedia', '2025-12-02 14:59:08'),
(3, 'Ruang C-302', 'Gedung C', 40, 'Proyektor, AC, Whiteboard, Kursi dan meja', 'ruang_c302.jpg', 'tersedia', '2025-12-02 14:59:08'),
(4, 'Ruang D-303', 'Gedung D', 40, 'Proyektor, AC, Whiteboard, Kursi dan meja', 'ruang_d303.jpg', 'tersedia', '2025-12-02 14:59:08'),
(5, 'Ruang E-406', 'Gedung E', 40, 'Proyektor, AC, Whiteboard, Kursi dan meja', 'ruang_e406.jpg', 'tersedia', '2025-12-02 14:59:08'),
(6, 'Ruang AD-201', 'Gedung AD', 40, 'Proyektor, AC, Whiteboard, Kursi dan meja', 'ruang_ad201.jpg', 'maintenance', '2025-12-02 14:59:08'),
(7, 'Ruang T-003', 'Gedung T', 40, 'Proyektor, AC, Whiteboard, Kursi dan meja, PC', 'ruang_t003.jpg', 'tersedia', '2025-12-02 14:59:08');

-- --------------------------------------------------------

--
-- Table structure for table `staff_bop`
--

CREATE TABLE `staff_bop` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_bop`
--

INSERT INTO `staff_bop` (`id`, `username`, `nama`, `email`, `password`, `created_at`) VALUES
(1, 'staff_bop_1', 'Budiono Siregar', 'bop1@mercubuana.ac.id', 'Budiono_123', '2025-12-02 14:59:06');

-- --------------------------------------------------------

--
-- Table structure for table `staff_bsp`
--

CREATE TABLE `staff_bsp` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_bsp`
--

INSERT INTO `staff_bsp` (`id`, `username`, `nama`, `email`, `password`, `created_at`) VALUES
(1, 'staff_bsp_1', 'Joko Wijaya', 'bsp1@mercubuana.ac.id', 'Joko_567', '2025-12-02 14:59:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `prodi` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nim`, `nama`, `email`, `password`, `prodi`, `created_at`) VALUES
(1, '41523010065', 'Gofar Hilman', '41523010065@student.mercubuana.ac.id', 'Gofar_123', 'Teknik Informatika', '2025-12-02 14:59:05'),
(2, '41524010035', 'Siti Zubaedah', '41524010035@student.mercubuana.ac.id', 'Siti_456', 'Akuntansi', '2025-12-02 14:59:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `fasilitas`
--
ALTER TABLE `fasilitas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jadwal_perkuliahan`
--
ALTER TABLE `jadwal_perkuliahan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ruangan_id` (`ruangan_id`);

--
-- Indexes for table `peminjaman_fasilitas`
--
ALTER TABLE `peminjaman_fasilitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fasilitas_id` (`fasilitas_id`),
  ADD KEY `disetujui_oleh` (`disetujui_oleh`);

--
-- Indexes for table `peminjaman_ruangan`
--
ALTER TABLE `peminjaman_ruangan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `ruangan_id` (`ruangan_id`),
  ADD KEY `disetujui_oleh` (`disetujui_oleh`);

--
-- Indexes for table `ruangan`
--
ALTER TABLE `ruangan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_bop`
--
ALTER TABLE `staff_bop`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `staff_bsp`
--
ALTER TABLE `staff_bsp`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nim` (`nim`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `fasilitas`
--
ALTER TABLE `fasilitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `jadwal_perkuliahan`
--
ALTER TABLE `jadwal_perkuliahan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `peminjaman_fasilitas`
--
ALTER TABLE `peminjaman_fasilitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `peminjaman_ruangan`
--
ALTER TABLE `peminjaman_ruangan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `ruangan`
--
ALTER TABLE `ruangan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `staff_bop`
--
ALTER TABLE `staff_bop`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `staff_bsp`
--
ALTER TABLE `staff_bsp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `jadwal_perkuliahan`
--
ALTER TABLE `jadwal_perkuliahan`
  ADD CONSTRAINT `jadwal_perkuliahan_ibfk_1` FOREIGN KEY (`ruangan_id`) REFERENCES `ruangan` (`id`);

--
-- Constraints for table `peminjaman_fasilitas`
--
ALTER TABLE `peminjaman_fasilitas`
  ADD CONSTRAINT `peminjaman_fasilitas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `peminjaman_fasilitas_ibfk_2` FOREIGN KEY (`fasilitas_id`) REFERENCES `fasilitas` (`id`),
  ADD CONSTRAINT `peminjaman_fasilitas_ibfk_3` FOREIGN KEY (`disetujui_oleh`) REFERENCES `staff_bsp` (`id`);

--
-- Constraints for table `peminjaman_ruangan`
--
ALTER TABLE `peminjaman_ruangan`
  ADD CONSTRAINT `peminjaman_ruangan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `peminjaman_ruangan_ibfk_2` FOREIGN KEY (`ruangan_id`) REFERENCES `ruangan` (`id`),
  ADD CONSTRAINT `peminjaman_ruangan_ibfk_3` FOREIGN KEY (`disetujui_oleh`) REFERENCES `staff_bop` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

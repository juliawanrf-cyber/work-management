-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 21, 2026 at 10:38 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_work_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `pesan_chat`
--

CREATE TABLE `pesan_chat` (
  `id_chat` int(11) NOT NULL,
  `id_pengirim` int(11) NOT NULL,
  `id_penerima` int(11) DEFAULT NULL,
  `pesan` text NOT NULL,
  `waktu_kirim` datetime NOT NULL DEFAULT current_timestamp(),
  `status_baca` enum('Unread','Read') NOT NULL DEFAULT 'Unread'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pesan_chat`
--

INSERT INTO `pesan_chat` (`id_chat`, `id_pengirim`, `id_penerima`, `pesan`, `waktu_kirim`, `status_baca`) VALUES
(1, 32, 22, 'test', '2026-07-21 13:42:38', 'Read'),
(2, 22, NULL, 'Selamat Siang', '2026-07-21 13:42:39', 'Unread'),
(3, 32, NULL, 'test', '2026-07-21 13:44:33', 'Unread'),
(4, 14, NULL, 'Tes', '2026-07-21 14:06:55', 'Unread'),
(5, 14, 30, 'tes', '2026-07-21 14:07:12', 'Unread'),
(6, 14, 22, 'tes', '2026-07-21 14:07:22', 'Unread'),
(7, 14, 32, 'tes tekla', '2026-07-21 14:07:30', 'Read'),
(8, 14, 13, 'tes', '2026-07-21 14:07:47', 'Read'),
(9, 14, 20, 'tos', '2026-07-21 14:07:54', 'Unread'),
(10, 14, 9, 'admin tees', '2026-07-21 14:08:03', 'Unread'),
(11, 13, 14, 'tes 1 2 3', '2026-07-21 14:10:19', 'Read'),
(12, 18, NULL, 'ALL IN ARGENTINA', '2026-07-21 14:57:47', 'Unread'),
(13, 16, NULL, 'BESOK 5R BIL?', '2026-07-21 15:12:25', 'Unread'),
(14, 18, 32, 'INFO', '2026-07-21 15:26:40', 'Read');

-- --------------------------------------------------------

--
-- Table structure for table `spk`
--

CREATE TABLE `spk` (
  `id_spk` int(11) NOT NULL,
  `no_spk` varchar(50) NOT NULL,
  `nama_proyek` varchar(150) NOT NULL,
  `nama_client` varchar(150) DEFAULT NULL,
  `deskripsi_tugas` text NOT NULL,
  `tingkat_urgensi` enum('Normal','High','Urgent') NOT NULL DEFAULT 'Normal',
  `total_tonase` decimal(15,2) NOT NULL DEFAULT 0.00,
  `deadline` datetime DEFAULT NULL,
  `status` enum('Pending','On Progress','Paused','Completed') NOT NULL DEFAULT 'Pending',
  `tgl_input` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_mulai` datetime DEFAULT NULL,
  `tgl_selesai` datetime DEFAULT NULL,
  `id_admin` int(11) NOT NULL,
  `id_drafter` int(11) DEFAULT NULL,
  `link_drive` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spk`
--

INSERT INTO `spk` (`id_spk`, `no_spk`, `nama_proyek`, `nama_client`, `deskripsi_tugas`, `tingkat_urgensi`, `total_tonase`, `deadline`, `status`, `tgl_input`, `tgl_mulai`, `tgl_selesai`, `id_admin`, `id_drafter`, `link_drive`) VALUES
(21, 'S-JBT-00002-II-2026', 'New Priok Eastern Access', 'PT. PEMBANGUNAN PERUMAHAN (PERSERO) Tbk', '[Scope: GA, Modeling, Shop Drawing] Pekerjaan Struktur BAja Zona Daeat Box Girder', 'Normal', 1947356.00, '2026-06-30 15:28:00', 'On Progress', '2026-06-04 08:46:52', '2026-06-10 13:18:29', NULL, 9, 31, ''),
(22, 'S-SSB-00043-V-2026', 'Workshop TJI', 'PT. TRANSFORMER JAYA INDONESIA', '[Scope: GA, Modeling, Shop Drawing] Struktur Baja Workshop TJI', 'Normal', 195000.00, '2026-06-30 09:13:00', 'Completed', '2026-06-04 09:13:14', '2026-06-04 10:00:00', '2026-07-21 15:28:12', 9, 21, NULL),
(23, 'S-SSB-00026-II-2026', 'Conveyor Indexim Coalindo', 'PT. JAKARTA PRIMA CRANES', '[Scope: Modeling, Shop Drawing] Steel Structure Stacking and Reclaim Conveyor System', 'Normal', 191025.51, '2026-06-30 09:29:00', 'On Progress', '2026-06-04 09:29:33', '2026-07-16 16:46:55', NULL, 9, 33, NULL),
(24, 'S-KUM-00017-II-2026', '3 Hanggar Drone', 'PT, MAYAKSA MUGI MULIA', '[Scope: GA, Modeling, Shop Drawing] Hanggar Supadio, Surabaya dan Waytuba\\r\\nAssesories Baut dan Anchor', 'Normal', 931631.72, '2026-09-16 09:31:00', 'On Progress', '2026-06-04 09:31:27', '2026-06-09 09:25:01', NULL, 9, 18, NULL),
(25, 'S-KUM-00023-II-2026', 'PLTM TALAGA', 'PT. GRIDTECH INDONESIA', '[Scope: GA, Modeling, Shop Drawing] Expantion Join', 'Normal', 335.10, '2026-06-30 09:32:00', 'Completed', '2026-06-04 09:32:44', '2026-06-08 15:42:00', '2026-07-16 14:29:43', 9, 14, ''),
(26, 'S-KUM-00028-VIII-2025', 'Steel Structure Batch 1 CAA PK BD-1 PT. Chandra Asri Alkali', 'JO PT. PP (PERSERO) TBK - PT. SEVEN GATES INDONESIA', '[Scope: GA, Modeling, Shop Drawing] Steel Structure', 'Normal', 295143.80, '2026-06-30 09:34:00', 'On Progress', '2026-06-04 09:34:57', '2026-06-04 10:17:00', NULL, 9, 14, NULL),
(33, 'S-KUM-00033-IV-2026', 'PROYEK PEMBANGUNAN JETTY I BARU DI INTEGRATED TERMINAL MANGGIS', 'PT. WIJAYA KARYA (Persero) Tbk', 'PAKET PENGADAAN BARANG HOSE PACKAGE', 'Normal', 85135.00, '2026-10-16 11:04:00', 'Pending', '2026-06-12 11:04:36', NULL, NULL, 9, NULL, ''),
(34, 'S-SSB-00071-VI-2026', 'Additional Work Steel Structure OFPS Pertamina RU VI Balongan', 'PT. TIMAS SUPLINDO', 'Additional Work\r\n\r\nNote : SPK lebih besar dari BOM list ', 'High', 453.27, '2026-06-29 09:59:00', 'On Progress', '2026-06-17 09:59:18', '2026-06-18 08:10:00', NULL, 9, 17, ''),
(35, 'S-KUM-00060-VI-2026', 'Steel Structure Perpanjangan Dermaga 2, Pembangunan BSL 3 dan Conveyor (Ship Loader)', 'PT. NINDYA KARYA', 'All by DHJ', 'Normal', 264320.00, '2027-05-31 22:17:00', 'Pending', '2026-07-01 10:21:19', '2026-07-26 14:40:00', NULL, 25, NULL, 'Z:\\03. DATA\\04. FILE RECEIVED\\10. PT. NINDYA KARYA\\2026\\1. PROJECT SHIPLOADER PKT BONTANG'),
(36, 'S-KUM-00019-II-2026 OWJJ', 'ADD SPK KUM-00053', 'OBAYASHI-WIJAYA KARYA-JAYA KONSTRUKSI-JFE ENGINEER', 'Additional Work, Concrate Work, Grouting Work, Grating', 'High', 14132.48, '2026-08-31 14:29:00', 'On Progress', '2026-07-01 14:29:51', '2026-07-21 14:52:40', NULL, 9, 32, ''),
(37, 'S-KUM-00017-II-2026-ENG', 'Lanjutan Hanggar Drone spk S-KUM-00017-II-2026', 'PT. MAYAKSA MUGI MULIA', 'Tower 10m\r\nTower 18m', 'High', 56688.36, '2026-09-16 15:40:00', 'On Progress', '2026-07-16 15:42:11', '2026-07-21 14:56:29', NULL, 25, 13, ''),
(38, 'S-SSB-00031-IV-2026', 'Steel Structure Dermaga Gospier', 'PT. WIJAYA KARYA (Persero) Tbk', 'Steel Structure', 'Normal', 216637.00, '2026-10-31 09:19:00', 'On Progress', '2026-07-21 09:20:15', '2026-07-21 09:21:39', NULL, 9, 17, ''),
(39, '001-xxx', 'testspk', 'testspk', 'testspk', 'Urgent', 10.00, '2026-07-31 15:16:00', 'On Progress', '2026-07-21 15:16:20', '2026-07-21 15:16:00', NULL, 30, 32, '');

-- --------------------------------------------------------

--
-- Table structure for table `spk_progres`
--

CREATE TABLE `spk_progres` (
  `id_progres` int(11) NOT NULL,
  `id_spk` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `tonase_diambil` decimal(15,2) NOT NULL DEFAULT 0.00,
  `progres_ga` int(11) DEFAULT 0,
  `progres_modeling` int(11) DEFAULT 0,
  `tgl_update` timestamp NOT NULL DEFAULT current_timestamp(),
  `keterangan_kerja` text NOT NULL,
  `is_kerja_tambah` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spk_progres`
--

INSERT INTO `spk_progres` (`id_progres`, `id_spk`, `id_user`, `tonase_diambil`, `progres_ga`, `progres_modeling`, `tgl_update`, `keterangan_kerja`, `is_kerja_tambah`) VALUES
(1, 1, 5, 0.00, 0, 0, '2026-06-01 04:00:56', 'Hari ini saya mengerjakan bold and nut', 0),
(2, 1, 7, 0.00, 0, 0, '2026-06-01 04:10:58', 'Saya menyiapkan draw material', 0),
(3, 2, 7, 0.00, 0, 0, '2026-06-02 04:06:24', 'test 1', 0),
(4, 2, 5, 0.00, 0, 0, '2026-06-02 04:06:52', 'test 2', 0),
(5, 3, 5, 0.00, 0, 0, '2026-06-02 08:22:55', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh.', 0),
(6, 3, 5, 35.00, 0, 0, '2026-06-02 08:24:11', 'buat tiang ', 0),
(8, 4, 5, 0.00, 0, 0, '2026-06-02 09:28:53', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh.', 0),
(9, 4, 5, 20.00, 0, 0, '2026-06-02 09:29:31', 'tiang', 0),
(10, 4, 7, 4.00, 0, 0, '2026-06-02 09:30:13', 'tiang pju', 0),
(11, 5, 7, 0.00, 0, 0, '2026-06-02 14:39:59', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh.', 0),
(12, 7, 7, 0.00, 0, 0, '2026-06-02 14:40:02', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh.', 0),
(13, 6, 7, 0.00, 0, 0, '2026-06-02 14:40:05', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh.', 0),
(14, 8, 7, 0.00, 0, 0, '2026-06-02 14:41:06', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh.', 0),
(15, 10, 7, 0.00, 0, 0, '2026-06-02 14:56:31', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh.', 0),
(16, 11, 7, 0.00, 0, 0, '2026-06-03 02:23:26', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh.', 0),
(17, 11, 7, 70.00, 0, 0, '2026-06-03 02:24:14', 'tiang', 0),
(18, 11, 7, 50.00, 0, 0, '2026-06-03 02:25:19', 'tiang pju', 0),
(19, 4, 5, 50.00, 0, 0, '2026-06-03 02:27:43', 'tiang lanjutan', 0),
(20, 12, 5, 0.00, 0, 0, '2026-06-03 04:04:28', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh.', 0),
(21, 18, 5, 0.00, 0, 0, '2026-06-03 06:11:41', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(22, 12, 5, 0.00, 0, 0, '2026-06-03 06:19:20', 'Fokus Kerja Hari Ini (GA, Modeling):\n• GA: revisi layout\n• Modeling: modeling gambar tiang kucing customer', 0),
(23, 18, 5, 0.00, 0, 0, '2026-06-03 06:54:49', 'Fokus Kerja Hari Ini (GA):\n• GA: test', 0),
(24, 17, 5, 0.00, 0, 0, '2026-06-03 07:10:30', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(25, 19, 5, 0.00, 0, 0, '2026-06-03 07:28:09', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(26, 19, 5, 35.00, 0, 0, '2026-06-03 07:29:10', 'Fokus Kerja Hari Ini (GA, Modeling, Shop Drawing):\n• GA: saya membuat ranah landscape gambar\n• Modeling: saya membuat modeling tiang untuk pju\n• Shop Drawing (Kontribusi: 35 Kg): tiang pju', 0),
(27, 19, 7, 67.00, 0, 0, '2026-06-03 07:30:39', 'Fokus Kerja Hari Ini (GA, Modeling, Shop Drawing):\n• GA: merevisi layout yang salah\n• Modeling: pengerjaan batuan tiang pju\n• Shop Drawing (Kontribusi: 67 Kg): tiang pju', 0),
(30, 23, 13, 0.00, 0, 0, '2026-06-04 02:55:59', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(31, 24, 16, 0.00, 0, 0, '2026-06-04 02:56:49', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(32, 22, 21, 0.00, 0, 0, '2026-06-04 03:00:19', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(33, 23, 18, 904.00, 0, 0, '2026-06-04 03:01:15', 'Fokus Kerja Hari Ini (Modeling, Shop Drawing):\n• Modeling: Handrail RECLAIM TUNNEL TOWER\n• Shop Drawing (Kontribusi: 904 Kg): Shop drawing SINGLE PART', 0),
(34, 22, 21, 20649.00, 0, 0, '2026-06-04 03:02:33', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 20,649 Kg): SHOP DRAW CLADING & SAGROD', 0),
(35, 24, 16, 27214.00, 0, 0, '2026-06-04 03:03:48', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 27,214 Kg): mengeluarkan shop drawing kolom', 0),
(36, 22, 21, 174351.00, 0, 0, '2026-06-04 03:09:32', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 174,351 Kg): ALL SHOP DRAW', 0),
(37, 23, 13, 0.00, 0, 0, '2026-06-04 03:09:45', 'Fokus Kerja Hari Ini (GA):\r\n• GA', 0),
(38, 23, 13, 235.00, 0, 0, '2026-06-04 03:10:42', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 235 Kg): SINGLE PART', 0),
(39, 23, 15, 1589.00, 0, 0, '2026-06-04 03:11:25', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 1,589 Kg): SINGLE PART', 0),
(40, 22, 21, 0.00, 0, 0, '2026-06-04 03:15:59', 'Fokus Kerja Hari Ini (GA):\n• GA: UPDATE GA', 0),
(41, 26, 14, 0.00, 0, 0, '2026-06-04 03:17:10', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(42, 26, 14, 0.00, 0, 0, '2026-06-04 03:17:38', 'Fokus Kerja Hari Ini (Modeling):\n• Modeling: Modeling 3D', 0),
(43, 26, 14, 0.00, 0, 0, '2026-06-04 03:19:06', 'Fokus Kerja Hari Ini (Modeling):\n• Modeling: Modeling 3D', 0),
(44, 23, 13, 0.00, 0, 0, '2026-06-04 03:25:28', 'Fokus Kerja Hari Ini (Modeling):\n• Modeling: TAIL FRAME', 0),
(45, 25, 20, 0.00, 0, 0, '2026-06-04 04:06:49', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(46, 21, 20, 0.00, 0, 0, '2026-06-04 04:07:18', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(47, 23, 13, 0.00, 0, 0, '2026-06-04 04:38:33', 'Fokus Kerja Hari Ini (Modeling):\n• Modeling: GALLERY 2 TORQUE ARM', 0),
(48, 23, 13, 235.00, 0, 0, '2026-06-04 04:39:23', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 235 Kg): SHOP DRAWING', 0),
(51, 22, 21, 0.00, 0, 0, '2026-06-04 07:48:44', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 0,00 Kg): menunggu info revisi final', 0),
(53, 26, 14, 0.00, 0, 0, '2026-06-04 08:06:19', 'Fokus Kerja Hari Ini (GA):\n• GA: 2231.57', 0),
(54, 26, 14, 0.00, 0, 0, '2026-06-04 08:06:46', 'Fokus Kerja Hari Ini (GA):\n• GA: 2231.57', 0),
(55, 24, 15, 4653.00, 0, 0, '2026-06-04 09:33:27', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 4.653,00 Kg): SHOP DRAWING ASSY RAFTER CPA', 0),
(56, 24, 16, 71662.00, 0, 0, '2026-06-04 09:55:04', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 71.662,00 Kg): menurunkan drawing rafter grid line B-G', 0),
(57, 24, 17, 0.00, 0, 0, '2026-06-04 09:55:29', 'Fokus Kerja Hari Ini (Modeling):\n• Modeling: Penambahan Frame for flashing', 0),
(69, 24, 21, 0.00, 0, 0, '2026-06-05 03:22:57', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 0,00 Kg): HANGGAR CPA 21x22', 0),
(70, 26, 18, 480.00, 100, 0, '2026-06-05 03:40:09', 'Fokus Kerja Hari Ini (GA, Shop Drawing):\n• GA (Kontribusi: + 100%): CHECKERED PLATE CSW, CONTROL BOX 2 & CONTROL BOX PARKING LOT\n• Shop Drawing (Kontribusi: 480,00 Kg): SHOP DRAWING ASSY & SINGLE PART CHECKERED PLATE WHA & ELB', 0),
(73, 24, 16, 6241.00, 0, 0, '2026-06-05 08:34:02', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 6.241,00 Kg): mengeluarkan shop drawing tie beam & bracing', 0),
(76, 25, 20, 0.00, 0, 45, '2026-06-08 01:20:55', 'Fokus Kerja Hari Ini (Modeling):\n• Modeling (Kontribusi: + 45%): Revisi Ab7', 0),
(77, 26, 18, 256.00, 0, 0, '2026-06-08 01:31:15', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 256,00 Kg): SHOP DRAWING ASSY & SINGLE CHQ CSW', 0),
(78, 23, 18, 227.00, 0, 100, '2026-06-08 07:07:50', 'Fokus Kerja Hari Ini (Modeling, Shop Drawing):\n• Modeling (Kontribusi: + 100%): STAIR RTC\n• Shop Drawing (Kontribusi: 227,00 Kg): Shop drawing SINGLE PART', 0),
(80, 24, 13, 0.00, 0, 0, '2026-06-08 08:42:59', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(81, 24, 13, 7029.38, 0, 0, '2026-06-08 08:46:51', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 7.029,38 Kg): assembly kolom & rafter', 0),
(82, 24, 12, 0.00, 0, 0, '2026-06-08 08:49:10', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(83, 24, 16, 19.32, 0, 0, '2026-06-08 09:21:26', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 19,32 Kg): mengeluarkan shop drawing purlin & beam', 0),
(84, 26, 18, 581.00, 0, 0, '2026-06-09 02:24:40', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 581,00 Kg): SHOP DRAWING ASSY & SINGLE CHQ ELB, CB2 & CB4', 0),
(85, 24, 18, 0.00, 0, 0, '2026-06-09 02:25:01', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(86, 24, 12, 1848.08, 0, 0, '2026-06-09 08:53:54', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 1.848,08 Kg): WIND BRACE, VERT BRACE, SUPPORT GUTTER, PURLIN, GIRT, STRUT BEAM', 0),
(87, 24, 13, 11489.98, 0, 0, '2026-06-09 08:54:37', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 11.489,98 Kg): assembly tie beam, purlin & wind bracing', 0),
(88, 24, 15, 15525.50, 0, 0, '2026-06-09 09:03:34', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 15.525,50 Kg): SHOP DRAWING ASSY WIND BRACING, TIE BEAM, DAN BEAM HANGGAR AL', 0),
(89, 21, 14, 0.00, 0, 0, '2026-06-09 10:18:12', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(90, 24, 12, 42320.72, 0, 0, '2026-06-10 01:45:15', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 42.320,72 Kg): COLUMN, RAFTER', 0),
(91, 21, 20, 0.00, 0, 7, '2026-06-10 01:52:34', 'Fokus Kerja Hari Ini (Modeling):\n• Modeling (Kontribusi: + 7%): Master Girder Interior & Eksterior', 0),
(92, 21, 31, 0.00, 0, 0, '2026-06-10 06:18:29', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(95, 24, 18, 0.00, 0, 100, '2026-06-10 10:05:57', 'Fokus Kerja Hari Ini (Modeling):\n• Modeling (Kontribusi: + 100%): CPA 2', 0),
(96, 24, 18, 31290.00, 0, 0, '2026-06-11 07:45:27', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 31.290,00 Kg): SHOP DRAWING ASSY & SINGLE CPA2', 0),
(98, 25, 20, 0.00, 0, 55, '2026-06-12 04:11:24', 'Fokus Kerja Hari Ini (Modeling):\n• Modeling (Kontribusi: + 55%): Revisi Ab7', 0),
(99, 25, 20, 0.00, 100, 0, '2026-06-12 04:11:41', 'Fokus Kerja Hari Ini (GA):\n• GA (Kontribusi: + 100%): Revisi GA-07 Detail AB 7', 0),
(100, 21, 20, 894025.34, 0, 0, '2026-06-12 04:17:07', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 894.025,34 Kg): Box Girder, Diafragma, Splice Plate', 0),
(101, 24, 17, 131.00, 0, 0, '2026-06-12 08:24:05', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 131,00 Kg): Shop dwg tambahan purlin', 0),
(102, 24, 16, 2257.32, 0, 0, '2026-06-12 09:03:32', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 2.257,32 Kg): mengeluarkan shop drawing wind bracing', 0),
(103, 24, 15, 26404.75, 0, 0, '2026-06-12 09:28:41', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 26.404,75 Kg): SHOP DRAWING ASSY GUTTER DAN PURLIN HANGGAR WAYTUBA', 0),
(104, 23, 18, 2508.69, 0, 0, '2026-06-17 00:59:03', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 2.508,69 Kg): Shop drawing SINGLE PART', 0),
(105, 25, 20, 335.10, 0, 0, '2026-06-17 01:09:18', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 335,10 Kg): Revisi pipa Y (close)', 0),
(106, 23, 18, 2495.85, 0, 0, '2026-06-17 06:23:53', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 2.495,85 Kg): Shop drawing SINGLE PART', 0),
(107, 34, 17, 0.00, 0, 0, '2026-06-18 01:10:29', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(108, 34, 17, 0.00, 0, 100, '2026-06-18 01:11:47', 'Fokus Kerja Hari Ini (Modeling):\n• Modeling (Kontribusi: + 100%): SI penambahan material untuk Bangunan HCFM', 0),
(109, 34, 17, 452.48, 0, 0, '2026-06-18 01:17:32', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 452,48 Kg): Shop drawing loose part finish', 0),
(110, 23, 18, 412.21, 0, 0, '2026-06-22 09:00:52', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 412,21 Kg): Shop drawing SINGLE PART', 0),
(111, 23, 18, 9616.51, 0, 0, '2026-06-25 10:11:49', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 9.616,51 Kg): Shop drawing SINGLE PART', 0),
(112, 24, 16, 7280.00, 0, 0, '2026-06-26 01:12:17', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 7.280,00 Kg): mengeluarkan shop drawing', 0),
(113, 23, 18, 6114.40, 0, 0, '2026-06-30 05:29:52', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 6.114,40 Kg): Shop drawing SINGLE PART', 0),
(114, 24, 16, 0.00, 100, 0, '2026-06-30 06:58:27', 'Fokus Kerja Hari Ini (GA):\n• GA (Kontribusi: + 100%): UPDATE GA DWG WAYTUBA', 0),
(115, 36, 17, 0.00, 0, 0, '2026-07-01 07:36:28', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(116, 36, 17, 0.00, 100, 0, '2026-07-01 07:37:24', 'Fokus Kerja Hari Ini (GA):\n• GA (Kontribusi: + 100%): GA Proses Approval ke Client', 0),
(117, 36, 17, 0.00, 0, 100, '2026-07-01 07:38:18', 'Fokus Kerja Hari Ini (Modeling):\n• Modeling (Kontribusi: + 100%): Modeling Coarse Screen', 0),
(118, 23, 18, 2534.76, 0, 0, '2026-07-03 01:06:23', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 2.534,76 Kg): Shop drawing SINGLE PART', 0),
(119, 21, 20, 0.00, 0, 93, '2026-07-16 06:22:07', 'Fokus Kerja Hari Ini (Modeling):\n• Modeling (Kontribusi: + 93%): Selesai model P51.52', 0),
(120, 24, 16, 299.72, 0, 0, '2026-07-16 06:23:52', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 299,72 Kg): mengerjakan shop drawing assy handrail', 0),
(121, 24, 18, 10686.50, 0, 0, '2026-07-16 06:24:48', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 10.686,50 Kg): SHOP DRAWING TOWER 10M', 0),
(122, 21, 14, 307258.11, 0, 0, '2026-07-16 07:05:17', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 307.258,11 Kg): Assy Drawing & Single Drawing', 0),
(123, 26, 14, 0.00, 0, 100, '2026-07-16 07:28:35', 'Fokus Kerja Hari Ini (Modeling):\n• Modeling (Kontribusi: + 100%): Revisi Model Main Gate', 0),
(124, 26, 14, 3465.37, 0, 0, '2026-07-16 07:29:11', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 3.465,37 Kg): Revisi GA Drawing', 0),
(125, 37, 18, 0.00, 0, 0, '2026-07-16 08:42:35', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(126, 37, 18, 0.00, 0, 50, '2026-07-16 08:42:55', 'Fokus Kerja Hari Ini (Modeling):\n• Modeling (Kontribusi: + 50%): TOWER 10M', 0),
(127, 37, 18, 20507.96, 0, 0, '2026-07-16 08:43:39', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 20.507,96 Kg): SHOP DRAWING ASSY & SINGLE TOWER10', 0),
(128, 37, 18, 0.00, 0, 40, '2026-07-16 08:44:10', 'Fokus Kerja Hari Ini (Modeling):\n• Modeling (Kontribusi: + 40%): TOWER 18M', 0),
(129, 23, 33, 0.00, 0, 0, '2026-07-16 09:46:55', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(130, 22, 21, 0.00, 100, 0, '2026-07-17 01:32:17', 'Fokus Kerja Hari Ini (GA):\n• GA (Kontribusi: + 100%): UPDATE GA', 0),
(131, 22, 21, 0.00, 0, 100, '2026-07-17 01:32:52', 'Fokus Kerja Hari Ini (Modeling):\n• Modeling (Kontribusi: + 100%): UPDATE MODEL', 0),
(132, 37, 32, 0.00, 0, 0, '2026-07-17 09:10:25', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(133, 37, 16, 0.00, 0, 0, '2026-07-21 02:16:32', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(134, 21, 20, 273325.00, 0, 0, '2026-07-21 02:18:47', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 273.325,00 Kg): Girder segmen 1', 0),
(135, 38, 17, 0.00, 0, 0, '2026-07-21 02:21:39', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(136, 21, 14, 288340.98, 0, 0, '2026-07-21 02:27:52', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 288.340,98 Kg): Single Drawing Girder P51-52', 0),
(137, 26, 14, 2781.85, 0, 0, '2026-07-21 02:28:42', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 2.781,85 Kg): Assy Drawing & Single Drawing', 0),
(138, 37, 32, 0.00, 0, 1, '2026-07-21 07:02:35', 'Fokus Kerja Hari Ini (Modeling):\n• Modeling (Kontribusi: + 1%): Pembuatan =Modeling HC-11', 0),
(139, 36, 32, 0.00, 0, 0, '2026-07-21 07:52:40', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(140, 37, 13, 0.00, 0, 0, '2026-07-21 07:56:29', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(141, 37, 13, 5678.60, 0, 0, '2026-07-21 08:00:04', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 5.678,60 Kg): shop drawing kolom, handrail, bracing, stair.', 0),
(142, 39, 32, 0.00, 0, 0, '2026-07-21 08:16:38', 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0),
(143, 39, 32, 0.00, 100, 0, '2026-07-21 08:17:05', 'Fokus Kerja Hari Ini (GA):\n• GA (Kontribusi: + 100%): pju', 0),
(144, 39, 32, 0.00, 0, 100, '2026-07-21 08:17:15', 'Fokus Kerja Hari Ini (Modeling):\n• Modeling (Kontribusi: + 100%): pju', 0),
(145, 39, 32, 10.00, 0, 0, '2026-07-21 08:17:26', 'Fokus Kerja Hari Ini (Shop Drawing):\n• Shop Drawing (Kontribusi: 10,00 Kg): pju', 0),
(146, 39, 32, 10.00, 10, 10, '2026-07-21 08:18:14', '=== [PEKERJAAN TAMBAH / ADDENDUM (VO)] ===\nFokus Kerja Hari Ini (GA, Modeling, Shop Drawing):\n• GA (Kontribusi: + 10%): test\n• Modeling (Kontribusi: + 10%): test\n• Shop Drawing (Kontribusi: 10,00 Kg): test', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','drafter','manager') NOT NULL,
  `status_akun` enum('Pending','Approved') NOT NULL DEFAULT 'Pending',
  `last_active` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `username`, `password`, `nama_lengkap`, `role`, `status_akun`, `last_active`) VALUES
(4, 'kiki', '$2y$10$Na4cBKLbzAZXxGzX7BxlTOWoCluxeTHvIpbJi.AuSDws8LzhKAUXu', 'kikiIT', 'manager', 'Approved', NULL),
(9, 'Lova ', '$2y$10$FFAAhnxqIDDOAVexhBYhs.DuUnH.VlUxH.fp81fdLcoRVE3V59Eqq', 'Lova Adhetya Veronita', 'admin', 'Approved', NULL),
(12, 'BB', '$2y$10$Y6uP7LMUSHfLwmftUTSzeOelFgO71c2lWcby.h3ZgtHMKlwypi51G', 'BERNARD BAHJAH', 'drafter', 'Approved', '2026-07-21 15:38:18'),
(13, 'leo picassona', '$2y$10$Hfoml/dc5MtloPEjnxwu3OqiLeNVDhTQiLdgC/dkMJlkYBlssqa9u', 'LEO PICASSONA', 'drafter', 'Approved', '2026-07-21 15:38:32'),
(14, 'Ian Kastela', '$2y$10$UZxR6lao2SYs0t7DOyVaMOevylCcEKxZzDqnep5PaaPlQ/Ewe166a', 'Ian Krisyanto', 'drafter', 'Approved', '2026-07-21 15:37:54'),
(15, 'syabilape', '$2y$10$LDf1rFJeFTtmuNb0GYjRXuZmGOjlDIrYYZFG.pxAKH/pEoczM82tq', 'SYABILA PUTRI ERDIANI', 'drafter', 'Approved', NULL),
(16, 'zdnngraha', '$2y$10$r57p.KXrWfaWkKXk9IrDi.cUvL17eMC4gpSI3.t602odtyXL10UCG', 'MUHAMMAD ZIDAN NUGRAHA', 'drafter', 'Approved', '2026-07-21 15:38:31'),
(17, 'op', '$2y$10$Xc4xatMlLCRL.xf6kEGSsu5H.mlZDkzLVXa4GQedjr5tYsNIX3562', 'ONDI PARENTA', 'drafter', 'Approved', NULL),
(18, 'fabil.dp', '$2y$10$XScfFPQRUvjgH307juMfXuDg1RP/LN23GeLTH/JoCU7XIh82NYM1e', 'FABIL DAKA PUTRA', 'drafter', 'Approved', '2026-07-21 15:38:29'),
(19, 'Riyan', '$2y$10$7dwBq1EHIDTgly8SMQ681es3vvZMthO40yPYdR.34OsWUfy5tZbH6', 'Riyan Adi Prasetyo', 'drafter', 'Approved', NULL),
(20, 'aris', '$2y$10$y4eC5JeXTZp74tAi/a/xPumkAh1sqbEhOVxQ/kVVS2z62hFFCUNqK', 'ARIS NUGROHO', 'drafter', 'Approved', NULL),
(21, 'nuryadi', '$2y$10$t6.2eBrAlgYgU7zpzPwHNeCPEmMd88W8t4hgIOUKjS8TlOA5a5CPa', 'NURYADI', 'drafter', 'Approved', '2026-07-21 15:38:12'),
(22, 'i.gunawan', '$2y$10$ADSlxDxFm/eU45Wpf6HM/eN7bfGv5sricZt1sy2srIdOgUL6jWexK', 'INDRA GUNAWAN', 'manager', 'Approved', '2026-07-21 13:47:10'),
(25, 'dyh.ayu', '$2y$10$SRE3foHLX0gkI6VNXrfUcOtm6Z2OcrNipR8zBCgsrQ5h9kkf5SZrq', 'Diyah Ayu Saputri', 'admin', 'Approved', NULL),
(27, 'Ferry', '$2y$10$AQllvidaj3wh6aNcp3HsAe7fLDuXyefNWiZQWPKiWh39eDRVgevN6', 'Ferry Subagja', 'manager', 'Approved', NULL),
(30, 'kiki1', '$2y$10$gkbNRBPkYM7YS3bOrXo0puts2UjAKATl4fY6dLy9rIC/xyUxKWGky', 'kiki1', 'admin', 'Approved', '2026-07-21 15:19:30'),
(31, 'testdrafter', '$2y$10$t8Z4NjzoIeiA1A3XDuiu7O2a.rvCn5jcR/HktvUL4gWiHJwqUQRM6', 'testdrafter', 'drafter', 'Approved', NULL),
(32, 'testdrafter1', '$2y$10$L6DOFft4BrlI/gSjQQTkYOqVu3MbWVFU7oK0IAB7XrdX74Lcgop8y', 'testdrafter1', 'drafter', 'Approved', '2026-07-21 15:37:49'),
(33, 'Riyan Adi Prasetyo', '$2y$10$ItMen1dxhM8uZVJCPYhQKuwYp/4gQIh1EoOOqupq59HWVTRyybLia', 'RIYAN ADI PRASETYO', 'drafter', 'Approved', '2026-07-21 14:16:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pesan_chat`
--
ALTER TABLE `pesan_chat`
  ADD PRIMARY KEY (`id_chat`),
  ADD KEY `id_pengirim` (`id_pengirim`),
  ADD KEY `id_penerima` (`id_penerima`);

--
-- Indexes for table `spk`
--
ALTER TABLE `spk`
  ADD PRIMARY KEY (`id_spk`),
  ADD UNIQUE KEY `no_spk` (`no_spk`),
  ADD KEY `id_admin` (`id_admin`),
  ADD KEY `id_drafter` (`id_drafter`);

--
-- Indexes for table `spk_progres`
--
ALTER TABLE `spk_progres`
  ADD PRIMARY KEY (`id_progres`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pesan_chat`
--
ALTER TABLE `pesan_chat`
  MODIFY `id_chat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `spk`
--
ALTER TABLE `spk`
  MODIFY `id_spk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `spk_progres`
--
ALTER TABLE `spk_progres`
  MODIFY `id_progres` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=147;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `spk`
--
ALTER TABLE `spk`
  ADD CONSTRAINT `spk_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE,
  ADD CONSTRAINT `spk_ibfk_2` FOREIGN KEY (`id_drafter`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

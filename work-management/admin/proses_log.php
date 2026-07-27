<?php
session_start();
require '../config.php';

// Proteksi halaman: Hanya Admin yang boleh mengeksekusi
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// 1. PENANGANAN AKSI BERBASIS POST (PROSES EDIT LOG)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['aksi']) && $_GET['aksi'] === 'edit') {
    $id_progres = intval($_POST['id_progres']);
    $tonase_diambil = intval($_POST['tonase_diambil']);
    $keterangan_kerja = mysqli_real_escape_string($conn, $_POST['keterangan_kerja']);

    if ($id_progres > 0 && !empty($keterangan_kerja)) {
        $query = "UPDATE spk_progres SET tonase_diambil = $tonase_diambil, keterangan_kerja = '$keterangan_kerja' WHERE id_progres = $id_progres";
        $conn->query($query);
    }
}

// 2. PENANGANAN AKSI BERBASIS GET (PROSES HAPUS LOG)
if (isset($_GET['aksi']) && $_GET['aksi'] === 'hapus' && isset($_GET['id'])) {
    $id_progres = intval($_GET['id']);
    
    if ($id_progres > 0) {
        $query = "DELETE FROM spk_progres WHERE id_progres = $id_progres";
        $conn->query($query);
    }
}

// Kembalikan Admin ke halaman dashboard utama
header("Location: dashboard.php");
exit;
?>
<?php
session_start();
require '../config.php';

// Proteksi halaman: Hanya Admin yang boleh masuk
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // 1. Bersihkan dulu log harian dari tabel spk_progres agar id_spk tidak terkunci (Foreign Key Error)
    $conn->query("DELETE FROM spk_progres WHERE id_spk = $id");
    
    // 2. Eksekusi penghapusan pada tabel data utama SPK
    $conn->query("DELETE FROM spk WHERE id_spk = $id");
}

// Balikkan halaman ke dashboard admin setelah eksekusi selesai
header("Location: dashboard.php");
exit;
?>
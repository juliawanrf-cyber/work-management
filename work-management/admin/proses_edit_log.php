<?php
session_start();
require '../config.php';

// Proteksi halaman: Hanya Admin yang boleh masuk
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['update_log'])) {
    $id_progres = intval($_POST['id_progres']);
    $id_spk = intval($_POST['id_spk']);
    $keterangan_kerja = mysqli_real_escape_string($conn, trim($_POST['keterangan_kerja']));
    
    // Menangkap input desimal untuk volume Shop Drawing (KG)
    $tonase_diambil = (float)$_POST['tonase_diambil'];
    
    // KUNCI UTAMA SINKRONISASI: Menangkap input angka persen baru dari form modal admin
    $progres_ga = intval($_POST['progres_ga']);
    $progres_modeling = intval($_POST['progres_modeling']);

    if ($id_progres > 0 && $id_spk > 0) {
        // Query update yang diselaraskan dengan kolom progres baru
        $query_update = "UPDATE spk_progres 
                         SET tonase_diambil = $tonase_diambil, 
                             progres_ga = $progres_ga, 
                             progres_modeling = $progres_modeling, 
                             keterangan_kerja = '$keterangan_kerja' 
                         WHERE id_progres = $id_progres";
        
        if ($conn->query($query_update)) {
            echo "<script>alert('Log kontribusi drafter dan persentase berhasil diperbarui!'); window.location='detail_spk.php?id=$id_spk';</script>";
            exit;
        } else {
            echo "<script>alert('Gagal mengoreksi data: " . $conn->error . "'); window.location='detail_spk.php?id=$id_spk';</script>";
            exit;
        }
    }
}
header("Location: dashboard.php");
exit;
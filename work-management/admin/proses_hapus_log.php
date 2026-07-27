<?php
session_start();
require '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$id_log = isset($_GET['id_log']) ? intval($_GET['id_log']) : 0;
$id_spk = isset($_GET['id_spk']) ? intval($_GET['id_spk']) : 0;

if ($id_log > 0 && $id_spk > 0) {
    $query_delete = "DELETE FROM spk_progres WHERE id_progres = $id_log";
    
    if ($conn->query($query_delete)) {
        echo "<script>alert('Log progres terpilih berhasil dihapus permanent dari sistem!'); window.location='detail_spk.php?id=$id_spk';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal menghapus log data: " . $conn->error . "'); window.location='detail_spk.php?id=$id_spk';</script>";
        exit;
    }
}

header("Location: dashboard.php");
exit;
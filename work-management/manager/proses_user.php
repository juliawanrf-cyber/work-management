<?php
session_start();
require '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'manager') {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $aksi = $_GET['aksi'];

    if ($aksi === 'approve') {
        $conn->query("UPDATE users SET status_akun = 'Approved' WHERE id_user = $id");
    } elseif ($aksi === 'tolak' || $aksi === 'hapus') {
        $conn->query("DELETE FROM users WHERE id_user = $id");
    }
}

header("Location: users.php");
exit;
?>
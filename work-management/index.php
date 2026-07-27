<?php
session_start();

// Jika belum melakukan otentikasi login, paksa ke halaman login
if (!isset($_SESSION['login'])) {
    header("Location: login");
    exit;
}

// Jika sudah login, distribusikan ke dashboard masing-masing divisi
if ($_SESSION['role'] == 'admin') {
    header("Location: admin/dashboard");
} elseif ($_SESSION['role'] == 'drafter') {
    header("Location: drafter/dashboard");
} elseif ($_SESSION['role'] == 'manager') {
    header("Location: manager/dashboard");
}
exit;
?>
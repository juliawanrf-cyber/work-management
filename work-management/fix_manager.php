<?php
require 'config.php';

// Membuat hash asli dari PHP kamu untuk password 'manager123'
$password_asli = password_hash('manager123', PASSWORD_DEFAULT);

// Update password milik manager1 di database
$query = "UPDATE users SET password = '$password_asli' WHERE username = 'manager1'";

if ($conn->query($query)) {
    echo "<h3>Sip, Sukses!</h3>";
    echo "Password untuk <strong>manager1</strong> sudah diperbarui dengan hash yang valid.<br>";
    echo "Silakan <strong>hapus file fix_manager.php ini</strong> demi keamanan, lalu coba login kembali di halaman login.";
} else {
    echo "Gagal memperbarui: " . $conn->error;
}
?>
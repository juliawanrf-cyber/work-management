<?php
require 'config.php';

echo "<h2>🔧 MEMULAI DIAGNOSIS & RESET MANAGER 🔧</h2>";

// 1. Memastikan kolom password di database berukuran 255 karakter (anti-terpotong)
$alter = $conn->query("ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NOT NULL");
if ($alter) {
    echo "✅ Langkah 1: Struktur tabel 'users' berhasil diamankan (VARCHAR 255).<br>";
} else {
    echo "❌ Langkah 1 Gagal: " . $conn->error . "<br>";
}

// 2. Bersihkan username manager1 yang lama agar tidak duplikat/bertabrakan
$conn->query("DELETE FROM users WHERE username = 'manager1'");
echo "✅ Langkah 2: Pembersihan data lama selesai.<br>";

// 3. Membuat hash password baru untuk 'manager123'
$password_baru = password_hash('manager123', PASSWORD_DEFAULT);

// 4. Masukkan kembali akun manager yang baru dan bersih
$query_insert = "INSERT INTO users (username, password, nama_lengkap, role, status_akun) 
                 VALUES ('manager1', '$password_baru', 'Ir. Hermawan (Manager Teknik)', 'manager', 'Approved')";

if ($conn->query($query_insert)) {
    echo "✅ Langkah 3: Akun <strong>manager1</strong> berhasil dibuat ulang!<br>";
} else {
    echo "❌ Langkah 3 Gagal: " . $conn->error . "<br>";
}

echo "<hr>";
echo "<h3>🧪 PENGUJIAN LANGSUNG DI SYSTEM:</h3>";

// 5. Kita tes langsung di file ini, apakah PHP mengenali password-nya
$cek = $conn->query("SELECT * FROM users WHERE username = 'manager1'");
$user = $cek->fetch_assoc();

if (password_verify('manager123', $user['password'])) {
    echo "<h3 style='color: green;'>🎉 BERHASIL TOTAL! Enkripsi PHP cocok 100%.</h3>";
    echo "Silakan kembali ke halaman <a href='login.php'><strong>login.php</strong></a> dan masukkan:<br>";
    echo "Username: <strong>manager1</strong><br>Password: <strong>manager123</strong>";
} else {
    echo "<h3 style='color: red;'>❌ GAGAL! Enkripsi masih belum cocok. Ada masalah pada internal PHP server.</h3>";
}
?>
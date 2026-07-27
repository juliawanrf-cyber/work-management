<?php
session_start();
header('Content-Type: application/json');
require 'config.php'; // Hubungkan ke file koneksi database lokal Anda

// Proteksi Akses: Harus sudah melewati proses login aplikasi
if (!isset($_SESSION['login'])) {
    echo json_encode(['balasan' => 'Silakan login terlebih dahulu untuk menggunakan fitur asisten virtual.']);
    exit;
}

$pesan_original = isset($_POST['pesan']) ? trim($_POST['pesan']) : '';
$pesan_user = strtolower($pesan_original);
$nama_user = $_SESSION['nama'];
$role_user = $_SESSION['role'];

if (empty($pesan_user)) {
    echo json_encode(['balasan' => 'Ada yang bisa saya bantu bapak/ibu?']);
    exit;
}

$balasan = "";

// =========================================================================
// JALUR 1: DATA INSTAN (LANGSUNG COCOKKAN KE DATABASE MYSQL LOKAL)
// =========================================================================

// Skenario A: Info Sambutan Awal
if (strpos($pesan_user, 'halo') !== false || strpos($pesan_user, 'hai') !== false) {
    $balasan = "Halo <strong>$nama_user</strong>! Saya adalah Asisten Virtual Work Management Platform. Anda masuk sebagai <strong>" . strtoupper($role_user) . "</strong>. Ada data riel SPK yang ingin Anda tanyakan dari database kantor hari ini?";
} 

// Skenario B: Deskripsi Dasar Mengenai Program
elseif (strpos($pesan_user, 'program apa') !== false || strpos($pesan_user, 'aplikasi apa') !== false || strpos($pesan_user, 'tentang program') !== false) {
    $balasan = "Ini adalah <strong>Work Management Platform Engineering (2026)</strong> milik <strong>PT Duta Hita Jaya</strong>. Aplikasi ini dibangun menggunakan PHP Native, MySQL, Bootstrap 5, dan Chart.js untuk mendistribusikan Surat Perintah Kerja (SPK), memantau alokasi beban kerja (satuan Kilogram/Kg), mendeteksi delay (Pause), kontrol akun baru oleh Manager, serta menyajikan visualisasi data leaderboard kontribusi divisi.";
}

// Skenario C: Pelacakan Kode SPK Spesifik menggunakan Regular Expression (RegEx)
elseif (preg_match('/spk-\d+-\d+/i', $pesan_user, $matches) || (strpos($pesan_user, 'lacak') !== false && preg_match('/\d+/', $pesan_user))) {
    preg_match('/spk-\d+-\d+/i', $pesan_user, $matches_spk);
    $no_spk = !empty($matches_spk) ? strtoupper($matches_spk[0]) : '';
    
    if (empty($no_spk)) {
        // Fallback jika user hanya menuliskan angka (misal: "lacak 1") -> diconvert ke format SPK-2026-001
        preg_match('/\d+/', $pesan_user, $matches_num);
        if(!empty($matches_num)) $no_spk = "SPK-2026-" . sprintf("%03d", $matches_num[0]);
    }

    if (!empty($no_spk)) {
        $query = $conn->query("SELECT spk.*, users.nama_lengkap AS nama_drafter FROM spk 
                               LEFT JOIN users ON spk.id_drafter = users.id_user 
                               WHERE spk.no_spk = '$no_spk'");
        
        if ($query && $query->num_rows > 0) {
            $data = $query->fetch_assoc();
            $id_spk = $data['id_spk'];
            
            // Hitung jumlahan progres beban yang sudah diinput drafter di tabel spk_progres
            $prog_query = $conn->query("SELECT COALESCE(SUM(tonase_diambil), 0) AS total_kilo FROM spk_progres WHERE id_spk = $id_spk");
            $progress_kilo = $prog_query->fetch_assoc()['total_kilo'];
            $persen = ($data['total_tonase'] > 0) ? round(($progress_kilo / $data['total_tonase']) * 100) : 0;

            if ($data['id_drafter'] == NULL) {
                $balasan = "Berkas <strong>$no_spk</strong> (Proyek: " . $data['nama_proyek'] . ") dengan target kapasitas <strong>" . number_format($data['total_tonase']) . " Kg</strong> saat ini <strong>belum diambil</strong>. Status: Mengantre (Pending).";
            } else {
                $balasan = "Berkas <strong>$no_spk</strong> (Proyek: " . $data['nama_proyek'] . ") sedang ditangani oleh Drafter <strong>" . $data['nama_drafter'] . "</strong>.📊 <b>Status Terkini:</b> " . strtoupper($data['status']) . "⚖️ <b>Beban Progres:</b> " . number_format($progress_kilo) . " / " . number_format($data['total_tonase']) . " Kg (" . $persen . "% Selesai).";
            }
        } else {
            $balasan = "Maaf, nomor berkas dokumen <strong>$no_spk</strong> tidak ditemukan dalam database sistem.";
        }
    }
}

// Skenario D: Tarik Daftar Antrean Pending Langsung dari Database
elseif (strpos($pesan_user, 'antrean') !== false || strpos($pesan_user, 'belum diambil') !== false || strpos($pesan_user, 'tugas baru') !== false) {
    $query = $conn->query("SELECT no_spk, nama_proyek, total_tonase FROM spk WHERE status = 'Pending' ORDER BY tingkat_urgensi = 'Urgent' DESC");
    if ($query && $query->num_rows > 0) {
        $balasan = "Berikut adalah daftar tugas di antrean yang belum diambil oleh tim Drafter:<ul class='mb-0 mt-1'>";
        while ($row = $query->fetch_assoc()) {
            $balasan .= "<li><strong>" . $row['no_spk'] . "</strong> - " . $row['nama_proyek'] . " (" . number_format($row['total_tonase']) . " Kg)</li>";
        }
        $balasan .= "</ul>";
    } else {
        $balasan = "Saat ini antrean SPK kosong bapak. Semua tugas sudah diklaim masuk meja kerja aktif produksi.";
    }
}

// Skenario E: Cek Proyek Berjalan Aktif (Status: On Progress)
elseif (strpos($pesan_user, 'sedang berjalan') !== false || strpos($pesan_user, 'progres aktif') !== false || strpos($pesan_user, 'on progress') !== false) {
    $query = $conn->query("SELECT spk.no_spk, spk.nama_proyek, users.nama_lengkap FROM spk 
                           JOIN users ON spk.id_drafter = users.id_user 
                           WHERE spk.status = 'On Progress'");
    if ($query && $query->num_rows > 0) {
        $balasan = "Berikut adalah proyek aktif saat ini (On Progress):<ul class='mb-0 mt-1'>";
        while ($row = $query->fetch_assoc()) {
            $balasan .= "<li><strong>" . $row['no_spk'] . "</strong> (" . $row['nama_proyek'] . ") &rarr; Drafter: <b>" . $row['nama_lengkap'] . "</b></li>";
        }
        $balasan .= "</ul>";
    } else {
        $balasan = "Saat ini tidak ada pengerjaan gambar teknik proyek yang berstatus On Progress di workshop.";
    }
}

// =========================================================================
// JALUR 2: INTEGRASI GOOGLE GEMINI AI (KONSULTASI LOGIKA, CODE & KATA ACAK)
// =========================================================================
else {
    // 🔴 MASUKKAN GOOGLE GEMINI API KEY ANDA DI SINI
    $api_key = "GANTI_DENGAN_API_KEY_GEMINI_ANDA"; 
    
    if (empty($api_key) || $api_key === "GANTI_DENGAN_API_KEY_GEMINI_ANDA") {
        // Fallback Simulasi Pintar Offline (Mencegah tampilan kosong/gantilah api key saat diketik "test")
        if (strpos($pesan_user, 'hitung') !== false || strpos($pesan_user, 'rumus') !== false || strpos($pesan_user, 'sisa') !== false) {
            $balasan = "Untuk menghitung sisa target beban proyek dalam satuan <b>Kg</b>, rumusan kodenya adalah: <code class='d-block p-2 bg-dark text-white rounded my-1'>Sisa = Total Volume (Tabel SPK) - Akumulasi Diambil (Tabel SPK Progres)</code>";
        } elseif (strpos($pesan_user, 'database') !== false || strpos($pesan_user, 'tabel') !== false || strpos($pesan_user, 'relasi') !== false) {
            $balasan = "Database sistem ini mengikat 3 tabel inti: <b>users</b> (role), <b>spk</b> (kuota target Kg), dan <b>spk_progres</b> (log kerja harian) yang terhubung melalui <code>id_user</code> dan <code>id_spk</code>.";
        } elseif (strpos($pesan_user, 'limit') !== false || strpos($pesan_user, 'halaman') !== false || strpos($pesan_user, 'pagination') !== false) {
            $balasan = "Sesuai blueprint, rendering baris data tabel dashboard dikunci ketat maksimal <b>5 baris data per halaman</b> (<code>LIMIT 5</code>) untuk optimalisasi loading database server.";
        } else {
            // Teks default jika pengguna mengetik kata acak seperti "test" namun API belum diisi
            $balasan = "Halo Pak/Ibu <strong>$nama_user</strong>, saya mendengar pesan Anda: <i>\"" . htmlspecialchars($pesan_original) . "\"</i>.Saya dikonfigurasi khusus sebagai pakar sistem <b>Work Management</b> ini. Anda bisa menguji database lokal kita dengan memberikan perintah operasional seperti:
            1. <i>'lacak SPK-2026-001'</i> (untuk melihat laporan beban proyek)
            2. <i>'cek antrean'</i> (melihat tugas status pending)
            3. <i>'proyek sedang berjalan'</i> (melihat pekerjaan aktif drafter)";
        }
    } else {
        // Dokumen instruksi sistem (System Prompt Context) agar Google Gemini paham aturan program Anda
        $system_context = "
        Anda adalah AI Asisten Virtual dan Senior Programmer untuk program 'Work Management' PT Duta Hita Jaya.
        Aturan program yang wajib Anda ketahui:
        1. Satuan volume beban kerja wajib menggunakan KILOGRAM (KG), istilah Ton sudah tidak digunakan. Format angka harus menggunakan titik pemisah ribuan (contoh: 2.500 Kg).
        2. Tampilan tabel dashboard dikunci maksimal 5 baris data per halaman (LIMIT 5).
        3. Manager memiliki fitur 'Bypass Filter Waktu' untuk melacak berkas historis masa lalu tanpa merusak grafik statistik di atasnya.
        4. Tabel database kita di MySQL adalah `users`, `spk`, dan `spk_progres`.
        
        Identitas penanya saat ini: Nama: $nama_user, Tingkat Akses Role: $role_user.
        Jawablah pertanyaan user mengenai pemrograman PHP, perbaikan bug MySQL, rumus sistem, atau analisis logika dengan Bahasa Indonesia yang baik, profesional, dan ringkas.
        ";

        // Endpoint API Resmi Google Gemini (Model gemini-1.5-flash)
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $api_key;

        // Struktur data JSON sesuai aturan dokumentasi Google Generative AI
        $data_payload = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $system_context . "\n\nPertanyaan User: " . $pesan_original]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.4
            ]
        ];

        // Eksekusi pengiriman data API via cURL PHP
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $balasan = "Gagal terhubung dengan server Google Gemini AI. Periksa koneksi internet server Anda.";
        } else {
            $result = json_decode($response, true);
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                $raw_text = $result['candidates'][0]['content']['parts'][0]['text'];
                
                // Rapikan format markdown code block (```php ... ```) menjadi elemen HTML pre-code agar cantik di widget chat
                $balasan = preg_replace('/
http://googleusercontent.com/immersive_entry_chip/0
http://googleusercontent.com/immersive_entry_chip/1
http://googleusercontent.com/immersive_entry_chip/2
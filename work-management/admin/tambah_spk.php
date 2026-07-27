<?php
session_start();
require '../config.php';

// Proteksi halaman: Hanya Admin yang boleh masuk
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login");
    exit;
}

$pesan = "";

if (isset($_POST['simpan'])) {
    $no_spk = mysqli_real_escape_string($conn, trim($_POST['no_spk']));
    $nama_proyek = mysqli_real_escape_string($conn, trim($_POST['nama_proyek']));
    $nama_client = mysqli_real_escape_string($conn, trim($_POST['nama_client']));
    $deskripsi_tugas = mysqli_real_escape_string($conn, trim($_POST['deskripsi_tugas']));
    $tingkat_urgensi = $_POST['tingkat_urgensi'];
    
    // REVISI DESIMAL SINKRONISASI: Menjaga angka koma murni masuk database
    $total_tonase = (float)$_POST['total_tonase'];
    
    // TANGKAP LINK GOOGLE DRIVE (OPSIONAL)
    $link_drive = mysqli_real_escape_string($conn, trim($_POST['link_drive']));
    
    $deadline = date('Y-m-d H:i:s', strtotime($_POST['deadline']));
    $id_admin = $_SESSION['id_user']; // ID admin pembuat

    // Cek duplikasi nomor SPK
    $cek_spk = $conn->query("SELECT id_spk FROM spk WHERE no_spk = '$no_spk'");
    if ($cek_spk->num_rows > 0) {
        $pesan = "<div class='alert alert-danger fw-semibold shadow-sm'><i class='bi bi-exclamation-triangle-fill me-1'></i> Nomor SPK tersebut sudah terdaftar! Gunakan kode lain.</div>";
    } else {
        // Query Insert Data SPK Baru + Menyimpan Link Google Drive
        $sql_insert = "INSERT INTO spk (no_spk, id_admin, nama_proyek, nama_client, deskripsi_tugas, tingkat_urgensi, total_tonase, deadline, status, link_drive) 
                       VALUES ('$no_spk', '$id_admin', '$nama_proyek', '$nama_client', '$deskripsi_tugas', '$tingkat_urgensi', '$total_tonase', '$deadline', 'Pending', '$link_drive')";
        
        if ($conn->query($sql_insert)) {
            $pesan = "<div class='alert alert-success fw-semibold shadow-sm'><i class='bi bi-check-circle-fill me-1'></i> SPK Berhasil Didaftarkan! Mengalihkan...</div>";
            // SINKRONISASI HTACCESS: Mengubah dashboard.php menjadi dashboard
            echo "<script>setTimeout(function(){ window.location.href='dashboard'; }, 1500);</script>";
        } else {
            $pesan = "<div class='alert alert-danger fw-semibold shadow-sm'>Gagal menyimpan data ke sistem database: " . $conn->error . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftarkan Proyek SPK Baru - PT Duta Hita Jaya</title>
    <link rel="icon" type="image/png" href="../dhj2.png">
    <link href="../bootstrap.min.css" rel="stylesheet">
    <link href="../bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background-color: #f8fafc; color: #334155; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); background-color: #ffffff; }
        .btn-dhj { background-color: #1e3a8a; color: white; transition: background-color 0.2s; }
        .btn-dhj:hover { background-color: #172554; color: white; }
    </style>
</head>
<body>

<div class="container my-5" style="max-width: 750px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold text-dark m-0"><i class="bi bi-file-earmark-plus-fill text-primary me-2"></i>Form Pembuatan SPK</h4>
        <a href="dashboard" class="btn btn-sm btn-outline-secondary rounded-2"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>

    <?= $pesan; ?>

    <div class="card card-custom p-4">
        <form action="" method="POST">
            <div class="row g-3 mb-3">
                <div class="col-sm-6 col-12">
                    <label class="form-label fw-semibold text-secondary">Nomor Urut SPK</label>
                    <input type="text" name="no_spk" class="form-control" placeholder="Contoh: SPK-2025-001" required>
                </div>
                <div class="col-sm-6 col-12">
                    <label class="form-label fw-semibold text-secondary">Nama Perusahaan Client</label>
                    <input type="text" name="nama_client" class="form-control" placeholder="Nama PT / Instansi Client..." required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary">Nama Proyek Konstruksi</label>
                <input type="text" name="nama_proyek" class="form-control" placeholder="Contoh: Pembangunan Gudang Struktur Baja..." required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary">Uraian / Deskripsi Penjelasan Tugas</label>
                <textarea name="deskripsi_tugas" class="form-control" rows="4" placeholder="Tulis instruksi pengerjaan gambar detail..." required></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-dark"><i class="bi bi-google text-success me-1"></i>Link Folder Google Drive (Aset / Referensi)</label>
                <input type="url" name="link_drive" class="form-control" placeholder="https://drive.google.com/drive/folders/...">
                <div class="form-text small text-muted">Kosongkan saja jika folder atau file referensi Google Drive belum siap.</div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-12">
                    <label class="form-label fw-semibold text-secondary">Tingkat Urgensi</label>
                    <select name="tingkat_urgensi" class="form-select" required>
                        <option value="Normal" selected>Normal</option>
                        <option value="High">Tinggi (High)</option>
                        <option value="Urgent">Sangat Mendesak (Urgent)</option>
                    </select>
                </div>
                <div class="col-sm-6 col-12">
                    <label class="form-label fw-semibold text-secondary">Total Target Tonase (Kg)</label>
                    <input type="number" name="total_tonase" class="form-control" placeholder="0.00" min="0" step="0.01" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold text-secondary">Batas Waktu (Deadline)</label>
                <input type="datetime-local" name="deadline" class="form-control" required>
            </div>

            <button type="submit" name="simpan" class="btn btn-dhj w-100 fw-semibold shadow-sm py-2">
                <i class="bi bi-send-check-fill me-1"></i> Daftarkan Proyek SPK Baru
            </button>
        </form>
    </div>
</div>

<script src="../bootstrap.bundle.min.js"></script>
</body>
</html>
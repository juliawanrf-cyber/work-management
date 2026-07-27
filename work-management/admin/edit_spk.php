<?php
session_start();
require '../config.php';

// Proteksi halaman: Hanya Admin yang boleh masuk
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: dashboard");
    exit;
}

// Ambil data SPK lama
$query_spk = $conn->query("SELECT * FROM spk WHERE id_spk = $id");
$spk = $query_spk->fetch_assoc();

if (!$spk) {
    header("Location: dashboard");
    exit;
}

$pesan = "";

if (isset($_POST['ubah'])) {
    $nama_proyek     = mysqli_real_escape_string($conn, trim($_POST['nama_proyek']));
    $nama_client     = mysqli_real_escape_string($conn, trim($_POST['nama_client']));
    $deskripsi_tugas = mysqli_real_escape_string($conn, trim($_POST['deskripsi_tugas']));
    $tingkat_urgensi = mysqli_real_escape_string($conn, $_POST['tingkat_urgensi']);
    $total_tonase    = (float)$_POST['total_tonase'];
    
    // TANGKAP INPUT LINK GOOGLE DRIVE BARU/PERUBAHAN
    $link_drive = mysqli_real_escape_string($conn, trim($_POST['link_drive']));
    
    // TANGKAP PERUBAHAN STATUS DARI FORM ADMIN
    $status_baru = mysqli_real_escape_string($conn, $_POST['status']);
    
    $tgl_mulai = !empty($_POST['tgl_mulai']) ? date('Y-m-d H:i:s', strtotime($_POST['tgl_mulai'])) : NULL;
    $deadline  = !empty($_POST['deadline']) ? date('Y-m-d H:i:s', strtotime($_POST['deadline'])) : NULL;

    // LOGIKA PENYELARASAN DATABASE TANGGAL SELESAI
    $field_tgl_selesai = "";
    if ($status_baru === 'Completed') {
        // Jika diubah jadi Completed dan tgl_selesai belum ada, isi jam sekarang
        if (empty($spk['tgl_selesai'])) {
            $field_tgl_selesai = ", tgl_selesai = NOW() ";
        }
    } else {
        // Jika status diturunkan dari Completed, kosongkan tanggal selesainya
        $field_tgl_selesai = ", tgl_selesai = NULL ";
    }

    $tgl_mulai_sql = $tgl_mulai ? "'$tgl_mulai'" : "NULL";
    $deadline_sql  = $deadline ? "'$deadline'" : "NULL";

    // Query Perbaruan Data
    $query_update = "UPDATE spk SET 
                        nama_proyek = '$nama_proyek', 
                        nama_client = '$nama_client', 
                        deskripsi_tugas = '$deskripsi_tugas', 
                        tingkat_urgensi = '$tingkat_urgensi', 
                        total_tonase = '$total_tonase', 
                        tgl_mulai = $tgl_mulai_sql, 
                        deadline = $deadline_sql,
                        link_drive = '$link_drive',
                        status = '$status_baru'
                        $field_tgl_selesai
                     WHERE id_spk = $id";

    if ($conn->query($query_update)) {
        $pesan = "<div class='alert alert-success fw-semibold shadow-sm rounded-3 mb-3'><i class='bi bi-check-circle-fill me-1.5'></i> Data SPK &amp; Status Produksi Berhasil Diperbarui! Mengalihkan...</div>";
        echo "<script>setTimeout(function(){ window.location.href='detail_spk?id=$id'; }, 1200);</script>";
    } else {
        $pesan = "<div class='alert alert-danger fw-semibold shadow-sm rounded-3 mb-3'>Gagal memperbarui data database: " . $conn->error . "</div>";
    }
    
    // Refresh data local array
    $query_spk = $conn->query("SELECT * FROM spk WHERE id_spk = $id");
    $spk = $query_spk->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit SPK #<?= $spk['no_spk']; ?> - PT Duta Hita Jaya</title>
    <link rel="icon" type="image/png" href="../dhj2.png">
    
    <!-- LOKAL ONLY (Offline Local Network Friendly) -->
    <link href="../bootstrap.min.css" rel="stylesheet">
    <link href="../bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        /* ========================================= */
        /* MODERN INDIGO THEME - LIGHT MODE          */
        /* ========================================= */

        :root {
            --primary-indigo: #4F46E5;
            --primary-indigo-light: #6366F1;
            --primary-indigo-dark: #4338CA;
            --secondary-cyan: #06B6D4;
            --secondary-sky: #0EA5E9;
            --secondary-purple: #9333EA;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-900: #111827;
            --orange-accent: #EA580C;
            --amber-accent: #F59E0B;
        }

        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #EDE9FE 0%, #DBEAFE 50%, #E0F2FE 100%) !important;
            background-attachment: fixed !important;
            color: var(--gray-900);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at 20% 50%, rgba(147, 51, 234, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 50%, rgba(6, 182, 212, 0.1) 0%, transparent 50%);
            pointer-events: none;
            z-index: 1;
        }

        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image: radial-gradient(circle, rgba(79, 70, 229, 0.05) 1px, transparent 1px);
            background-size: 30px 30px;
            opacity: 0.4;
            pointer-events: none;
            z-index: 2;
        }

        .glass-card {
            position: relative;
            z-index: 10;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(249, 250, 251, 0.98) 100%) !important;
            backdrop-filter: blur(20px) saturate(180%) !important;
            border: 1px solid rgba(79, 70, 229, 0.15) !important;
            border-radius: 20px !important;
            box-shadow: 0 12px 40px -8px rgba(79, 70, 229, 0.12), 0 0 0 1px rgba(79, 70, 229, 0.05) inset !important;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .glass-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 48px -10px rgba(79, 70, 229, 0.2), 0 0 0 1.5px rgba(79, 70, 229, 0.15) inset !important;
        }

        .icon-glow-container {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .icon-glow-blue {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(147, 51, 234, 0.1) 100%) !important;
            color: var(--primary-indigo) !important;
            border: 2px solid rgba(79, 70, 229, 0.3) !important;
            box-shadow: 0 8px 24px -4px rgba(79, 70, 229, 0.2) !important;
        }

        .btn-grad-blue {
            background: linear-gradient(135deg, var(--primary-indigo) 0%, var(--secondary-cyan) 100%) !important;
            color: white !important;
            border: none !important;
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.3) !important;
            transition: all 0.3s ease;
        }

        .btn-grad-blue:hover {
            background: linear-gradient(135deg, var(--primary-indigo-light) 0%, var(--secondary-cyan) 100%) !important;
            box-shadow: 0 8px 28px rgba(79, 70, 229, 0.4) !important;
            transform: translateY(-2px);
            color: white !important;
        }

        .btn-outline-secondary {
            border: 1.5px solid rgba(148, 163, 184, 0.4) !important;
            color: var(--primary-indigo-dark) !important;
            background: rgba(79, 70, 229, 0.05);
        }

        .btn-outline-secondary:hover {
            background: rgba(79, 70, 229, 0.1) !important;
            border-color: var(--primary-indigo) !important;
            transform: translateY(-2px);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-indigo) !important;
            box-shadow: 0 0 0 0.25rem rgba(79, 70, 229, 0.25) !important;
        }

        .form-control,
        .form-select {
            color: var(--gray-900) !important;
            background-color: #ffffff !important;
        }

        .form-label {
            color: var(--gray-900) !important;
        }

        .form-text {
            color: #6b7280 !important;
        }

        .alert {
            position: relative;
            z-index: 10;
        }

        :focus-visible {
            outline: 3px solid var(--primary-indigo) !important;
            outline-offset: 2px;
        }
    </style>
</head>
<body>

<div class="container my-5" style="max-width: 800px; position: relative; z-index: 10;">
    <div class="glass-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-glow-container icon-glow-blue">
                    <i class="bi bi-pencil-square"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-gray-900 m-0" style="letter-spacing: -0.5px;">Modifikasi Data SPK</h4>
                    <span class="text-muted small">Perbarui parameter teknis atau tautan Google Drive proyek.</span>
                </div>
            </div>
            <a href="detail_spk?id=<?= $id; ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3.5 fw-semibold">
                <i class="bi bi-arrow-left me-1"></i> Batal
            </a>
        </div>
    </div>

    <?= $pesan; ?>

    <div class="glass-card p-4">
        <form action="" method="POST">
            <div class="row g-3 mb-3">
                <div class="col-sm-6 col-12">
                    <label class="form-label fw-semibold text-gray-900 small">Nomor Urut SPK (Permanen)</label>
                    <input type="text" class="form-control bg-white font-monospace fw-bold rounded-3" value="<?= $spk['no_spk']; ?>" readonly>
                </div>
                <div class="col-sm-6 col-12">
                    <label class="form-label fw-semibold text-gray-900 small">Nama Perusahaan Client</label>
                    <input type="text" name="nama_client" class="form-control rounded-3" value="<?= htmlspecialchars($spk['nama_client']); ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-gray-900 small">Nama Proyek Konstruksi</label>
                <input type="text" name="nama_proyek" class="form-control rounded-3" value="<?= htmlspecialchars($spk['nama_proyek']); ?>" required>
            </div>

            <?php $clean_deskripsi_edit = str_replace(["\r\n", "\r", "\n"], " ", $spk['deskripsi_tugas']); ?>
            <div class="mb-3">
                <label class="form-label fw-semibold text-gray-900 small">Uraian / Deskripsi Penjelasan Tugas</label>
                <textarea name="deskripsi_tugas" class="form-control rounded-3" rows="4" required><?= htmlspecialchars($clean_deskripsi_edit); ?></textarea>
            </div>

            <div class="mb-3 p-3 rounded-3 border" style="background: rgba(16, 185, 129, 0.08); border-color: #10b981 !important;">
                <label class="form-label fw-semibold text-gray-900 small mb-1"><i class="bi bi-google text-success me-1"></i> Link Folder Google Drive (Aset / Referensi)</label>
                <input type="url" name="link_drive" class="form-control form-control-sm rounded-3" placeholder="https://drive.google.com/drive/folders/..." value="<?= htmlspecialchars($spk['link_drive']); ?>">
                <div class="form-text small" style="font-size: 11px;">Bisa diperbarui atau diubah kapan saja mengikuti pembaruan dari klien.</div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-sm-6 col-12">
                    <label class="form-label fw-semibold text-gray-900 small">Status Kontrol Produksi</label>
                    <select name="status" class="form-select border-primary fw-semibold rounded-3" style="background-color: rgba(74, 144, 226, 0.08) !important; color: #1e3a8a !important;" required>
                        <option value="Pending" <?= $spk['status'] == 'Pending' ? 'selected' : ''; ?>>Pending (Antrean Tugas)</option>
                        <option value="On Progress" <?= $spk['status'] == 'On Progress' ? 'selected' : ''; ?>>On Progress (Meja Kerja Aktif)</option>
                        <option value="Paused" <?= $spk['status'] == 'Paused' ? 'selected' : ''; ?>>Paused (Pekerjaan Tertunda)</option>
                        <option value="Completed" <?= $spk['status'] == 'Completed' ? 'selected' : ''; ?>>Completed (Selesai Gambar)</option>
                    </select>
                    <div class="form-text text-danger mt-1" style="font-size: 11px; line-height: 1.3;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Jika status diubah kembali ke <b>On Progress</b>, proyek otomatis aktif lagi di dashboard drafter.
                    </div>
                </div>
                <div class="col-sm-6 col-12">
                    <label class="form-label fw-semibold text-gray-900 small">Tingkat Urgensi</label>
                    <select name="tingkat_urgensi" class="form-select rounded-3" required>
                        <option value="Normal" <?= $spk['tingkat_urgensi'] == 'Normal' ? 'selected' : ''; ?>>Normal</option>
                        <option value="High" <?= $spk['tingkat_urgensi'] == 'High' ? 'selected' : ''; ?>>Tinggi (High)</option>
                        <option value="Urgent" <?= $spk['tingkat_urgensi'] == 'Urgent' ? 'selected' : ''; ?>>Sangat Mendesak (Urgent)</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-12">
                    <label class="form-label fw-semibold text-gray-900 small">Total Target Tonase (Kg)</label>
                    <input type="number" name="total_tonase" class="form-control rounded-3" value="<?= $spk['total_tonase']; ?>" min="0" step="0.01" required>
                </div>
                <div class="col-sm-6 col-12"></div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-12">
                    <label class="form-label fw-semibold text-gray-900 small">Waktu Mulai Baru</label>
                    <input type="datetime-local" name="tgl_mulai" class="form-control rounded-3" value="<?= $spk['tgl_mulai'] ? date('Y-m-d\TH:i', strtotime($spk['tgl_mulai'])) : ''; ?>" required>
                </div>
                <div class="col-sm-6 col-12">
                    <label class="form-label fw-semibold text-gray-900 small">Deadline Baru</label>
                    <input type="datetime-local" name="deadline" class="form-control rounded-3" value="<?= $spk['deadline'] ? date('Y-m-d\TH:i', strtotime($spk['deadline'])) : ''; ?>" required>
                </div>
            </div>

            <button type="submit" name="ubah" class="btn btn-grad-blue w-100 fw-semibold py-2.5 rounded-pill shadow-sm">
                <i class="bi bi-cloud-check-fill me-1.5"></i> Simpan Perubahan SPK
            </button>
        </form>
    </div>
</div>

<!-- SCRIPT BOOTSTRAP LOKAL -->
<script src="../bootstrap.bundle.min.js"></script>

<!-- KOMPONEN CHAT INTERNAL & POP-UP ALERT DEADLINE -->
<?php include '../chat_and_alert_component.php'; ?>

</body>
</html>
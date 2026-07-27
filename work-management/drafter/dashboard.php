<?php
session_start();
require '../config.php';

// Proteksi halaman: Hanya Drafter yang boleh masuk
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'drafter') {
    header("Location: ../login");
    exit;
}

$id_drafter_login = $_SESSION['id_user'];

// LOGIKA PENCARIAN
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$search_query = "";
if (!empty($search)) {
    $search_query = " AND (spk.no_spk LIKE '%$search%' OR spk.nama_proyek LIKE '%$search%') ";
}

$batas = 5; // Batas maksimal 5 data per tabel

// ==========================================
// PAGINATION TABEL 1: MEJA KERJA AKTIF
// ==========================================
$hal_kerja = isset($_GET['hal_kerja']) ? (int)$_GET['hal_kerja'] : 1;
$awal_kerja = ($hal_kerja > 1) ? ($hal_kerja * $batas) - $batas : 0;

$query_hitung_kerja = "SELECT COUNT(*) AS total FROM spk 
                       WHERE status IN ('On Progress', 'Paused') 
                       AND (id_drafter = '$id_drafter_login' OR id_spk IN (SELECT id_spk FROM spk_progres WHERE id_user = '$id_drafter_login'))
                       $search_query";
$total_kerja = $conn->query($query_hitung_kerja)->fetch_assoc()['total'];
$total_hal_kerja = ceil($total_kerja / $batas);

$query_my_job = "SELECT spk.*, 
                (SELECT COALESCE(SUM(tonase_diambil), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk AND (is_kerja_tambah = 0 OR is_kerja_tambah IS NULL)) AS tonase_utama,
                (SELECT COALESCE(SUM(tonase_diambil), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk AND is_kerja_tambah = 1) AS tonase_vo,
                (SELECT COALESCE(SUM(progres_ga), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk) AS total_ga,
                (SELECT COALESCE(SUM(progres_modeling), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk) AS total_modeling
                FROM spk 
                WHERE spk.status IN ('On Progress', 'Paused') 
                AND (spk.id_drafter = '$id_drafter_login' OR spk.id_spk IN (SELECT id_spk FROM spk_progres WHERE id_user = '$id_drafter_login'))
                $search_query
                ORDER BY spk.tgl_input DESC
                LIMIT $awal_kerja, $batas";
$my_jobs = $conn->query($query_my_job);

// ==========================================
// PAGINATION TABEL 2: KOLABORASI TAG TEAM
// ==========================================
$hal_collab = isset($_GET['hal_collab']) ? (int)$_GET['hal_collab'] : 1;
$awal_collab = ($hal_collab > 1) ? ($hal_collab * $batas) - $batas : 0;

$query_hitung_collab = "SELECT COUNT(*) AS total FROM spk 
                        WHERE status IN ('On Progress', 'Paused') 
                        AND id_drafter != '$id_drafter_login'
                        AND id_spk NOT IN (SELECT id_spk FROM spk_progres WHERE id_user = '$id_drafter_login')
                        $search_query";
$total_collab = $conn->query($query_hitung_collab)->fetch_assoc()['total'];
$total_hal_collab = ceil($total_collab / $batas);

$query_collab = "SELECT spk.*, users.nama_lengkap AS nama_lead_drafter,
                (SELECT COALESCE(SUM(tonase_diambil), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk AND (is_kerja_tambah = 0 OR is_kerja_tambah IS NULL)) AS tonase_utama,
                (SELECT COALESCE(SUM(tonase_diambil), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk AND is_kerja_tambah = 1) AS tonase_vo,
                (SELECT COALESCE(SUM(progres_ga), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk) AS total_ga,
                (SELECT COALESCE(SUM(progres_modeling), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk) AS total_modeling
                FROM spk 
                JOIN users ON spk.id_drafter = users.id_user
                WHERE spk.status IN ('On Progress', 'Paused') 
                AND spk.id_drafter != '$id_drafter_login'
                AND spk.id_spk NOT IN (SELECT id_spk FROM spk_progres WHERE id_user = '$id_drafter_login')
                $search_query
                ORDER BY spk.tgl_input DESC
                LIMIT $awal_collab, $batas";
$collab_jobs = $conn->query($query_collab);

// ==========================================
// PAGINATION TABEL 3: DAFTAR ANTREAN SPK
// ==========================================
$hal_queue = isset($_GET['hal_queue']) ? (int)$_GET['hal_queue'] : 1;
$awal_queue = ($hal_queue > 1) ? ($hal_queue * $batas) - $batas : 0;

$query_hitung_queue = "SELECT COUNT(*) AS total FROM spk WHERE status = 'Pending' $search_query";
$total_queue = $conn->query($query_hitung_queue)->fetch_assoc()['total'];
$total_hal_queue = ceil($total_queue / $batas);

$query_queue = "SELECT * FROM spk 
                WHERE status = 'Pending' 
                $search_query 
                ORDER BY tingkat_urgensi = 'Urgent' DESC, tgl_input DESC
                LIMIT $awal_queue, $batas";
$queue_jobs = $conn->query($query_queue);

// Array penampung HTML modal agar dirender di luar glass-card
$modals_output = [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drafter Dashboard - PT Duta Hita Jaya</title>
    <link rel="icon" type="image/png" href="../dhj2.png">
    
    <!-- LOKAL ONLY (BISA DIPAKAI OFFLINE TANPA INTERNET) -->
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

        .glass-card-success { border-left: 6px solid #10b981 !important; }
        .glass-card-primary { border-left: 6px solid var(--primary-indigo) !important; }
        .glass-card-amber { border-left: 6px solid var(--amber-accent) !important; }

        .navbar-glass {
            position: relative;
            z-index: 1030;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(249, 250, 251, 0.95) 100%) !important;
            backdrop-filter: blur(20px) !important;
            border-bottom: 2px solid rgba(79, 70, 229, 0.1) !important;
            box-shadow: 0 8px 32px -8px rgba(79, 70, 229, 0.1) !important;
            padding-top: 12px;
            padding-bottom: 12px;
        }

        .company-info-steel { line-height: 1.3; }
        
        .company-name-steel {
            font-size: 1.2rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, var(--gray-900) 0%, var(--primary-indigo) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: none;
        }

        .company-division-steel {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--primary-indigo);
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .user-badge-steel {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(147, 51, 234, 0.1) 100%) !important;
            border: 1.5px solid rgba(79, 70, 229, 0.3) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
            color: var(--primary-indigo) !important;
        }

        .btn-logout-steel {
            background: linear-gradient(135deg, rgba(234, 88, 12, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%) !important;
            border: 1.5px solid rgba(234, 88, 12, 0.3) !important;
            color: var(--orange-accent) !important;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(234, 88, 12, 0.1);
        }

        .btn-logout-steel:hover {
            background: linear-gradient(135deg, var(--orange-accent) 0%, var(--amber-accent) 100%) !important;
            color: #fff !important;
            border-color: var(--orange-accent) !important;
            box-shadow: 0 6px 20px rgba(234, 88, 12, 0.3);
            transform: translateY(-2px);
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

        .icon-glow-green {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.15) 100%) !important;
            color: #10b981 !important;
            border: 2px solid rgba(16, 185, 129, 0.2) !important;
            box-shadow: 0 8px 24px -4px rgba(16, 185, 129, 0.2) !important;
        }

        .icon-glow-blue {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(147, 51, 234, 0.1) 100%) !important;
            color: var(--primary-indigo) !important;
            border: 2px solid rgba(79, 70, 229, 0.3) !important;
            box-shadow: 0 8px 24px -4px rgba(79, 70, 229, 0.2) !important;
        }

        .icon-glow-amber {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(234, 88, 12, 0.15) 100%) !important;
            color: var(--orange-accent) !important;
            border: 2px solid rgba(245, 158, 11, 0.2) !important;
            box-shadow: 0 8px 24px -4px rgba(245, 158, 11, 0.2) !important;
        }

        .glass-card:hover .icon-glow-container { transform: translateY(-4px) scale(1.08); }

        .table-glass { background: transparent !important; }

        .table-glass thead th {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.08) 0%, rgba(147, 51, 234, 0.05) 100%) !important;
            color: var(--primary-indigo-dark) !important;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.8px;
            padding: 16px 18px;
            border-bottom: 2px solid rgba(79, 70, 229, 0.2) !important;
        }

        .table-glass tbody td {
            background: transparent !important;
            padding: 18px 18px;
            border-bottom: 1px solid rgba(79, 70, 229, 0.1) !important;
            font-size: 0.875rem;
            color: var(--gray-900);
        }

        .table-glass tbody tr:hover { background: rgba(79, 70, 229, 0.04) !important; }

        .btn-action-custom {
            border-radius: 12px !important;
            font-weight: 600 !important;
            font-size: 0.8rem !important;
            padding: 8px 16px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .btn-grad-blue {
            background: linear-gradient(135deg, var(--primary-indigo) 0%, var(--secondary-cyan) 100%) !important;
            color: white !important;
            border: none !important;
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.3) !important;
        }

        .btn-grad-blue:hover {
            background: linear-gradient(135deg, var(--primary-indigo-light) 0%, var(--secondary-cyan) 100%) !important;
            box-shadow: 0 8px 28px rgba(79, 70, 229, 0.4) !important;
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-indigo) 0%, var(--secondary-purple) 100%) !important;
            border: none !important;
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.3) !important;
            color: white !important;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-indigo-light) 0%, var(--secondary-purple) 100%) !important;
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4) !important;
            transform: translateY(-2px);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-indigo) !important;
            color: var(--primary-indigo) !important;
            background: rgba(79, 70, 229, 0.05);
        }

        .btn-outline-primary:hover {
            background: linear-gradient(135deg, var(--primary-indigo) 0%, var(--secondary-purple) 100%) !important;
            color: white !important;
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.3);
            transform: translateY(-2px);
        }

        .link-spk-click {
            color: var(--primary-indigo-dark) !important;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .link-spk-click:hover {
            color: var(--primary-indigo) !important;
            text-shadow: 0 0 10px rgba(79, 70, 229, 0.3);
        }

        .badge {
            font-weight: 600;
            letter-spacing: 0.3px;
            padding: 6px 12px;
            border-radius: 8px;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-indigo) !important;
            box-shadow: 0 0 0 0.25rem rgba(79, 70, 229, 0.2) !important;
        }

        .input-group-text {
            background: rgba(79, 70, 229, 0.05) !important;
            border-color: rgba(79, 70, 229, 0.15) !important;
        }

        .progress {
            background-color: rgba(79, 70, 229, 0.1) !important;
        }

        .progress-bar {
            background: linear-gradient(90deg, #10b981 0%, #059669 100%) !important;
        }
        .progress-bar.bg-warning {
            background: linear-gradient(90deg, var(--amber-accent) 0%, var(--orange-accent) 100%) !important;
        }

        .modal-backdrop { background-color: rgba(17, 24, 39, 0.7) !important; }
        .modal-content {
            background: #ffffff !important;
            border: 2px solid rgba(79, 70, 229, 0.2) !important;
            box-shadow: 0 24px 64px -12px rgba(17, 24, 39, 0.3) !important;
        }
        .modal-header {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.08) 0%, rgba(147, 51, 234, 0.05) 100%) !important;
            border-bottom: 2px solid rgba(79, 70, 229, 0.15) !important;
        }
        .modal-body { color: var(--gray-900) !important; }
        .modal-footer {
            background: rgba(249, 250, 251, 1) !important;
            border-top: 2px solid rgba(79, 70, 229, 0.1) !important;
        }

        .container-responsive {
            width: 100%;
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 1.25rem;
            position: relative;
            z-index: 10;
        }
        .scope-desc-box { display: none; margin-top: 8px; margin-bottom: 12px; }
        .text-truncate-custom {
            max-width: 280px;
            white-space: normal;
            word-wrap: break-word;
            font-size: 0.82rem;
            line-height: 1.45;
        }

        @media (prefers-reduced-motion: no-preference) {
            .glass-card, .btn-action-custom, .icon-glow-container, .link-spk-click { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        }
        @media (max-width: 767.98px) {
            .table-glass tbody td { padding: 12px; font-size: 0.8rem; }
            .btn-action-custom { padding: 6px 12px !important; font-size: 0.75rem !important; }
            .text-truncate-custom { max-width: 180px; }
            .icon-glow-container { width: 48px; height: 48px; font-size: 1.3rem; }
        }
        :focus-visible { outline: 3px solid var(--primary-indigo) !important; outline-offset: 2px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-glass sticky-top shadow-sm">
    <div class="container-responsive d-flex justify-content-between align-items-center">
        <a class="navbar-brand p-0">
            <div class="company-info-steel">
                <div class="company-name-steel">PT Duta Hita Jaya</div>
                <div class="company-division-steel">STEEL CONSTRUCTION DIVISION</div>
            </div>
        </a>
        <div class="navbar-nav align-items-center flex-row gap-2">
            <span class="user-badge-steel px-3 py-2 rounded-pill d-inline-flex align-items-center">
                <i class="bi bi-vector-pen me-2 fs-6" style="color: var(--primary-indigo);"></i> 
                <span style="color: #6B7280;">Drafter:</span> 
                <strong class="ms-2" style="color: var(--primary-indigo-dark);"><?= $_SESSION['nama']; ?></strong>
            </span>
            <a class="btn btn-logout-steel btn-sm rounded-pill px-3 fw-semibold d-inline-flex align-items-center" href="../logout">
                <i class="bi bi-box-arrow-right me-1.5"></i> Keluar
            </a>
        </div>
    </div>
</nav>

<div class="container-responsive my-4">

    <!-- SEARCH BAR CARD GLASS -->
    <div class="glass-card p-3 mb-4">
        <form action="dashboard" method="GET" class="row g-2 align-items-center">
            <div class="col-md-9 col-12">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted rounded-start-pill ps-3"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 pe-3 rounded-end-pill" placeholder="Cari berdasarkan No. SPK atau Nama Proyek..." value="<?= htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3 col-12 d-flex gap-2">
                <button type="submit" class="btn btn-grad-blue btn-sm rounded-pill w-100 fw-semibold d-inline-flex align-items-center justify-content-center">
                    <i class="bi bi-search me-1"></i> Cari SPK
                </button>
                <?php if (!empty($search)) : ?>
                    <a href="dashboard" class="btn btn-outline-secondary btn-sm rounded-pill w-50 text-center d-inline-flex align-items-center justify-content-center">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- TABEL 1: MEJA KERJA PROYEK AKTIF -->
    <div class="glass-card glass-card-success p-4 mb-4">
        <div class="d-flex align-items-center mb-3 flex-wrap gap-3">
            <div class="icon-glow-container icon-glow-green">
                <i class="bi bi-cone-striped"></i>
            </div>
            <div>
                <h4 class="fw-bold text-dark m-0" style="letter-spacing: -0.5px;">Meja Kerja Proyek Aktif</h4>
                <p class="text-muted small m-0">Input progres harian Anda di sini. Akumulasi beban kilo (Kg) dipantau real-time harian.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-glass align-middle mb-0">
                <thead>
                    <tr>
                        <th>No. SPK</th>
                        <th>Nama Proyek &amp; Instruksi</th>
                        <th>Urgensi</th>
                        <th style="min-width: 210px;">Alokasi Progres Beban</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th class="text-center" style="min-width: 200px;">Tindakan Operasional</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($my_jobs->num_rows > 0) : ?>
                        <?php while ($job = $my_jobs->fetch_assoc()) : ?>
                            <?php 
                                $badge_urgensi = ($job['tingkat_urgensi'] == 'Urgent') ? 'bg-danger-subtle text-danger border border-danger-subtle' : (($job['tingkat_urgensi'] == 'High') ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-light text-secondary border');
                                $row_class = ($job['status'] == 'Paused') ? 'opacity-75' : '';
                                
                                // KALKULASI PROPORSI TONASE UTAMA VS VO
                                $target_kg       = (float)$job['total_tonase'];
                                $tonase_utama    = (float)$job['tonase_utama'];
                                $tonase_vo       = (float)$job['tonase_vo'];
                                $total_akumulasi = $tonase_utama + $tonase_vo;

                                $persen_utama = ($target_kg > 0) ? round(($tonase_utama / $target_kg) * 100, 2) : 0;
                                $persen_vo    = ($target_kg > 0) ? round(($tonase_vo / $target_kg) * 100, 2) : 0;
                                $persen_total = ($target_kg > 0) ? round(($total_akumulasi / $target_kg) * 100, 2) : 0;

                                $width_bar_utama = min($persen_utama, 100);
                                $width_bar_vo    = $persen_vo;

                                $out_ga = intval($job['total_ga']);
                                $out_model = intval($job['total_modeling']);

                                // PERAPIHAN TEKS DESKRIPSI (Hilangkan karakter \r\n)
                                $clean_deskripsi = str_replace(["\r\n", "\r", "\n"], " ", $job['deskripsi_tugas']);

                                // TAMPUNG MODAL HTML KE DALAM ARRAY UNTUK DITERBITKAN DI LUAR GLASS-CARD
                                ob_start();
                            ?>
                            <!-- MODAL UPDATE PROGRES DRAFTING -->
                            <div class="modal fade text-start" id="modalUpdate<?= $job['id_spk']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-md">
                                    <div class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
                                        <form action="proses_drafter?aksi=update_progress" method="POST">
                                            <input type="hidden" name="id_spk" value="<?= $job['id_spk']; ?>">
                                            <div class="modal-header bg-light">
                                                <h5 class="modal-title fw-bold text-dark fs-6"><i class="bi bi-file-earmark-medical-fill text-primary me-1.5"></i> Laporan Kerja Progres</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body py-3">
                                                <span class="font-monospace fw-bold text-dark d-block"><?= $job['no_spk']; ?></span>
                                                <span class="small fw-semibold text-secondary d-block mb-2"><?= $job['nama_proyek']; ?></span>
                                                
                                                <div class="p-2.5 mb-3 rounded-3 border border-warning shadow-sm" style="background-color: #fffbeb;">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="is_kerja_tambah" value="1" id="isKerjaTambah<?= $job['id_spk']; ?>">
                                                        <label class="form-check-label small fw-bold text-warning-emphasis" for="isKerjaTambah<?= $job['id_spk']; ?>">
                                                            <i class="bi bi-plus-circle-fill text-warning me-1"></i> Tandai sebagai Pekerjaan Tambah (VO / Addendum)
                                                        </label>
                                                    </div>
                                                    <span class="text-muted d-block ms-4" style="font-size: 10px; line-height: 1.3;">
                                                        Centang opsi ini jika input harian ini merupakan revisi tambahan / over-target / permintaan ekstra client di luar kontrak awal.
                                                    </span>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label text-dark small fw-bold d-block">Scope Kerja Hari Ini (Bisa Pilih > 1)</label>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input chk-scope chk-ga-class" type="checkbox" name="scope_kerja[]" value="GA" id="scopeGA<?= $job['id_spk']; ?>" data-id="<?= $job['id_spk']; ?>">
                                                        <label class="form-check-label small" for="scopeGA<?= $job['id_spk']; ?>">GA</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input chk-scope chk-model-class" type="checkbox" name="scope_kerja[]" value="Modeling" id="scopeModel<?= $job['id_spk']; ?>" data-id="<?= $job['id_spk']; ?>">
                                                        <label class="form-check-label small" for="scopeModel<?= $job['id_spk']; ?>">Modeling</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input chk-scope chk-shop" type="checkbox" name="scope_kerja[]" value="Shop Drawing" id="scopeShop<?= $job['id_spk']; ?>" data-id="<?= $job['id_spk']; ?>">
                                                        <label class="form-check-label small" for="scopeShop<?= $job['id_spk']; ?>">Shop Drawing</label>
                                                    </div>
                                                </div>

                                                <div id="boxGA<?= $job['id_spk']; ?>" class="scope-desc-box">
                                                    <label class="form-label text-success small fw-bold">Penjelasan Pengerjaan GA</label>
                                                    <input type="text" name="keterangan_ga" class="form-control form-control-sm mb-2" placeholder="Detail kontribusi GA...">
                                                    <input type="number" name="progres_ga_val" id="inputPersenGA<?= $job['id_spk']; ?>" class="form-control form-control-sm" min="1" placeholder="Cicilan %..." disabled>
                                                </div>
                                                <div id="boxModel<?= $job['id_spk']; ?>" class="scope-desc-box">
                                                    <label class="form-label text-primary small fw-bold">Penjelasan Pengerjaan Modeling</label>
                                                    <input type="text" name="keterangan_modeling" class="form-control form-control-sm mb-2" placeholder="Detail kontribusi Modeling...">
                                                    <input type="number" name="progres_modeling_val" id="inputPersenModel<?= $job['id_spk']; ?>" class="form-control form-control-sm" min="1" placeholder="Cicilan %..." disabled>
                                                </div>
                                                <div id="boxShop<?= $job['id_spk']; ?>" class="scope-desc-box">
                                                    <label class="form-label text-danger small fw-bold">Penjelasan Pengerjaan Shop Drawing</label>
                                                    <input type="text" name="keterangan_shop" class="form-control form-control-sm mb-2" placeholder="Detail komponen detail drawing...">
                                                    <input type="number" name="tonase_diambil" id="inputKg<?= $job['id_spk']; ?>" class="form-control form-control-sm" min="0" step="0.01" placeholder="Berat tonase Kg..." disabled>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light py-2">
                                                <button type="button" class="btn btn-secondary btn-sm rounded-2" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary btn-sm rounded-2 fw-semibold">Simpan Progres</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php 
                                $modals_output[] = ob_get_clean(); 
                            ?>

                            <tr class="<?= $row_class; ?>">
                                <td>
                                    <a href="detail_spk?id=<?= $job['id_spk']; ?>" class="link-spk-click font-monospace"><i class="bi bi-box-arrow-up-right me-1"></i><?= $job['no_spk']; ?></a>
                                    <?php if (!empty($job['link_drive'])) : ?>
                                        <a href="<?= $job['link_drive']; ?>" target="_blank" class="ms-1.5 text-success" title="Buka Folder Google Drive Gambar Kerja"><i class="bi bi-google"></i></a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark d-block"><?= $job['nama_proyek']; ?></span>
                                    <span class="text-muted text-truncate-custom d-inline-block"><?= htmlspecialchars($clean_deskripsi); ?></span>
                                </td>
                                <td><span class="badge <?= $badge_urgensi; ?> px-2.5 py-1 rounded-2"><?= $job['tingkat_urgensi']; ?></span></td>
                                <td>
                                    <div style="min-width: 190px;">
                                        <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 11px;">
                                            <span class="text-muted fw-medium text-nowrap">
                                                SD: <strong><?= number_format($total_akumulasi, 2, ',', '.'); ?></strong> / <?= number_format($target_kg, 2, ',', '.'); ?> Kg
                                            </span>
                                            <span class="fw-bold <?= ($persen_total > 100) ? 'text-warning-emphasis' : 'text-success'; ?> ms-1"><?= $persen_total; ?>%</span>
                                        </div>
                                        <div class="progress mb-1.5" style="height: 8px; background-color: #cbd5e1; border-radius: 6px; overflow: hidden;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $width_bar_utama; ?>%" title="Utama: <?= number_format($tonase_utama, 2, ',', '.'); ?> Kg"></div>
                                            <?php if ($width_bar_vo > 0) : ?>
                                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $width_bar_vo; ?>%; background-color: #f59e0b !important;" title="Pekerjaan Tambah (VO): +<?= number_format($tonase_vo, 2, ',', '.'); ?> Kg"></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex gap-1 flex-wrap" style="font-size: 10px;">
                                            <span class="badge bg-light text-dark border px-1.5" style="border-color: #99f6e4 !important; background-color: #f0fdfa !important; color: #115e59 !important;">GA: <b><?= $out_ga; ?>%</b></span>
                                            <span class="badge bg-light text-primary border px-1.5" style="border-color: #bfdbfe !important; background-color: #eff6ff !important;">Model: <b><?= $out_model; ?>%</b></span>
                                            <?php if ($tonase_vo > 0) : ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-1.5" title="Ada Pekerjaan Tambah">+VO: <b><?= number_format($tonase_vo, 2, ',', '.'); ?> Kg</b></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-secondary small fw-medium text-nowrap"><i class="bi bi-clock me-1"></i><?= date('d M Y H:i', strtotime($job['deadline'])); ?></td>
                                <td>
                                    <?php if ($job['status'] == 'On Progress') : ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 text-nowrap"><i class="bi bi-play-fill me-0.5"></i> ACTIVE</span>
                                    <?php else : ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 text-nowrap"><i class="bi bi-pause-fill me-0.5"></i> PAUSED</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1.5 flex-wrap justify-content-center">
                                        <button type="button" class="btn btn-primary btn-action-custom text-nowrap" data-bs-toggle="modal" data-bs-target="#modalUpdate<?= $job['id_spk']; ?>"><i class="bi bi-pencil-fill"></i> Update</button>
                                        <?php if ((int)$job['id_drafter'] === (int)$id_drafter_login) : ?>
                                            <?php if ($job['status'] == 'On Progress') : ?>
                                                <a href="proses_drafter?aksi=pause&id=<?= $job['id_spk']; ?>" class="btn btn-warning btn-action-custom text-nowrap"><i class="bi bi-pause-fill"></i> Pause</a>
                                                
                                                <?php if ($persen_total >= 100) : ?>
                                                    <a href="proses_drafter?aksi=complete&id=<?= $job['id_spk']; ?>" class="btn btn-success btn-action-custom text-nowrap" onclick="return confirm('Apakah pengerjaan gambar teknik ini sudah selesai total?')"><i class="bi bi-check-all"></i> Selesai</a>
                                                <?php else : ?>
                                                    <button type="button" class="btn btn-secondary btn-action-custom text-nowrap" style="cursor: not-allowed;" title="Shop Drawing harus 100% untuk menyelesaikan proyek" disabled><i class="bi bi-lock-fill"></i> Belum Selesai</button>
                                                <?php endif; ?>

                                            <?php else : ?>
                                                <a href="proses_drafter?aksi=resume&id=<?= $job['id_spk']; ?>" class="btn btn-grad-blue btn-action-custom text-nowrap"><i class="bi bi-play-fill"></i> Resume</a>
                                            <?php endif; ?>
                                        <?php else : ?>
                                            <span class="badge bg-light text-secondary border d-inline-flex align-items-center px-2.5 py-1.5 rounded-2 small text-nowrap" style="font-size: 11px;"><i class="bi bi-person-workspace text-primary me-1"></i> Anggota Tim</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr><td colspan="7" class="text-center text-muted small py-4">Tidak ada proyek aktif berjalan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TABEL 2: KOLABORASI & TAG TEAM AKTIF DIVISI -->
    <div class="glass-card glass-card-primary p-4 mb-4">
        <div class="d-flex align-items-center mb-3 flex-wrap gap-3">
            <div class="icon-glow-container icon-glow-blue">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <h4 class="fw-bold text-dark m-0" style="letter-spacing: -0.5px;">Kolaborasi &amp; Tag Team Aktif Divisi</h4>
                <p class="text-muted small m-0">Proyek milik Drafter lain. Anda bisa bergabung (Tag Team) untuk mempercepat pengeluaran gambar teknik.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-glass align-middle mb-0">
                <thead>
                    <tr>
                        <th>No. SPK</th>
                        <th>Nama Proyek &amp; Deskripsi</th>
                        <th>Drafter Utama</th>
                        <th style="min-width: 210px;">Akumulasi Progres</th>
                        <th>Deadline</th>
                        <th class="text-center">Aksi Tag Team</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($collab_jobs->num_rows > 0) : ?>
                        <?php while ($col = $collab_jobs->fetch_assoc()) : ?>
                            <?php 
                                $col_target     = (float)$col['total_tonase'];
                                $col_utama      = (float)$col['tonase_utama'];
                                $col_vo         = (float)$col['tonase_vo'];
                                $col_total_akumu= $col_utama + $col_vo;

                                $persen_col_utama = ($col_target > 0) ? round(($col_utama / $col_target) * 100, 2) : 0;
                                $persen_col_vo    = ($col_target > 0) ? round(($col_vo / $col_target) * 100, 2) : 0;
                                $persen_col_total = ($col_target > 0) ? round(($col_total_akumu / $col_target) * 100, 2) : 0;

                                $width_col_utama  = min($persen_col_utama, 100);
                                $width_col_vo     = $persen_col_vo;

                                $col_ga = intval($col['total_ga']);
                                $col_model = intval($col['total_modeling']);

                                // PERAPIHAN TEKS DESKRIPSI (Hilangkan karakter \r\n)
                                $clean_col_deskripsi = str_replace(["\r\n", "\r", "\n"], " ", $col['deskripsi_tugas']);
                            ?>
                            <tr>
                                <td>
                                    <a href="detail_spk?id=<?= $col['id_spk']; ?>" class="link-spk-click font-monospace"><i class="bi bi-box-arrow-up-right me-1"></i><?= $col['no_spk']; ?></a>
                                    <?php if (!empty($col['link_drive'])) : ?>
                                        <a href="<?= $col['link_drive']; ?>" target="_blank" class="ms-1.5 text-success" title="Buka Folder Google Drive Gambar Kerja"><i class="bi bi-google"></i></a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark d-block"><?= $col['nama_proyek']; ?></span>
                                    <span class="text-muted text-truncate-custom d-inline-block"><?= htmlspecialchars($clean_col_deskripsi); ?></span>
                                </td>
                                <td><span class="badge bg-primary-subtle text-primary border px-2.5 py-1 rounded-2"><i class="bi bi-person-fill text-primary me-1"></i> <?= $col['nama_lead_drafter']; ?></span></td>
                                <td>
                                    <div style="min-width: 190px;">
                                        <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 11px;">
                                            <span class="text-muted fw-medium text-nowrap">SD: <strong><?= number_format($col_total_akumu, 2, ',', '.'); ?></strong> / <?= number_format($col_target, 2, ',', '.'); ?> Kg</span>
                                            <span class="fw-bold <?= ($persen_col_total > 100) ? 'text-warning-emphasis' : 'text-success'; ?> ms-1"><?= $persen_col_total; ?>%</span>
                                        </div>
                                        <div class="progress mb-1.5" style="height: 8px; background-color: #cbd5e1; border-radius: 6px; overflow: hidden;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $width_col_utama; ?>%" title="Utama: <?= number_format($col_utama, 2, ',', '.'); ?> Kg"></div>
                                            <?php if ($width_col_vo > 0) : ?>
                                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $width_col_vo; ?>%; background-color: #f59e0b !important;" title="Pekerjaan Tambah (VO): +<?= number_format($col_vo, 2, ',', '.'); ?> Kg"></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex gap-1 flex-wrap" style="font-size: 10px;">
                                            <span class="badge bg-light text-dark border px-1.5" style="border-color: #99f6e4 !important; background-color: #f0fdfa !important; color: #115e59 !important;">GA: <b><?= $col_ga; ?>%</b></span>
                                            <span class="badge bg-light text-primary border px-1.5" style="border-color: #bfdbfe !important; background-color: #eff6ff !important;">Model: <b><?= $col_model; ?>%</b></span>
                                            <?php if ($col_vo > 0) : ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-1.5">+VO: <b><?= number_format($col_vo, 2, ',', '.'); ?> Kg</b></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-secondary small fw-medium text-nowrap"><i class="bi bi-clock me-1"></i><?= date('d M Y H:i', strtotime($col['deadline'])); ?></td>
                                <td class="text-center">
                                    <a href="proses_drafter?aksi=claim&id=<?= $col['id_spk']; ?>" class="btn btn-outline-primary btn-action-custom text-nowrap" onclick="return confirm('Bergabung ke dalam tim proyek kolaborasi ini?')"><i class="bi bi-person-plus-fill me-1"></i> Gabung Tim</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr><td colspan="6" class="text-center text-muted small py-4">Tidak ada proyek aktif lain untuk dikolaborasikan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TABEL 3: DAFTAR ANTREAN TUGAS SPK -->
    <div class="glass-card glass-card-amber p-4 mb-4">
        <div class="d-flex align-items-center mb-3 flex-wrap gap-3">
            <div class="icon-glow-container icon-glow-amber">
                <i class="bi bi-clipboard-data"></i>
            </div>
            <div>
                <h4 class="fw-bold text-dark m-0" style="letter-spacing: -0.5px;">Daftar Antrean Tugas SPK</h4>
                <p class="text-muted small m-0">Klaim ambil proyek secara penuh untuk didistribusikan ke meja produksi Anda.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-glass align-middle mb-0">
                <thead>
                    <tr>
                        <th>No. SPK</th>
                        <th>Nama Proyek &amp; Deskripsi</th>
                        <th>Urgensi</th>
                        <th>Total Volume Target</th>
                        <th>Deadline</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($queue_jobs->num_rows > 0) : ?>
                        <?php while ($q = $queue_jobs->fetch_assoc()) : ?>
                            <?php 
                                $q_urgent = ($q['tingkat_urgensi'] == 'Urgent');
                                $badge_urgensi = $q_urgent ? 'bg-danger-subtle text-danger border border-danger-subtle' : (($q['tingkat_urgensi'] == 'High') ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-light text-secondary border');
                                
                                // PERAPIHAN TEKS DESKRIPSI (Hilangkan karakter \r\n)
                                $clean_q_deskripsi = str_replace(["\r\n", "\r", "\n"], " ", $q['deskripsi_tugas']);
                            ?>
                            <tr class="<?= $q_urgent ? 'table-danger-subtle border-start border-danger border-3' : ''; ?>">
                                <td>
                                    <a href="detail_spk?id=<?= $q['id_spk']; ?>" class="link-spk-click font-monospace"><i class="bi bi-box-arrow-up-right me-1"></i><?= $q['no_spk']; ?></a>
                                    <?php if (!empty($q['link_drive'])) : ?>
                                        <a href="<?= $q['link_drive']; ?>" target="_blank" class="ms-1.5 text-success" title="Buka Folder Google Drive Gambar Kerja"><i class="bi bi-google"></i></a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark d-block"><?= $q['nama_proyek']; ?></span>
                                    <span class="text-muted text-truncate-custom d-inline-block"><?= htmlspecialchars($clean_q_deskripsi); ?></span>
                                </td>
                                <td><span class="badge <?= $badge_urgensi; ?> px-2.5 py-1 rounded-2"><?= $q['tingkat_urgensi']; ?></span></td>
                                <td><b class="text-dark font-monospace"><?= number_format($q['total_tonase'], 2, ',', '.'); ?> Kg</b></td>
                                <td class="text-secondary small fw-medium text-nowrap"><i class="bi bi-clock me-1"></i><?= date('d M Y H:i', strtotime($q['deadline'])); ?></td>
                                <td class="text-center">
                                    <a href="proses_drafter?aksi=claim&id=<?= $q['id_spk']; ?>" class="btn btn-outline-primary btn-action-custom text-nowrap" onclick="return confirm('Klaim ambil alih proyek ini penuh ke Meja Proyek?')"><i class="bi bi-box-arrow-in-down me-1"></i> Ambil Proyek</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Antrean tugas kosong.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- CETAK SEMUA MODAL DI LUAR GLASS CARD UNTUK MENGHINDARI BUG STACKING CONTEXT -->
<?= implode('', $modals_output); ?>

<!-- SCRIPT BOOTSTRAP LOKAL -->
<script src="../bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const scopeCheckboxes = document.querySelectorAll(".chk-scope");
    scopeCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener("change", function () {
            const idSPK = this.getAttribute("data-id"); 
            const scopeValue = this.value;
            let targetBoxId = "";
            
            if (scopeValue === "GA") targetBoxId = "boxGA" + idSPK;
            else if (scopeValue === "Modeling") targetBoxId = "boxModel" + idSPK;
            else if (scopeValue === "Shop Drawing") targetBoxId = "boxShop" + idSPK;

            const targetBox = document.getElementById(targetBoxId);
            if (!targetBox) return; 
            
            const inputTextBar = targetBox.querySelector("input[type='text']");
            const inputNumberBar = targetBox.querySelector("input[type='number']");

            if (this.checked) {
                targetBox.style.display = "block";
                if(inputTextBar) inputTextBar.required = true;
                if(inputNumberBar) { inputNumberBar.disabled = false; inputNumberBar.required = true; }
            } else {
                targetBox.style.display = "none";
                if(inputTextBar) { inputTextBar.required = false; inputTextBar.value = ""; }
                if(inputNumberBar) { inputNumberBar.disabled = true; inputNumberBar.required = false; inputNumberBar.value = ""; }
            }
        });
    });
});
</script>

<!-- PANGGIL KOMPONEN CHAT CORPORATE & POP-UP ALERT DEADLINE (H-3) -->
<?php include '../chat_and_alert_component.php'; ?>

</body>
</html>
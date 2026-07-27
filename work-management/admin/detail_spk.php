<?php
session_start();
require '../config.php';

// Proteksi halaman: Hanya Admin yang boleh masuk
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login");
    exit;
}

$id_spk = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_spk <= 0) {
    header("Location: dashboard");
    exit;
}

$pesan = "";

// =========================================================================
// 1. BACKEND OPERASIONAL: PROSES UPDATE LOG PROGRES
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'update_log') {
    $id_progres = intval($_POST['id_progres']);
    $tonase     = (float)$_POST['tonase_diambil'];
    $ga         = intval($_POST['progres_ga']);
    $modeling   = intval($_POST['progres_modeling']);
    $keterangan = mysqli_real_escape_string($conn, trim($_POST['keterangan_kerja']));

    $update_query = "UPDATE spk_progres SET 
                     tonase_diambil = $tonase, 
                     progres_ga = $ga, 
                     progres_modeling = $modeling, 
                     keterangan_kerja = '$keterangan',
                     tgl_update = NOW()
                     WHERE id_progres = $id_progres";
    
    if ($conn->query($update_query)) {
        $pesan = "<div class='alert alert-success alert-dismissible fade show small fw-medium mb-3 rounded-3 shadow-sm' role='alert'>
                    <i class='bi bi-check-circle-fill me-1.5'></i> Rekaman log kontribusi drafter berhasil diperbarui!
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                  </div>";
    } else {
        $pesan = "<div class='alert alert-danger alert-dismissible fade show small fw-medium mb-3 rounded-3 shadow-sm' role='alert'>
                    <i class='bi bi-exclamation-triangle-fill me-1.5'></i> Gagal memperbarui log database: " . $conn->error . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                  </div>";
    }
}

// =========================================================================
// 2. BACKEND OPERASIONAL: PROSES DELETE LOG PROGRES
// =========================================================================
if (isset($_GET['aksi']) && $_GET['aksi'] === 'hapus_log') {
    $id_hapus = isset($_GET['id_log']) ? intval($_GET['id_log']) : 0;
    if ($id_hapus > 0) {
        if ($conn->query("DELETE FROM spk_progres WHERE id_progres = $id_hapus")) {
            header("Location: detail_spk?id=" . $id_spk);
            exit;
        }
    }
}

// 3. QUERY INFORMASI UTAMA DATA SPK + PEMISAHAN SPK UTAMA & VO
$query_spk = "SELECT spk.*, 
              (SELECT COALESCE(SUM(tonase_diambil), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk AND (is_kerja_tambah = 0 OR is_kerja_tambah IS NULL)) AS tonase_utama,
              (SELECT COALESCE(SUM(tonase_diambil), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk AND is_kerja_tambah = 1) AS tonase_vo,
              (SELECT COALESCE(SUM(progres_ga), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk) AS total_ga,
              (SELECT COALESCE(SUM(progres_modeling), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk) AS total_modeling,
              u_admin.nama_lengkap AS nama_pembuat
              FROM spk 
              LEFT JOIN users u_admin ON spk.id_admin = u_admin.id_user
              WHERE spk.id_spk = $id_spk";
$result_spk = $conn->query($query_spk);

if ($result_spk->num_rows <= 0) {
    echo "<script>alert('Data SPK tidak ditemukan!'); window.location='dashboard';</script>";
    exit;
}

$spk = $result_spk->fetch_assoc();

// KALKULASI PROPORSI PERSENTASE DUA BAR GRAFIK
$total_target   = (float)$spk['total_tonase'];
$tonase_utama   = (float)$spk['tonase_utama'];
$tonase_vo      = (float)$spk['tonase_vo'];
$total_akumulasi = $tonase_utama + $tonase_vo;

// Persentase porsi masing-masing terhadap target awal
$persen_utama_real = ($total_target > 0) ? round(($tonase_utama / $total_target) * 100, 2) : 0;
$persen_vo_real    = ($total_target > 0) ? round(($tonase_vo / $total_target) * 100, 2) : 0;
$persen_total_real = ($total_target > 0) ? round(($total_akumulasi / $total_target) * 100, 2) : 0;

// Batasi tampilan lebar bar utama maksimal 100% agar bar VO bersambung rapi
$width_bar_utama = min($persen_utama_real, 100);
$width_bar_vo    = $persen_vo_real; 

$persen_ga       = min(intval($spk['total_ga']), 100);
$persen_modeling = min(intval($spk['total_modeling']), 100);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking SPK - <?= $spk['no_spk']; ?></title>
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

        .glass-card-success { border-left: 6px solid #10b981 !important; }
        .glass-card-primary { border-left: 6px solid var(--primary-indigo) !important; }
        .glass-card-amber { border-left: 6px solid var(--amber-accent) !important; }
        .glass-card-danger { border-left: 6px solid #ef4444 !important; }

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

        .btn-back-steel {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(147, 51, 234, 0.1) 100%) !important;
            border: 1.5px solid rgba(79, 70, 229, 0.3) !important;
            color: var(--primary-indigo) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
            transition: all 0.3s ease;
        }

        .btn-back-steel:hover {
            background: linear-gradient(135deg, var(--primary-indigo) 0%, var(--primary-indigo-dark) 100%) !important;
            border-color: var(--primary-indigo-dark) !important;
            color: #fff !important;
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
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

        .icon-glow-purple {
            background: linear-gradient(135deg, rgba(147, 51, 234, 0.1) 0%, rgba(126, 34, 206, 0.15) 100%) !important;
            color: var(--secondary-purple) !important;
            border: 2px solid rgba(147, 51, 234, 0.2) !important;
            box-shadow: 0 8px 24px -4px rgba(147, 51, 234, 0.2) !important;
        }

        .icon-glow-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.15) 100%) !important;
            color: #ef4444 !important;
            border: 2px solid rgba(239, 68, 68, 0.2) !important;
            box-shadow: 0 8px 24px -4px rgba(239, 68, 68, 0.2) !important;
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

        .btn-outline-success {
            border: 2px solid #10b981 !important;
            color: #10b981 !important;
            background: rgba(16, 185, 129, 0.05);
        }
        
        .btn-outline-success:hover {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
            transform: translateY(-2px);
        }

        .btn-outline-danger {
            border: 2px solid #ef4444 !important;
            color: #ef4444 !important;
            background: rgba(239, 68, 68, 0.05);
        }
        
        .btn-outline-danger:hover {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            color: white !important;
            box-shadow: 0 4px 16px rgba(239, 68, 68, 0.3);
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

        /* STYLING TIMELINE GLASSMORPHISM */
        .timeline-steps { position: relative; padding-left: 30px; border-left: 2px dashed rgba(79, 70, 229, 0.3); }
        .timeline-item { position: relative; margin-bottom: 24px; }
        .timeline-icon { position: absolute; left: -41px; top: 4px; width: 22px; height: 22px; border-radius: 50%; background-color: #fff; border: 4px solid var(--primary-indigo); box-shadow: 0 0 12px rgba(79, 70, 229, 0.3); }
        .timeline-item.completed .timeline-icon { border-color: #10b981; background-color: #10b981; box-shadow: 0 0 12px rgba(16, 185, 129, 0.4); }
        .timeline-item.vo-item .timeline-icon { border-color: var(--orange-accent); background-color: var(--orange-accent); box-shadow: 0 0 12px rgba(234, 88, 12, 0.4); }

        .timeline-card-glass {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(249, 250, 251, 0.95) 100%) !important;
            backdrop-filter: blur(12px) !important;
            border: 1px solid rgba(79, 70, 229, 0.15) !important;
            border-radius: 14px !important;
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.08) !important;
            color: var(--gray-900);
        }

        .timeline-card-vo {
            background: linear-gradient(135deg, rgba(254, 243, 199, 0.95) 0%, rgba(253, 230, 138, 0.95) 100%) !important;
            backdrop-filter: blur(12px) !important;
            border: 1px solid rgba(245, 158, 11, 0.4) !important;
            box-shadow: 0 4px 16px rgba(245, 158, 11, 0.15) !important;
            color: var(--gray-900);
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
        .container-responsive-detail {
            width: 100%;
            max-width: 1240px; 
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
        .text-break-custom {
            white-space: pre-line; 
            line-height: 1.6;
            word-wrap: break-word;
            word-break: break-word;
        }

        .badge-vo {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(234, 88, 12, 0.1) 100%);
            color: var(--orange-accent);
            border: 1.5px solid rgba(245, 158, 11, 0.3);
            font-weight: 700;
        }

        @media (prefers-reduced-motion: no-preference) {
            .glass-card, .btn-action-custom, .icon-glow-container, .link-spk-click { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        }
        @media (max-width: 991.98px) {
            .border-end { border-right: none !important; border-bottom: 1px solid rgba(79, 70, 229, 0.15); padding-bottom: 10px; }
        }
        @media (max-width: 767.98px) {
            .table-glass tbody td { padding: 12px; font-size: 0.8rem; }
            .btn-action-custom { padding: 6px 12px !important; font-size: 0.75rem !important; }
            .text-truncate-custom { max-width: 180px; }
            .glass-card { padding: 1.25rem !important; }
            h2 { font-size: 1.5rem !important; }
            h5 { font-size: 1rem !important; }
            .timeline-steps { padding-left: 20px; }
            .timeline-icon { left: -31px; width: 18px; height: 18px; border-width: 3px; }
            .icon-glow-container { width: 48px; height: 48px; font-size: 1.3rem; }
        }
        :focus-visible { outline: 3px solid var(--primary-indigo) !important; outline-offset: 2px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-glass sticky-top shadow-sm mb-4">
    <div class="container-responsive d-flex justify-content-between align-items-center">
        <a class="navbar-brand p-0">
            <div class="company-info-steel">
                <div class="company-name-steel">PT Duta Hita Jaya</div>
                <div class="company-division-steel">ADMIN CONTROL PANEL</div>
            </div>
        </a>
        <div class="navbar-nav align-items-center flex-row gap-2">
            <a class="btn btn-outline-secondary btn-sm rounded-pill px-3.5 fw-semibold d-inline-flex align-items-center" href="dashboard">
                <i class="bi bi-arrow-left me-1.5"></i> <span>Kembali ke Dashboard</span>
            </a>
        </div>
    </div>
</nav>

<div class="container-responsive">
    
    <?= $pesan; ?>

    <!-- HEADER PANEL UTAMA SPK -->
    <div class="glass-card p-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center border-bottom pb-3 mb-3">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-glow-container icon-glow-blue">
                    <i class="bi bi-file-earmark-text-fill"></i>
                </div>
                <div>
                    <span class="text-uppercase text-secondary small fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">Lembar Pelacakan Digital Progres Admin</span>
                    <h2 class="fw-bold text-gray-900 m-0 font-monospace"><?= $spk['no_spk']; ?></h2>
                    <h5 class="text-primary fw-semibold mt-1 mb-0"><?= $spk['nama_proyek']; ?></h5>
                </div>
            </div>
            <div class="text-md-end mt-3 mt-md-0 d-flex flex-column align-items-md-end gap-2">
                <div>
                    <span class="small text-muted d-block mb-1">Status Produksi Sekarang:</span>
                    <?php $badge_status = $spk['status'] == 'On Progress' ? 'bg-success-subtle text-success border border-success-subtle' : ($spk['status'] == 'Paused' ? 'bg-danger-subtle text-danger border border-danger-subtle' : ($spk['status'] == 'Completed' ? 'bg-info-subtle text-info-emphasis border border-info-subtle' : 'bg-light text-secondary border')); ?>
                    <span class="badge <?= $badge_status; ?> px-3 py-2 fs-6 fw-bold text-uppercase rounded-3"><i class="bi bi-info-circle-fill me-1"></i> <?= $spk['status']; ?></span>
                </div>
                <a href="edit_spk?id=<?= $id_spk; ?>" class="btn btn-sm btn-outline-primary fw-semibold rounded-pill px-3.5 py-1.5 shadow-sm bg-white">
                    <i class="bi bi-pencil-square me-1"></i> Edit SPK
                </a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg col-md-6 col-12 border-end">
                <label class="text-muted small d-block">Tingkat Urgensi Proyek</label>
                <?php $urg_cls = $spk['tingkat_urgensi'] == 'Urgent' ? 'text-danger fw-bold' : ($spk['tingkat_urgensi'] == 'High' ? 'text-warning-emphasis fw-bold' : 'text-secondary'); ?>
                <span class="<?= $urg_cls; ?>"><i class="bi bi-exclamation-octagon me-1"></i> Area <?= $spk['tingkat_urgensi']; ?></span>
            </div>
            
            <div class="col-lg col-md-6 col-12 border-end">
                <label class="text-muted small d-block">Perusahaan Client</label>
                <span class="text-primary fw-bold"><i class="bi bi-building me-1"></i> <?= !empty($spk['nama_client']) ? htmlspecialchars($spk['nama_client']) : '-'; ?></span>
            </div>

            <div class="col-lg col-md-6 col-12 border-end">
                <label class="text-muted small d-block">Dibuat Oleh Staf</label>
                <span class="text-gray-900 fw-medium"><i class="bi bi-person-fill-gear me-1"></i> <?= $spk['nama_pembuat']; ?></span>
            </div>

            <div class="col-lg col-md-6 col-12 border-end">
                <label class="text-muted small d-block">Waktu Input Sistem</label>
                <span class="text-gray-900 fw-medium text-nowrap"><i class="bi bi-calendar-check me-1"></i> <?= date('d M Y H:i', strtotime($spk['tgl_input'])); ?> WIB</span>
            </div>

            <div class="col-lg col-md-6 col-12">
                <label class="text-muted small d-block">Target Deadline</label>
                <span class="text-danger fw-bold text-nowrap"><i class="bi bi-clock-fill me-1"></i> <?= date('d M Y H:i', strtotime($spk['deadline'])); ?></span>
            </div>
        </div>

        <!-- PANEL GRAFIK VOLUME DENGAN MEMISAHKAN BAR SPK UTAMA & KERJA TAMBAH (VO) -->
        <div class="mt-4 p-3 rounded-3 border shadow-sm" style="background: rgba(255, 255, 255, 0.7); border-color: rgba(74, 144, 226, 0.2) !important;">
            <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-1">
                <span class="small fw-bold text-gray-900 d-flex align-items-center">
                    <i class="bi bi-pie-chart-fill text-success me-1.5"></i> Akumulasi Volume Shop Drawing (Penentu Status SPK)
                </span>
                <span class="fw-bold text-gray-900 small">
                    Total: <?= number_format($total_akumulasi, 2, ',', '.'); ?> / <?= number_format($total_target, 2, ',', '.'); ?> Kg (<?= $persen_total_real; ?>%)
                </span>
            </div>

            <!-- GRAFIK TUMPANG TINDIH (STACKED PROGRESS BAR) -->
            <div class="progress mb-2" style="height: 16px; background-color: rgba(79, 70, 229, 0.1); border-radius: 8px; overflow: hidden;">
                <!-- 1. BAR HIJAU: SPK UTAMA -->
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: <?= $width_bar_utama; ?>%" title="SPK Utama: <?= number_format($tonase_utama, 2, ',', '.'); ?> Kg">
                    <?php if($width_bar_utama > 15): ?><span style="font-size: 10px;" class="fw-bold">Utama: <?= $persen_utama_real; ?>%</span><?php endif; ?>
                </div>

                <!-- 2. BAR ORANYE: PEKERJAAN TAMBAH / ADDENDUM (VO) -->
                <?php if ($width_bar_vo > 0) : ?>
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning text-gray-900 fw-bold" role="progressbar" style="width: <?= $width_bar_vo; ?>%; background-color: #f59e0b !important;" title="Pekerjaan Tambah (VO): +<?= number_format($tonase_vo, 2, ',', '.'); ?> Kg">
                        <span style="font-size: 10px;">+VO: <?= $persen_vo_real; ?>%</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- LEGENDA & RINCIAN ANGKA DISPONSORKAN DI BAWAH GRAFIK -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 px-1">
                <div class="d-flex align-items-center gap-3" style="font-size: 11px;">
                    <span class="d-flex align-items-center gap-1 fw-bold text-success">
                        <span style="width: 10px; height: 10px; background-color: #10b981; border-radius: 2px; display: inline-block;"></span>
                        SPK Utama: <?= number_format($tonase_utama, 2, ',', '.'); ?> Kg (<?= $persen_utama_real; ?>%)
                    </span>
                    
                    <?php if ($tonase_vo > 0) : ?>
                        <span class="d-flex align-items-center gap-1 fw-bold text-warning-emphasis">
                            <span style="width: 10px; height: 10px; background-color: #f59e0b; border-radius: 2px; display: inline-block;"></span>
                            Pekerjaan Tambah (VO): +<?= number_format($tonase_vo, 2, ',', '.'); ?> Kg (+<?= $persen_vo_real; ?>%)
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($tonase_vo > 0) : ?>
                    <span class="badge badge-vo px-2.5 py-1 rounded-pill" style="font-size: 11px;">
                        <i class="bi bi-plus-square-fill me-1"></i> Addendum Terdeteksi
                    </span>
                <?php endif; ?>
            </div>

            <div class="row g-3 pt-2 border-top mb-2">
                <div class="col-md-6 col-12">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-secondary fw-semibold" style="font-size: 12px;"><i class="bi bi-card-text text-teal me-1"></i> Progres Gambar GA</span>
                        <span class="fw-bold text-gray-900" style="font-size: 12px;"><?= $persen_ga; ?>% / 100%</span>
                    </div>
                    <div class="progress" style="height: 8px; background-color: rgba(79, 70, 229, 0.1);">
                        <div class="progress-bar bg-info" role="progressbar" style="width: <?= $persen_ga; ?>%; background-color: #0d9488 !important;"></div>
                    </div>
                </div>

                <div class="col-md-6 col-12">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-secondary fw-semibold" style="font-size: 12px;"><i class="bi bi-box-perspective text-primary me-1"></i> Progres Struktur 3D Modeling</span>
                        <span class="fw-bold text-gray-900" style="font-size: 12px;"><?= $persen_modeling; ?>% / 100%</span>
                    </div>
                    <div class="progress" style="height: 8px; background-color: rgba(79, 70, 229, 0.1);">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $persen_modeling; ?>%"></div>
                    </div>
                </div>
            </div>

            <div class="pt-2 border-top mt-2">
                <?php if (!empty($spk['link_drive'])) : ?>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <span class="small fw-bold text-secondary text-uppercase" style="font-size: 11px;"><i class="bi bi-cloud-check-fill text-primary me-1"></i> File Sharing Aset Cloud :</span>
                        <a href="<?= $spk['link_drive']; ?>" target="_blank" class="btn btn-xs btn-success fw-bold px-3 py-1.5 shadow-sm rounded-pill d-inline-flex align-items-center" style="font-size: 12px;">
                            <i class="bi bi-google me-1.5"></i> Buka Folder Google Drive <i class="bi bi-box-arrow-up-right small ms-1.5"></i>
                        </a>
                    </div>
                <?php else : ?>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <span class="small fw-bold text-secondary text-uppercase" style="font-size: 11px;"><i class="bi bi-cloud-slash-fill text-muted me-1"></i> File Sharing Aset Cloud :</span>
                        <span class="text-muted small italic bg-white px-2 py-1 rounded border" style="font-size: 11px;"><i class="bi bi-info-circle me-1"></i> Tautan belum ditambahkan Admin</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- RUANG LINGKUP & INSTRUKSI SPK -->
    <div class="glass-card p-4 mb-4">
        <h5 class="fw-bold text-gray-900 mb-2 d-flex align-items-center">
            <i class="bi bi-card-text text-primary me-2"></i> Ruang Lingkup &amp; Catatan Instruksi SPK
        </h5>
        <?php $clean_instruksi_admin = str_replace(["\r\n", "\r", "\n"], " ", $spk['deskripsi_tugas']); ?>
        <div class="p-3 rounded-3 border text-gray-900 text-break-custom" style="background: rgba(255, 255, 255, 0.7); border-color: rgba(74, 144, 226, 0.2) !important;">
            <?= htmlspecialchars($clean_instruksi_admin); ?>
        </div>
    </div>

    <!-- LINIMASA HISTORI HARIAN DRAFTER DENGAN OPERASIONAL ADMIN -->
    <div class="glass-card p-4 mb-5">
        <h5 class="fw-bold text-gray-900 mb-4 border-bottom pb-2 d-flex align-items-center">
            <i class="bi bi-arrow-down-up text-primary me-2"></i> Linimasa Histori &amp; Log Kontribusi Harian Drafter
        </h5>

        <?php
        $log_progres = $conn->query("SELECT spk_progres.*, users.nama_lengkap FROM spk_progres JOIN users ON spk_progres.id_user = users.id_user WHERE spk_progres.id_spk = $id_spk ORDER BY spk_progres.tgl_update DESC");
        if ($log_progres && $log_progres->num_rows > 0) :
        ?>
            <div class="timeline-steps ms-2 ms-sm-3">
                <?php while ($log = $log_progres->fetch_assoc()) : ?>
                    <?php 
                        $is_vo = ((int)$log['is_kerja_tambah'] === 1);
                        $is_kilo_exist = ($log['tonase_diambil'] > 0 || $log['progres_ga'] > 0 || $log['progres_modeling'] > 0);
                        
                        $item_class = $is_vo ? 'vo-item' : ($is_kilo_exist ? 'completed' : '');
                        $clean_log_admin_ket = str_replace(["\r\n", "\r", "\n"], " ", $log['keterangan_kerja']);
                    ?>
                    <div class="timeline-item <?= $item_class; ?>">
                        <div class="timeline-icon"></div>
                        <div class="p-3 timeline-card-glass">
                            <div class="d-flex justify-content-between align-items-start align-items-sm-center mb-2 border-bottom pb-2 flex-column flex-sm-row gap-2">
                                <div class="d-flex align-items-center flex-wrap gap-1">
                                    <span class="badge bg-primary-subtle text-primary border px-2 py-1 small" style="font-size: 10px;"><i class="bi bi-person-fill"></i> Drafter</span>
                                    <strong class="text-gray-900 fs-6 me-1"><?= $log['nama_lengkap']; ?></strong>
                                    
                                    <?php if ($is_vo) : ?>
                                        <span class="badge bg-warning text-gray-900 border border-warning px-2 py-1 fw-bold rounded-2" style="font-size: 10px;">
                                            <i class="bi bi-plus-circle-fill text-danger me-1"></i> PEKERJAAN TAMBAH / ADDENDUM (VO)
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex align-items-center gap-3">
                                    <span class="text-muted font-monospace small" style="font-size: 11px;"><i class="bi bi-clock me-1"></i><?= date('d M Y - H:i', strtotime($log['tgl_update'])); ?> WIB</span>
                                    
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-outline-primary btn-xs px-2 py-1 rounded-2 btn-edit-log" style="font-size: 11px;"
                                                data-bs-toggle="modal" data-bs-target="#modalEditLog"
                                                data-id="<?= $log['id_progres']; ?>"
                                                data-tonase="<?= $log['tonase_diambil']; ?>"
                                                data-ga="<?= $log['progres_ga']; ?>"
                                                data-modeling="<?= $log['progres_modeling']; ?>"
                                                data-ket="<?= htmlspecialchars($clean_log_admin_ket); ?>"
                                                title="Edit Data Log">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="detail_spk?id=<?= $id_spk; ?>&aksi=hapus_log&id_log=<?= $log['id_progres']; ?>" 
                                           class="btn btn-outline-danger btn-xs px-2 py-1 rounded-2" style="font-size: 11px;"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus permanen log kontribusi harian drafter ini? Tindakan ini akan mengubah kalkulasi akumulasi progres.')" 
                                           title="Hapus Log">
                                             <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-secondary text-break-custom" style="font-size: 0.9rem; color: #475569 !important;">
                                <?= htmlspecialchars($clean_log_admin_ket); ?>
                            </div>

                            <?php if ($is_kilo_exist) : ?>
                                <div class="mt-2 pt-2 border-top d-flex gap-1.5 flex-wrap">
                                    <?php if ($log['tonase_diambil'] > 0) : ?>
                                        <span class="badge <?= $is_vo ? 'bg-warning text-gray-900' : 'bg-success'; ?> px-2.5 py-1.5 rounded-2" style="font-size: 11px;">
                                            <i class="bi bi-plus-circle-fill me-1"></i> Shop Drawing: +<?= number_format($log['tonase_diambil'], 2, ',', '.'); ?> Kg
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($log['progres_ga'] > 0) : ?>
                                        <span class="badge px-2.5 py-1.5 rounded-2" style="font-size: 11px; background-color: #ccfbf1 !important; color: #115e59 !important; border: 1px solid #99f6e4;"><i class="bi bi-plus-circle-fill me-1"></i> GA: +<?= $log['progres_ga']; ?>%</span>
                                    <?php endif; ?>
                                    <?php if ($log['progres_modeling'] > 0) : ?>
                                        <span class="badge bg-primary-subtle text-primary px-2.5 py-1.5 rounded-2" style="font-size: 11px; border: 1px solid #bfdbfe;"><i class="bi bi-plus-circle-fill me-1"></i> Modeling: +<?= $log['progres_modeling']; ?>%</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-clipboard-x fs-1 d-block mb-2 text-black-50"></i> Belum ada laporan harian masuk dari tim drafter.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL EDIT LOG -->
<div class="modal fade" id="modalEditLog" tabindex="-1" aria-labelledby="labelModalEdit" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
            <form action="detail_spk?id=<?= $id_spk; ?>" method="POST">
                <input type="hidden" name="action_type" value="update_log">
                <input type="hidden" name="id_progres" id="modal_id_progres">
                
                <div class="modal-header bg-light py-2.5">
                    <h6 class="modal-title fw-bold text-gray-900" id="labelModalEdit">
                        <i class="bi bi-pencil-square text-primary me-1.5"></i> Koreksi Nilai Log Kontribusi
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body py-3" style="font-size: 0.85rem;">
                    <div class="mb-2.5">
                        <label class="form-label fw-bold text-secondary mb-1">Capaian Shop Drawing (Kg)</label>
                        <input type="number" name="tonase_diambil" id="modal_tonase" class="form-control form-control-sm rounded-2" step="0.01" min="0" required>
                    </div>
                    
                    <div class="row g-2 mb-2.5">
                        <div class="col-6">
                            <label class="form-label fw-bold text-secondary mb-1">Cicilan GA Layout (%)</label>
                            <input type="number" name="progres_ga" id="modal_ga" class="form-control form-control-sm rounded-2" min="0" max="100" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold text-secondary mb-1">Cicilan 3D Modeling (%)</label>
                            <input type="number" name="progres_modeling" id="modal_modeling" class="form-control form-control-sm rounded-2" min="0" max="100" required>
                        </div>
                    </div>
                    
                    <div>
                        <label class="form-label fw-bold text-secondary mb-1">Deskripsi Catatan Kerja Koreksi</label>
                        <textarea name="keterangan_kerja" id="modal_ket" class="form-control form-control-sm rounded-2" rows="3" placeholder="Tulis rincian perbaikan berkas..." required></textarea>
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-2 d-flex justify-content-end gap-1.5 border-0">
                    <button type="button" class="btn btn-secondary btn-sm px-3 rounded-pill" data-bs-dismiss="modal" style="font-size: 11px;">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3 rounded-pill fw-semibold" style="font-size: 11px;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SCRIPT BOOTSTRAP LOKAL -->
<script src="../bootstrap.bundle.min.js"></script>
<script>
    const editButtons = document.querySelectorAll('.btn-edit-log');
    if (editButtons.length > 0) {
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const idLog = this.getAttribute('data-id');
                const tonase = this.getAttribute('data-tonase');
                const ga = this.getAttribute('data-ga');
                const modeling = this.getAttribute('data-modeling');
                const ketReal = this.getAttribute('data-ket');

                document.getElementById('modal_id_progres').value = idLog;
                document.getElementById('modal_tonase').value = tonase;
                document.getElementById('modal_ga').value = ga;
                document.getElementById('modal_modeling').value = modeling;
                document.getElementById('modal_ket').value = getDecodedString(ketReal);
            });
        });
    }

    function getDecodedString(str) {
        const txt = document.createElement('textarea');
        txt.innerHTML = str;
        return txt.value;
    }
</script>

<!-- KOMPONEN CHAT INTERNAL & POP-UP ALERT DEADLINE -->
<?php include '../chat_and_alert_component.php'; ?>

</body>
</html>
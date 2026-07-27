<?php
session_start();
require '../config.php';

// Proteksi halaman: Hanya Admin yang boleh masuk
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login");
    exit;
}

// PARAMETER FILTER & PENCARIAN
$filter_waktu = isset($_GET['filter_waktu']) ? $_GET['filter_waktu'] : 'all';
$search       = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';

$where_clauses = [];

if ($filter_waktu === 'daily') {
    $where_clauses[] = " DATE(spk.tgl_input) = CURDATE() ";
} elseif ($filter_waktu === 'weekly') {
    $where_clauses[] = " YEARWEEK(spk.tgl_input, 1) = YEARWEEK(CURDATE(), 1) ";
} elseif ($filter_waktu === 'monthly') {
    $where_clauses[] = " MONTH(spk.tgl_input) = MONTH(CURDATE()) AND YEAR(spk.tgl_input) = YEAR(CURDATE()) ";
}

if (!empty($search)) {
    $where_clauses[] = " (spk.no_spk LIKE '%$search%' OR spk.nama_proyek LIKE '%$search%' OR spk.nama_client LIKE '%$search%') ";
}

$where_clause = "";
if (count($where_clauses) > 0) {
    $where_clause = " WHERE " . implode(" AND ", $where_clauses);
}

// PAGINATION SYSTEM
$batas = 10;
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

$query_hitung = "SELECT COUNT(*) AS total FROM spk $where_clause";
$total_data = $conn->query($query_hitung)->fetch_assoc()['total'];
$total_halaman = ceil($total_data / $batas);

// Query penarik data SPK + Pemisahan Tonase Utama & VO untuk grafik
$query_spk = "SELECT spk.*, 
              (SELECT COALESCE(SUM(tonase_diambil), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk AND (is_kerja_tambah = 0 OR is_kerja_tambah IS NULL)) AS tonase_utama,
              (SELECT COALESCE(SUM(tonase_diambil), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk AND is_kerja_tambah = 1) AS tonase_vo,
              (SELECT COALESCE(SUM(progres_ga), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk) AS total_ga,
              (SELECT COALESCE(SUM(progres_modeling), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk) AS total_modeling,
              (
                  SELECT GROUP_CONCAT(DISTINCT users.nama_lengkap SEPARATOR ', ') 
                  FROM spk_progres 
                  JOIN users ON spk_progres.id_user = users.id_user 
                  WHERE spk_progres.id_spk = spk.id_spk
              ) AS nama_tim_gabungan,
              u_pencetus.nama_lengkap AS nama_pencetus
              FROM spk 
              LEFT JOIN users u_pencetus ON spk.id_drafter = u_pencetus.id_user 
              $where_clause
              ORDER BY spk.tgl_input DESC 
              LIMIT $halaman_awal, $batas";
$list_spk = $conn->query($query_spk);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PT Duta Hita Jaya</title>
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

<nav class="navbar navbar-expand-lg navbar-glass sticky-top shadow-sm">
    <div class="container-responsive d-flex justify-content-between align-items-center">
        <a class="navbar-brand p-0">
            <div class="company-info-steel">
                <div class="company-name-steel">PT Duta Hita Jaya</div>
                <div class="company-division-steel">ADMIN CONTROL PANEL</div>
            </div>
        </a>
        <div class="navbar-nav align-items-center flex-row gap-2">
            <span class="user-badge-steel px-3 py-2 rounded-pill d-inline-flex align-items-center">
                <i class="bi bi-person-badge-fill me-2 fs-6" style="color: var(--primary-indigo);"></i> 
                <span style="color: #6B7280;">Admin:</span> 
                <strong class="ms-2" style="color: var(--primary-indigo-dark);"><?= $_SESSION['nama']; ?></strong>
            </span>
            <a class="btn btn-logout-steel btn-sm rounded-pill px-3 fw-semibold d-inline-flex align-items-center" href="../logout">
                <i class="bi bi-box-arrow-right me-1.5"></i> Keluar
            </a>
        </div>
    </div>
</nav>

<div class="container-responsive my-4">
    
    <!-- HEADER DAERAH DASHBOARD -->
    <div class="glass-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-glow-container icon-glow-blue">
                    <i class="bi bi-folder-fill"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-gray-900 m-0" style="letter-spacing: -0.5px;">Manajemen Surat Perintah Kerja (SPK)</h3>
                    <p class="text-muted small m-0">Klik langsung pada <b>Nomor SPK</b> untuk membuka monitoring riwayat kerja harian.</p>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-outline-success btn-sm rounded-pill fw-semibold px-3.5 py-2 d-inline-flex align-items-center" onclick="openExportModal('excel')">
                    <i class="bi bi-file-earmark-excel me-1.5 fs-6"></i> Excel
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill fw-semibold px-3.5 py-2 d-inline-flex align-items-center" onclick="openExportModal('pdf')">
                    <i class="bi bi-file-earmark-pdf me-1.5 fs-6"></i> PDF
                </button>
                <a href="tambah_spk" class="btn btn-grad-blue btn-sm rounded-pill fw-semibold px-3.5 py-2 d-inline-flex align-items-center">
                    <i class="bi bi-plus-circle-fill me-1.5"></i> SPK Baru
                </a>
            </div>
        </div>
    </div>

    <!-- CARI KATA KUNCI FORM -->
    <div class="glass-card p-3 mb-3">
        <form action="dashboard" method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="filter_waktu" value="<?= htmlspecialchars($filter_waktu); ?>">
            <div class="col-md-9 col-12">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted rounded-start-pill ps-3"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 pe-3 rounded-end-pill" placeholder="Tulis Kode SPK, Nama Proyek, atau Client..." value="<?= htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3 col-12 d-flex gap-2">
                <button type="submit" class="btn btn-grad-blue btn-sm rounded-pill w-100 fw-semibold d-inline-flex align-items-center justify-content-center">
                    <i class="bi bi-search me-1"></i> Cari Data
                </button>
                <?php if(!empty($search)): ?>
                    <a href="dashboard?filter_waktu=<?= $filter_waktu; ?>" class="btn btn-outline-secondary btn-sm rounded-pill w-50 text-center d-inline-flex align-items-center justify-content-center">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- FILTER WAKTU TOMBOL -->
    <div class="glass-card p-3 mb-4">
        <div class="d-flex gap-2 flex-wrap">
            <a href="dashboard?filter_waktu=all&search=<?= urlencode($search); ?>" class="btn btn-sm rounded-pill px-3.5 fw-semibold <?= ($filter_waktu == 'all') ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-list-ul me-1"></i> Semua SPK
            </a>
            <a href="dashboard?filter_waktu=daily&search=<?= urlencode($search); ?>" class="btn btn-sm rounded-pill px-3.5 fw-semibold <?= ($filter_waktu == 'daily') ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-calendar-day me-1"></i> Hari Ini
            </a>
            <a href="dashboard?filter_waktu=weekly&search=<?= urlencode($search); ?>" class="btn btn-sm rounded-pill px-3.5 fw-semibold <?= ($filter_waktu == 'weekly') ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-calendar-week me-1"></i> Minggu Ini
            </a>
            <a href="dashboard?filter_waktu=monthly&search=<?= urlencode($search); ?>" class="btn btn-sm rounded-pill px-3.5 fw-semibold <?= ($filter_waktu == 'monthly') ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-calendar-month me-1"></i> Bulan Ini
            </a>
        </div>
    </div>

    <!-- TABEL UTAMA SPK -->
    <div class="glass-card p-4">
        <div class="table-responsive">
            <table class="table table-glass align-middle mb-0">
                <thead>
                    <tr>
                        <th>No. SPK</th>
                        <th>Nama Proyek &amp; Client</th>
                        <th>Urgensi</th>
                        <th>Waktu Mulai</th>
                        <th>Deadline</th>
                        <th>Waktu Selesai</th>
                        <th>Drafter Terlibat</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($list_spk->num_rows > 0) : ?>
                        <?php while ($spk = $list_spk->fetch_assoc()) : ?>
                            <?php 
                                $badge_urgensi = ($spk['tingkat_urgensi'] == 'Urgent') ? 'bg-danger-subtle text-danger border border-danger-subtle' : (($spk['tingkat_urgensi'] == 'High') ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-light text-secondary border');
                                $badge_status = $spk['status'] == 'On Progress' ? 'bg-success-subtle text-success border border-success-subtle' : ($spk['status'] == 'Paused' ? 'bg-danger-subtle text-danger border border-danger-subtle' : ($spk['status'] == 'Completed' ? 'bg-info-subtle text-info-emphasis border border-info-subtle' : 'bg-light text-secondary border'));
                                $is_overdue = ($spk['status'] !== 'Completed' && !empty($spk['deadline']) && strtotime($spk['deadline']) < time());
                                $tampilkan_drafter_admin = !empty($spk['nama_tim_gabungan']) ? $spk['nama_tim_gabungan'] : $spk['nama_pencetus'];
                                
                                // KALKULASI TONASE TERPISAH (UTAMA vs VO)
                                $total_target    = (float)$spk['total_tonase'];
                                $tonase_utama    = (float)$spk['tonase_utama'];
                                $tonase_vo       = (float)$spk['tonase_vo'];
                                $total_akumulasi = $tonase_utama + $tonase_vo;

                                $persen_utama = ($total_target > 0) ? round(($tonase_utama / $total_target) * 100, 2) : 0;
                                $persen_vo    = ($total_target > 0) ? round(($tonase_vo / $total_target) * 100, 2) : 0;
                                $persen_total = ($total_target > 0) ? round(($total_akumulasi / $total_target) * 100, 2) : 0;

                                $width_bar_utama = min($persen_utama, 100);
                                $width_bar_vo    = $persen_vo;
                            ?>
                            <tr class="<?= $is_overdue ? 'table-danger-subtle' : ''; ?>">
                                <td>
                                    <a href="detail_spk?id=<?= $spk['id_spk']; ?>" class="link-spk-click font-monospace"><i class="bi bi-box-arrow-up-right me-1"></i><?= $spk['no_spk']; ?></a>
                                    
                                    <?php if (!empty($spk['link_drive'])) : ?>
                                        <a href="<?= $spk['link_drive']; ?>" target="_blank" class="ms-1.5 text-success" title="Buka Folder Google Drive Aset"><i class="bi bi-google"></i></a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-semibold text-gray-900 d-block"><?= $spk['nama_proyek']; ?></span>
                                    <span class="text-primary small d-block mb-1 fw-medium"><i class="bi bi-building me-1"></i>Client: <?= htmlspecialchars($spk['nama_client']); ?></span>
                                    
                                    <!-- PROGRESS BAR MINIS DENGAN SEGMEN WARNA HIJAU & ORANYE -->
                                    <div class="mt-1" style="min-width: 210px;">
                                        <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 10px;">
                                            <span class="fw-bold text-secondary">SD: <?= number_format($total_akumulasi, 2, ',', '.'); ?> / <?= number_format($total_target, 2, ',', '.'); ?> Kg</span>
                                            <span class="fw-bold <?= ($persen_total > 100) ? 'text-warning-emphasis' : 'text-success'; ?>">(<?= $persen_total; ?>%)</span>
                                        </div>
                                        <div class="progress" style="height: 8px; background-color: rgba(79, 70, 229, 0.1); border-radius: 6px; overflow: hidden;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $width_bar_utama; ?>%" title="Utama: <?= number_format($tonase_utama, 2, ',', '.'); ?> Kg"></div>
                                            <?php if($width_bar_vo > 0): ?>
                                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $width_bar_vo; ?>%; background-color: #f59e0b !important;" title="VO: +<?= number_format($tonase_vo, 2, ',', '.'); ?> Kg"></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge <?= $badge_urgensi; ?> px-2.5 py-1 rounded-2"><?= $spk['tingkat_urgensi']; ?></span></td>
                                <td class="text-secondary small"><?= $spk['tgl_mulai'] ? date('d M Y H:i', strtotime($spk['tgl_mulai'])) : '-'; ?></td>
                                <td class="text-secondary small fw-medium"><i class="bi bi-clock me-1"></i><?= date('d M Y H:i', strtotime($spk['deadline'])); ?></td>
                                <td class="text-secondary small"><?= $spk['tgl_selesai'] ? date('d M Y H:i', strtotime($spk['tgl_selesai'])) : '-'; ?></td>
                                <td class="small fw-medium"><i class="bi bi-people me-1 text-primary"></i><?= !empty($tampilkan_drafter_admin) ? $tampilkan_drafter_admin : 'Belum diambil'; ?></td>
                                <td><span class="badge <?= $badge_status; ?> px-2.5 py-1 rounded-2"><?= $spk['status']; ?></span></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1.5 justify-content-center">
                                        <a href="edit_spk?id=<?= $spk['id_spk']; ?>" class="btn btn-primary btn-sm rounded-2 d-inline-flex align-items-center" title="Edit SPK">
                                            <i class="bi bi-pencil-square me-1"></i> Edit
                                        </a>
                                        <a href="hapus_spk?id=<?= $spk['id_spk']; ?>" class="btn btn-danger btn-sm rounded-2 d-inline-flex align-items-center" onclick="return confirm('Hapus SPK ini?')" title="Hapus SPK">
                                            <i class="bi bi-trash me-1"></i> Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr><td colspan="9" class="text-center text-muted small py-4">Tidak ada data SPK.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_halaman > 1) : ?>
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top" style="border-color: rgba(74, 144, 226, 0.2) !important;">
                <small class="text-muted fw-medium">Halaman <b class="text-gray-900"><?= $halaman; ?></b> dari <b class="text-gray-900"><?= $total_halaman; ?></b></small>
                <nav>
                    <ul class="pagination pagination-sm m-0">
                        <li class="page-item <?= ($halaman <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link rounded-pill" href="dashboard?halaman=<?= $halaman - 1; ?>&filter_waktu=<?= $filter_waktu; ?>&search=<?= urlencode($search); ?>">
                                <i class="bi bi-chevron-left"></i> Sebelumnya
                            </a>
                        </li>
                        <?php for ($x = 1; $x <= $total_halaman; $x++) : ?>
                            <li class="page-item <?= ($halaman == $x) ? 'active' : '' ?>">
                                <a class="page-link" href="dashboard?halaman=<?= $x; ?>&filter_waktu=<?= $filter_waktu; ?>&search=<?= urlencode($search); ?>"><?= $x; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : '' ?>">
                            <a class="page-link rounded-pill" href="dashboard?halaman=<?= $halaman + 1; ?>&filter_waktu=<?= $filter_waktu; ?>&search=<?= urlencode($search); ?>">
                                Berikutnya <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL EKSPOR DATA -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content shadow-lg border-0" style="border-radius: 18px;">
            <div class="modal-header border-0 bg-light">
                <h6 class="modal-title fw-bold" id="exportModalLabel"><i class="bi bi-calendar-range text-primary me-2"></i>Tentukan Jangka Periode Data</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEksporDinamis" method="GET" target="_blank">
                <input type="hidden" name="filter_waktu" value="<?= htmlspecialchars($filter_waktu); ?>">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search); ?>">
                
                <div class="modal-body py-3">
                    <p class="text-muted small mb-3">Kosongkan kolom pilihan tanggal jika Anda berencana mengunduh seluruh database historis.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-gray-900 mb-1">Dari Tanggal (Mulai)</label>
                        <input type="date" name="tgl_awal" id="modal_tgl_awal" class="form-control form-control-sm">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-gray-900 mb-1">Sampai Tanggal (Akhir)</label>
                        <input type="date" name="tgl_akhir" id="modal_tgl_akhir" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3 rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitModal" class="btn btn-sm px-3.5 text-white fw-semibold rounded-pill">Unduh Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SCRIPT BOOTSTRAP LOKAL -->
<script src="../bootstrap.bundle.min.js"></script>
<script>
    function openExportModal(type) {
        const form = document.getElementById('formEksporDinamis');
        const submitBtn = document.getElementById('btnSubmitModal');
        
        document.getElementById('modal_tgl_awal').value = '';
        document.getElementById('modal_tgl_akhir').value = '';

        if(type === 'excel') {
            form.action = 'ekspor_excel';
            submitBtn.className = 'btn btn-sm btn-success text-white fw-semibold px-3.5 rounded-pill';
            submitBtn.innerHTML = '<i class="bi bi-file-earmark-excel-fill me-1.5"></i>Unduh Excel';
        } else if(type === 'pdf') {
            form.action = 'ekspor_pdf';
            submitBtn.className = 'btn btn-sm btn-danger text-white fw-semibold px-3.5 rounded-pill';
            submitBtn.innerHTML = '<i class="bi bi-file-earmark-pdf-fill me-1.5"></i>Cetak PDF';
        }
        
        var myModal = new bootstrap.Modal(document.getElementById('exportModal'));
        myModal.show();
    }
</script>

<!-- PANGGIL KOMPONEN CHAT CORPORATE & POP-UP ALERT DEADLINE (H-3) -->
<?php include '../chat_and_alert_component.php'; ?>

</body>
</html>
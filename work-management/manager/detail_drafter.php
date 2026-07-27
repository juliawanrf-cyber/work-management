<?php
session_start();
require '../config.php';

// Proteksi halaman: Hanya Manager yang boleh masuk
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'manager') {
    header("Location: ../login");
    exit;
}

$id_drafter = isset($_GET['id']) ? intval($_GET['id']) : 0;
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'daily'; // daily, weekly, monthly

// Ambil Informasi Dasar Drafter Terkait
$query_user = $conn->query("SELECT * FROM users WHERE id_user = $id_drafter AND role = 'drafter'");
if ($query_user->num_rows === 0) {
    echo "<script>alert('Data karyawan drafter tidak ditemukan!'); window.location='dashboard';</script>";
    exit;
}
$drafter_info = $query_user->fetch_assoc();

// Formulasi Filter Jangka Waktu Rekam Kerja Karyawan
$where_waktu = "";
$label_periode = "";
if ($filter === 'daily') {
    $where_waktu = " AND DATE(p.tgl_update) = CURDATE() ";
    $label_periode = "HARI INI (DAILY)";
} elseif ($filter === 'weekly') {
    $where_waktu = " AND YEARWEEK(p.tgl_update, 1) = YEARWEEK(CURDATE(), 1) ";
    $label_periode = "MINGGU INI (WEEKLY)";
} elseif ($filter === 'monthly') {
    $where_waktu = " AND MONTH(p.tgl_update) = MONTH(CURDATE()) AND YEAR(p.tgl_update) = YEAR(CURDATE()) ";
    $label_periode = "BULAN INI (MONTHLY)";
}

// 1. Ambil Log Audit Detail Kumulatif (Shop Drawing, GA, Modeling)
$query_log = "SELECT p.*, s.no_spk, s.nama_proyek, s.nama_client
              FROM spk_progres p
              JOIN spk s ON p.id_spk = s.id_spk
              WHERE p.id_user = $id_drafter $where_waktu
              ORDER BY p.tgl_update DESC";
$list_logs = $conn->query($query_log);

// 2. Ambil Akumulasi Counter Atas Khusus Untuk Drafter Ini Sesuai Filter Periode
$stats_drafter = $conn->query("SELECT 
    COUNT(DISTINCT p.id_spk) AS total_spk_disentuh,
    COALESCE(SUM(p.tonase_diambil), 0) AS total_tonase_diselesaikan,
    COALESCE(SUM(p.progres_ga), 0) AS total_cicilan_ga,
    COALESCE(SUM(p.progres_modeling), 0) AS total_cicilan_model
    FROM spk_progres p
    WHERE p.id_user = $id_drafter $where_waktu")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Kinerja Drafter - <?= htmlspecialchars($drafter_info['nama_lengkap']); ?></title>
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

        .glass-card, .card-stat-glass {
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

        .glass-card:hover, .card-stat-glass:hover {
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

        .btn-dash-pill {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(147, 51, 234, 0.1) 100%) !important;
            border: 1.5px solid rgba(79, 70, 229, 0.3) !important;
            color: var(--primary-indigo) !important;
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.1);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .btn-dash-pill:hover {
            background: linear-gradient(135deg, var(--primary-indigo) 0%, var(--primary-indigo-dark) 100%) !important;
            border-color: var(--primary-indigo-dark) !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.3);
            color: #fff !important;
        }

        /* TITLE CARD HEADER */
        .header-title-box {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 15px 0;
            border-bottom: 2px solid rgba(79, 70, 229, 0.15);
            margin-bottom: 1.5rem;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--gray-900);
            letter-spacing: -0.3px;
            margin-bottom: 4px;
            text-shadow: none;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0;
        }

        .icon-square-blue {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            border: 2px solid rgba(79, 70, 229, 0.2);
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(147, 51, 234, 0.15) 100%);
            box-shadow: 0 8px 24px -4px rgba(79, 70, 229, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: var(--primary-indigo);
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .icon-square-blue:hover {
            transform: translateY(-4px) scale(1.05);
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
        <a class="navbar-brand p-0" href="dashboard">
            <div class="company-info-steel">
                <div class="company-name-steel">PT Duta Hita Jaya</div>
                <div class="company-division-steel">MANAGER EXECUTIVE PANEL</div>
            </div>
        </a>
        <a class="btn btn-outline-secondary btn-sm rounded-pill px-3.5 fw-semibold d-inline-flex align-items-center" href="dashboard">
            <i class="bi bi-arrow-left me-1.5"></i> <span>Kembali ke Dashboard</span>
        </a>
    </div>
</nav>

<div class="container-responsive my-4">
    
    <!-- BANNER PROFILE DRAFTER -->
    <div class="glass-card p-4 mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-glow-container icon-glow-blue fs-4 fw-bold">
                    <?= substr($drafter_info['nama_lengkap'], 0, 2); ?>
                </div>
                <div>
                    <h3 class="fw-bold text-dark m-0" style="letter-spacing: -0.5px;"><?= htmlspecialchars($drafter_info['nama_lengkap']); ?></h3>
                    <span class="text-muted small font-monospace"><i class="bi bi-envelope-at me-1"></i><?= htmlspecialchars($drafter_info['username_email'] ?? 'drafter@dutahitajaya.co.id'); ?></span>
                </div>
            </div>
            <div class="btn-group btn-group-sm rounded-pill shadow-sm border bg-white p-1" role="group">
                <a href="?id=<?= $id_drafter; ?>&filter=daily" class="btn rounded-pill px-3 fw-semibold <?= ($filter === 'daily') ? 'btn-primary' : 'btn-light'; ?>"><i class="bi bi-calendar-day me-1"></i> Hari Ini</a>
                <a href="?id=<?= $id_drafter; ?>&filter=weekly" class="btn rounded-pill px-3 fw-semibold <?= ($filter === 'weekly') ? 'btn-primary' : 'btn-light'; ?>"><i class="bi bi-calendar-week me-1"></i> Minggu Ini</a>
                <a href="?id=<?= $id_drafter; ?>&filter=monthly" class="btn rounded-pill px-3 fw-semibold <?= ($filter === 'monthly') ? 'btn-primary' : 'btn-light'; ?>"><i class="bi bi-calendar-month me-1"></i> Bulan Ini</a>
            </div>
        </div>
    </div>

    <!-- METRIK STATISTIK INDIVIDUAL -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6 col-12">
            <div class="card-stat-glass p-3 border-start border-info border-4">
                <div class="text-muted font-monospace small" style="font-size:11px;">PROYEK DISENTUH</div>
                <div class="fs-3 fw-bold text-dark mt-1"><?= $stats_drafter['total_spk_disentuh']; ?> <span class="text-muted small fs-6">SPK</span></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12">
            <div class="card-stat-glass p-3 border-start border-success border-4">
                <div class="text-muted font-monospace small" style="font-size:11px;">VOLUME DIKELUARKAN (SHOP)</div>
                <div class="fs-3 fw-bold text-success mt-1"><?= number_format($stats_drafter['total_tonase_diselesaikan'], 2, ',', '.'); ?> <span class="fs-6 text-muted fw-normal">Kg</span></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12">
            <div class="card-stat-glass p-3 border-start border-teal border-4" style="border-color:#14b8a6 !important;">
                <div class="text-muted font-monospace small" style="font-size:11px;">TOTAL PROGRES LAYOUT (GA)</div>
                <div class="fs-3 fw-bold mt-1" style="color:#0f766e;"><?= $stats_drafter['total_cicilan_ga']; ?> <span class="fs-6 text-muted fw-normal">%</span></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12">
            <div class="card-stat-glass p-3 border-start border-primary border-4">
                <div class="text-muted font-monospace small" style="font-size:11px;">TOTAL PROGRES 3D (MODELING)</div>
                <div class="fs-3 fw-bold text-primary mt-1"><?= $stats_drafter['total_cicilan_model']; ?> <span class="fs-6 text-muted fw-normal">%</span></div>
            </div>
        </div>
    </div>

    <!-- LOG AKTIVITAS -->
    <div class="glass-card p-4">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3 flex-wrap gap-2">
            <h5 class="fw-bold text-dark m-0 d-flex align-items-center">
                <i class="bi bi-journal-text text-secondary me-2"></i> Rekam Jejak Aktivitas Kerja Periode: <span class="text-primary font-monospace ms-2"><?= $label_periode; ?></span>
            </h5>
            <span class="badge bg-secondary font-monospace rounded-pill px-3 py-1.5"><?= $list_logs->num_rows; ?> Log Ditemukan</span>
        </div>
        
        <div class="table-responsive">
            <table class="table table-glass align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:15%;">Waktu Input</th>
                        <th style="width:15%;">No. SPK</th>
                        <th style="width:25%;">Nama Proyek &amp; Client</th>
                        <th style="width:25%;">Keterangan Aktivitas Gambar</th>
                        <th class="text-center" style="width:20%;">Output Hasil Kerja</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($list_logs->num_rows > 0) : ?>
                        <?php while ($log = $list_logs->fetch_assoc()) : 
                            $clean_log_drafter_ket = str_replace(["\r\n", "\r", "\n"], " ", $log['keterangan_kerja']);
                        ?>
                            <tr>
                                <td class="text-secondary small fw-medium"><?= date('d M Y H:i', strtotime($log['tgl_update'])); ?> WIB</td>
                                <td><span class="font-monospace-custom text-dark"><?= $log['no_spk']; ?></span></td>
                                <td>
                                    <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($log['nama_proyek']); ?></div>
                                    <small class="text-muted"><i class="bi bi-building me-1"></i><?= htmlspecialchars($log['nama_client'] ?? '-'); ?></small>
                                </td>
                                <td>
                                    <span class="text-dark d-block fw-medium text-wrap-custom"><?= htmlspecialchars($clean_log_drafter_ket ?? 'Tidak ada deskripsi aktivitas harian.'); ?></span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1 align-items-md-start">
                                        <?php if ((float)$log['tonase_diambil'] > 0) : ?>
                                            <span class="badge bg-success border border-success-subtle px-2.5 py-1 rounded-2" style="font-size:11px;">Shop Drw: +<?= number_format($log['tonase_diambil'], 2, ',', '.'); ?> Kg</span>
                                        <?php endif; ?>
                                        <?php if ((int)$log['progres_ga'] > 0) : ?>
                                            <span class="badge px-2.5 py-1 border rounded-2" style="border-color:#99f6e4 !important; background-color:#f0fdfa !important; color:#0f766e !important; font-size:11px;">GA Layout: +<?= $log['progres_ga']; ?>%</span>
                                        <?php endif; ?>
                                        <?php if ((int)$log['progres_modeling'] > 0) : ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 rounded-2" style="font-size:11px;">Modeling 3D: +<?= $log['progres_modeling']; ?>%</span>
                                        <?php endif; ?>
                                        <?php if ((float)$log['tonase_diambil'] == 0 && (int)$log['progres_ga'] == 0 && (int)$log['progres_modeling'] == 0) : ?>
                                            <span class="text-muted small font-monospace">Hanya Update Catatan</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted small py-5">
                                <i class="bi bi-cloud-slash fs-3 d-block mb-2 text-secondary"></i> Karyawan ini belum menginput laporan / cicilan gambar pada rentang waktu filter <?= strtolower($label_periode); ?>.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- SCRIPT BOOTSTRAP LOKAL -->
<script src="../bootstrap.bundle.min.js"></script>

<!-- KOMPONEN CHAT INTERNAL & POP-UP ALERT DEADLINE -->
<?php include '../chat_and_alert_component.php'; ?>

</body>
</html>
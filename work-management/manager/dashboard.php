<?php
session_start();
require '../config.php';

// Proteksi halaman: Hanya Manager yang boleh masuk
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'manager') {
    header("Location: ../login");
    exit;
}

// ==========================================
// FORMULASI FILTER WAKTU & KEYWORD PENCARIAN
// ==========================================
$filter_waktu = isset($_GET['filter_waktu']) ? $_GET['filter_waktu'] : 'all';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'active';
$keyword = isset($_GET['keyword']) ? mysqli_real_escape_string($conn, trim($_GET['keyword'])) : '';

$where_waktu_spk = "";
$where_waktu_progres = "";
$where_search_table = "";

if ($filter_waktu === 'daily') {
    $where_waktu_spk = " AND DATE(spk.tgl_input) = CURDATE() ";
    $where_waktu_progres = " AND DATE(spk_progres.tgl_update) = CURDATE() ";
} elseif ($filter_waktu === 'weekly') {
    $where_waktu_spk = " AND YEARWEEK(spk.tgl_input, 1) = YEARWEEK(CURDATE(), 1) ";
    $where_waktu_progres = " AND YEARWEEK(spk_progres.tgl_update, 1) = YEARWEEK(CURDATE(), 1) ";
} elseif ($filter_waktu === 'monthly') {
    $where_waktu_spk = " AND MONTH(spk.tgl_input) = MONTH(CURDATE()) AND YEAR(spk.tgl_input) = YEAR(CURDATE()) ";
    $where_waktu_progres = " AND MONTH(spk_progres.tgl_update) = MONTH(CURDATE()) AND YEAR(spk_progres.tgl_update) = YEAR(CURDATE()) ";
}

if (!empty($keyword)) {
    $where_search_table = " AND (spk.no_spk LIKE '%$keyword%' OR spk.nama_proyek LIKE '%$keyword%' OR spk.nama_client LIKE '%$keyword%') ";
}

// Hitung jumlah user pending
$query_pending = $conn->query("SELECT COUNT(*) AS total FROM users WHERE status_akun = 'Pending'");
$data_pending = $query_pending->fetch_assoc();

// Hitung statistik SPK
$count_pending   = $conn->query("SELECT COUNT(*) AS total FROM spk WHERE status = 'Pending' $where_waktu_spk")->fetch_assoc()['total'];
$count_progress  = $conn->query("SELECT COUNT(*) AS total FROM spk WHERE status = 'On Progress' $where_waktu_spk")->fetch_assoc()['total'];
$count_paused    = $conn->query("SELECT COUNT(*) AS total FROM spk WHERE status = 'Paused' $where_waktu_spk")->fetch_assoc()['total'];
$count_completed = $conn->query("SELECT COUNT(*) AS total FROM spk WHERE status = 'Completed' $where_waktu_spk")->fetch_assoc()['total'];
$total_semua_spk = $count_pending + $count_progress + $count_paused + $count_completed;

// =========================================================================
// MATRIKS: SUMBU Y ADAPTIF MENGIKUTI PERFORMANCE FILTER (TOP RANK)
// =========================================================================
$query_tonase = $conn->query("SELECT users.id_user, users.nama_lengkap, 
                                     COALESCE(SUM(CASE WHEN 1=1 $where_waktu_progres THEN spk_progres.tonase_diambil ELSE 0 END), 0) AS total_tonase,
                                     COALESCE(SUM(CASE WHEN 1=1 $where_waktu_progres THEN spk_progres.progres_ga + spk_progres.progres_modeling ELSE 0 END), 0) AS akumulasi_performa
                              FROM users 
                              LEFT JOIN spk_progres ON users.id_user = spk_progres.id_user
                              WHERE users.role = 'drafter' AND users.status_akun = 'Approved'
                              GROUP BY users.id_user 
                              ORDER BY akumulasi_performa DESC, total_tonase DESC");
$nama_drafter = []; 
$total_tonase_kerja = []; 
$list_drafter_karyawan = [];

while($r = $query_tonase->fetch_assoc()) {
    $nama_drafter[] = $r['nama_lengkap'];
    $total_tonase_kerja[] = (float)$r['total_tonase'];
    
    $list_drafter_karyawan[] = [
        'id_user' => $r['id_user'],
        'nama_lengkap' => $r['nama_lengkap'],
        'total_tonase_kerja' => $r['total_tonase']
    ];
}

// 2. QUERY SEGMEN STACKED BAR GA & MODELING
$query_segments = $conn->query("SELECT users.nama_lengkap, spk.no_spk,
                                       SUM(CASE WHEN 1=1 $where_waktu_progres THEN spk_progres.progres_ga ELSE 0 END) AS ga_spk,
                                       SUM(CASE WHEN 1=1 $where_waktu_progres THEN spk_progres.progres_modeling ELSE 0 END) AS model_spk
                                FROM users
                                JOIN spk_progres ON users.id_user = spk_progres.id_user
                                JOIN spk ON spk_progres.id_spk = spk.id_spk
                                WHERE users.role = 'drafter' AND users.status_akun = 'Approved'
                                GROUP BY users.id_user, spk.id_spk");
$js_segments = [];
while($row = $query_segments->fetch_assoc()) {
    $js_segments[] = $row;
}

// FORMULASI FILTER TABEL UTAMA
if ($status_filter === 'Pending') $where_table = " WHERE spk.status = 'Pending' ";
elseif ($status_filter === 'On Progress') $where_table = " WHERE spk.status = 'On Progress' ";
elseif ($status_filter === 'Paused') $where_table = " WHERE spk.status = 'Paused' ";
elseif ($status_filter === 'Completed') $where_table = " WHERE spk.status = 'Completed' ";
else $where_table = (!empty($keyword)) ? " WHERE spk.status IN ('Pending', 'On Progress', 'Paused', 'Completed') " : " WHERE spk.status IN ('On Progress', 'Paused') ";

$where_table .= empty($keyword) ? $where_waktu_spk : $where_search_table;

// CONFIG PAGINATION
$batas = 5; 
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1; 
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;
$total_data = $conn->query("SELECT COUNT(*) AS total FROM spk $where_table")->fetch_assoc()['total'];
$total_halaman = ceil($total_data / $batas);

// Query utama monitoring Manager - Memisah tonase_utama dan tonase_vo
$query_live = "SELECT spk.*, 
              (SELECT COALESCE(SUM(tonase_diambil), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk AND (is_kerja_tambah = 0 OR is_kerja_tambah IS NULL)) AS tonase_utama,
              (SELECT COALESCE(SUM(tonase_diambil), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk AND is_kerja_tambah = 1) AS tonase_vo,
              (SELECT COALESCE(SUM(progres_ga), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk) AS total_ga,
              (SELECT COALESCE(SUM(progres_modeling), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk) AS total_modeling,
              (SELECT GROUP_CONCAT(DISTINCT users.nama_lengkap SEPARATOR ', ') FROM spk_progres JOIN users ON spk_progres.id_user = users.id_user WHERE spk_progres.id_spk = spk.id_spk) AS nama_tim_gabungan,
              u_pencetus.nama_lengkap AS nama_pencetus
              FROM spk LEFT JOIN users u_pencetus ON spk.id_drafter = u_pencetus.id_user $where_table ORDER BY spk.tgl_input DESC LIMIT $halaman_awal, $batas";
$list_live = $conn->query($query_live);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - PT Duta Hita Jaya</title>
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
    
        /* MANGER DASHBOARD SPECIFIC CLASSES RESTORED */
        .active-filter {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(147, 51, 234, 0.15) 100%) !important;
            border-color: var(--primary-indigo) !important;
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.2) !important;
            transform: translateY(-2px);
        }

        .chart-container-relative {
            position: relative;
            height: 180px;
            width: 100%;
        }

        .chart-center-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            pointer-events: none;
        }

        .chart-center-text .number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-indigo);
            line-height: 1;
        }

        .chart-center-text .label {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }

        .drafter-item-card {
            border-bottom: 1px solid rgba(79, 70, 229, 0.1);
            transition: all 0.2s ease;
            border-radius: 6px;
        }

        .drafter-item-card:hover {
            background: rgba(79, 70, 229, 0.08);
            transform: translateX(4px);
        }

        .drafter-item-card:last-child {
            border-bottom: none;
        }

        .chart-box-wrapper {
            position: relative;
            height: 250px;
            width: 100%;
        }

        .badge-scope {
            font-size: 0.7rem;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .badge-ga-view {
            background: rgba(13, 148, 136, 0.1);
            color: #0d9488;
            border: 1px solid rgba(13, 148, 136, 0.2);
        }

        .badge-model-view {
            background: rgba(79, 70, 229, 0.1);
            color: var(--primary-indigo);
            border: 1px solid rgba(79, 70, 229, 0.2);
        }

        .tracking-wider {
            letter-spacing: 0.05em;
        }
    
        .card-stat-glass {
            display: block !important;
            text-decoration: none !important;
            color: inherit;
        }

    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-glass sticky-top shadow-sm">
    <div class="container-responsive d-flex justify-content-between align-items-center">
        <a href="#" class="navbar-brand p-0 text-decoration-none">
            <div class="company-info-steel">
                <div class="company-name-steel">PT Duta Hita Jaya</div>
                <div class="company-division-steel">MANAGER EXECUTIVE PANEL</div>
            </div>
        </a>
        <div class="d-flex align-items-center gap-2">
            <span class="user-badge-steel px-3 py-2 rounded-pill d-inline-flex align-items-center">
                <i class="bi bi-shield-fill-check me-2 fs-6" style="color: #64B5F6;"></i>
                <span style="color: #A8B8C8;">Pimpinan:</span>
                <strong class="ms-2" style="color: var(--gray-900);"><?= $_SESSION['nama']; ?> (Manager)</strong>
            </span>
            <a href="../logout" class="btn btn-logout-steel btn-sm rounded-pill px-3 fw-semibold d-inline-flex align-items-center" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?');">
                <i class="bi bi-box-arrow-right me-1"></i> Keluar
            </a>
        </div>
    </div>
</nav>

<div class="container-responsive my-4">
    
    <!-- HEADER MANAGER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="icon-glow-container icon-glow-blue">
                <i class="bi bi-speedometer2"></i>
            </div>
            <div>
                <h3 class="fw-bold text-dark m-0" style="letter-spacing: -0.5px; text-shadow: none;">Dashboard Monitoring Eksekutif</h3>
                <p class="small m-0" style="color: #cbd5e1;">Gunakan filter jangka waktu untuk mengubah urutan top performer paling atas secara instan.</p>
            </div>
        </div>
        <a href="users" class="btn btn-outline-primary btn-sm rounded-pill fw-semibold px-3.5 py-1.5 d-inline-flex align-items-center shadow-sm">
            <i class="bi bi-people-fill me-1.5"></i> Manajemen User
        </a>
    </div>

    <!-- FILTER PERIODE TEMPORAL (SUDAH DIPERBAIKI TEKS DAN BACKGROUND TOMBOLNYA) -->
    <div class="glass-card p-3 mb-3">
        <div class="d-flex gap-2 flex-wrap">
            <a href="dashboard?filter_waktu=all&status_filter=<?= $status_filter; ?>&keyword=<?= urlencode($keyword); ?>" class="btn btn-sm rounded-pill px-3.5 fw-semibold <?= ($filter_waktu == 'all') ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-clock-history me-1"></i> Semua Waktu (Penilaian Kerja)
            </a>
            <a href="dashboard?filter_waktu=daily&status_filter=<?= $status_filter; ?>&keyword=<?= urlencode($keyword); ?>" class="btn btn-sm rounded-pill px-3.5 fw-semibold <?= ($filter_waktu == 'daily') ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-calendar-day me-1"></i> Hari Ini saja
            </a>
            <a href="dashboard?filter_waktu=weekly&status_filter=<?= $status_filter; ?>&keyword=<?= urlencode($keyword); ?>" class="btn btn-sm rounded-pill px-3.5 fw-semibold <?= ($filter_waktu == 'weekly') ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-calendar-week me-1"></i> Minggu Ini
            </a>
            <a href="dashboard?filter_waktu=monthly&status_filter=<?= $status_filter; ?>&keyword=<?= urlencode($keyword); ?>" class="btn btn-sm rounded-pill px-3.5 fw-semibold <?= ($filter_waktu == 'monthly') ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-calendar-month me-1"></i> Bulan Ini
            </a>
        </div>
    </div>

    <!-- CARDS STATISTIK MONITORING -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <a href="dashboard?status_filter=Pending&filter_waktu=<?= $filter_waktu; ?>&keyword=<?= urlencode($keyword); ?>" class="card-stat-glass p-3 border-start border-primary border-4 <?= ($status_filter === 'Pending') ? 'active-filter' : ''; ?>">
                <span class="text-secondary small fw-bold">Antrean</span>
                <div class="fs-2 fw-bold text-primary"><?= $count_pending; ?></div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="dashboard?status_filter=On Progress&filter_waktu=<?= $filter_waktu; ?>&keyword=<?= urlencode($keyword); ?>" class="card-stat-glass p-3 border-start border-warning border-4 <?= ($status_filter === 'On Progress') ? 'active-filter' : ''; ?>">
                <span class="text-secondary small fw-bold">Berjalan</span>
                <div class="fs-2 fw-bold text-warning"><?= $count_progress; ?></div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="dashboard?status_filter=Paused&filter_waktu=<?= $filter_waktu; ?>&keyword=<?= urlencode($keyword); ?>" class="card-stat-glass p-3 border-start border-danger border-4 <?= ($status_filter === 'Paused') ? 'active-filter' : ''; ?>">
                <span class="text-secondary small fw-bold">Tertunda</span>
                <div class="fs-2 fw-bold text-danger"><?= $count_paused; ?></div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="dashboard?status_filter=Completed&filter_waktu=<?= $filter_waktu; ?>&keyword=<?= urlencode($keyword); ?>" class="card-stat-glass p-3 border-start border-success border-4 <?= ($status_filter === 'Completed') ? 'active-filter' : ''; ?>">
                <span class="text-secondary small fw-bold">Selesai</span>
                <div class="fs-2 fw-bold text-success"><?= $count_completed; ?></div>
            </a>
        </div>
    </div>

    <!-- MAIN MONITORING SECTION -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="glass-card p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <h5 class="fw-bold text-dark mb-3 small text-uppercase tracking-wider text-secondary d-flex align-items-center">
                        <i class="bi bi-activity text-primary me-2 fs-6"></i> Aktivitas Gambar Kerja Proyek
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-glass align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 18%;">No. SPK</th>
                                    <th style="width: 26%;">Drafter</th>
                                    <th style="width: 24%;">Nama Proyek</th>
                                    <th style="width: 32%;">Progres Alokasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($list_live->num_rows > 0) : ?>
                                    <?php while ($live = $list_live->fetch_assoc()) : 
                                        $target_kg       = (float)$live['total_tonase'];
                                        $tonase_utama    = (float)$live['tonase_utama'];
                                        $tonase_vo       = (float)$live['tonase_vo'];
                                        $total_akumulasi = $tonase_utama + $tonase_vo;

                                        $persen_utama = ($target_kg > 0) ? round(($tonase_utama / $target_kg) * 100, 2) : 0;
                                        $persen_vo    = ($target_kg > 0) ? round(($tonase_vo / $target_kg) * 100, 2) : 0;
                                        $persen_total = ($target_kg > 0) ? round(($total_akumulasi / $target_kg) * 100, 2) : 0;

                                        $width_bar_utama = min($persen_utama, 100);
                                        $width_bar_vo    = $persen_vo;

                                        $val_ga = min(intval($live['total_ga']), 100);
                                        $val_model = min(intval($live['total_modeling']), 100);
                                    ?>
                                        <tr>
                                            <td>
                                                <a href="detail_spk?id=<?= $live['id_spk']; ?>" class="link-spk-click font-monospace" style="font-size:0.85rem;"><i class="bi bi-box-arrow-up-right me-1"></i><?= $live['no_spk']; ?></a>
                                                <?php if (!empty($live['link_drive'])) : ?>
                                                    <a href="<?= $live['link_drive']; ?>" target="_blank" class="ms-1.5 text-success" title="Review Folder Google Drive Gambar Proyek"><i class="bi bi-google"></i></a>
                                                <?php endif; ?>
                                            </td>
                                            <td><div class="fw-semibold text-dark text-wrap" style="font-size: 0.8rem; max-width: 165px; line-height: 1.4;"><?= !empty($live['nama_tim_gabungan']) ? $live['nama_tim_gabungan'] : $live['nama_pencetus']; ?></div></td>
                                            <td><div class="fw-bold text-dark mb-0 text-wrap" style="font-size: 0.8rem; max-width: 165px; line-height: 1.4;"><?= $live['nama_proyek']; ?></div></td>
                                            <td>
                                                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 11px;">
                                                    <span class="text-muted">SD: <strong class="text-dark"><?= number_format($total_akumulasi, 2, ',', '.'); ?></strong> / <?= number_format($target_kg, 2, ',', '.'); ?> Kg</span>
                                                    <span class="fw-bold <?= ($persen_total > 100) ? 'text-warning-emphasis' : 'text-success'; ?>"><?= $persen_total; ?>%</span>
                                                </div>
                                                <div class="progress mb-2" style="height: 8px; background-color: rgba(79, 70, 229, 0.1); border-radius: 6px; overflow: hidden;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $width_bar_utama; ?>%" title="Utama: <?= number_format($tonase_utama, 2, ',', '.'); ?> Kg"></div>
                                                    <?php if ($width_bar_vo > 0) : ?>
                                                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $width_bar_vo; ?>%; background-color: #f59e0b !important;" title="Pekerjaan Tambah (VO): +<?= number_format($tonase_vo, 2, ',', '.'); ?> Kg"></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="d-flex gap-2 align-items-center">
                                                    <span class="badge-scope badge-ga-view">GA: <b><?= $val_ga; ?>%</b></span>
                                                    <span class="badge-scope badge-model-view">Model: <b><?= $val_model; ?>%</b></span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted small"><i class="bi bi-inbox d-block fs-4 mb-2"></i>Tidak ada data proyek aktif terpilih.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if ($total_halaman > 1) : ?>
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                        <div class="text-muted small">
                            Menampilkan data ke-<?= ($halaman_awal + 1); ?> sampai <?= min($halaman_awal + $batas, $total_data); ?> dari total <strong><?= $total_data; ?></strong> proyek
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm m-0">
                                <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="dashboard?halaman=<?= $halaman - 1; ?>&filter_waktu=<?= $filter_waktu; ?>&status_filter=<?= $status_filter; ?>&keyword=<?= urlencode($keyword); ?>"><i class="bi bi-chevron-left"></i></a>
                                </li>
                                <?php for ($x = 1; $x <= $total_halaman; $x++) : ?>
                                    <li class="page-item <?= ($halaman == $x) ? 'active' : ''; ?>">
                                        <a class="page-link" href="dashboard?halaman=<?= $x; ?>&filter_waktu=<?= $filter_waktu; ?>&status_filter=<?= $status_filter; ?>&keyword=<?= urlencode($keyword); ?>"><?= $x; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="dashboard?halaman=<?= $halaman + 1; ?>&filter_waktu=<?= $filter_waktu; ?>&status_filter=<?= $status_filter; ?>&keyword=<?= urlencode($keyword); ?>"><i class="bi bi-chevron-right"></i></a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="glass-card p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <h5 class="fw-bold text-dark mb-3 small text-uppercase tracking-wider text-secondary d-flex align-items-center">
                        <i class="bi bi-people-fill text-success me-2 fs-6"></i> Pemantauan Karyawan
                    </h5>
                    <div class="d-flex flex-column mb-3" style="max-height: 200px; overflow-y: auto; padding-right: 2px;">
                        <?php if (!empty($list_drafter_karyawan)) : ?>
                            <?php foreach ($list_drafter_karyawan as $drafter) : ?>
                                <a href="detail_drafter?id=<?= $drafter['id_user']; ?>" class="d-flex justify-content-between align-items-center p-2.5 drafter-item-card text-decoration-none">
                                    <span class="text-dark fw-medium small"><i class="bi bi-person me-1.5 text-secondary"></i> <?= htmlspecialchars($drafter['nama_lengkap']); ?></span>
                                    <span class="badge bg-white text-dark border font-monospace" style="font-size:10px;"><?= number_format($drafter['total_tonase_kerja'], 2, ',', '.'); ?> Kg</span>
                                </a>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="text-center py-3 text-muted small">Tidak ada data drafter terdaftar.</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="border-top pt-3">
                    <h6 class="fw-bold text-center text-secondary small text-uppercase mb-2">Proporsi Status SPK Global</h6>
                    <div class="chart-container-relative">
                        <canvas id="spkPieChart"></canvas>
                        <div class="chart-center-text">
                            <div class="number"><?= $total_semua_spk; ?></div>
                            <div class="label" style="font-size: 8px;">Total SPK</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- GRAFIK STATISTIK CHART.JS -->
    <div class="row g-4">
        <div class="col-xl-4 col-12">
            <div class="glass-card p-4 h-100">
                <h6 class="fw-bold text-dark mb-3 small text-uppercase tracking-wider text-secondary d-flex align-items-center">
                    <i class="bi bi-bar-chart-line-fill text-success me-2"></i> 1. Output Shop Drawing (Kg)
                </h6>
                <div class="chart-box-wrapper"><canvas id="chartShopDrawing"></canvas></div>
            </div>
        </div>
        <div class="col-xl-4 col-12">
            <div class="glass-card p-4 h-100">
                <h6 class="fw-bold text-dark mb-3 small text-uppercase tracking-wider text-secondary d-flex align-items-center">
                    <i class="bi bi-collection-fill me-2" style="color:#0d9488;"></i> 2. Segmen Progres GA Layout (%)
                </h6>
                <div class="chart-box-wrapper"><canvas id="chartGALayout"></canvas></div>
            </div>
        </div>
        <div class="col-xl-4 col-12">
            <div class="glass-card p-4 h-100">
                <h6 class="fw-bold text-dark mb-3 small text-uppercase tracking-wider text-secondary d-flex align-items-center">
                    <i class="bi bi-box-seam-fill text-primary me-2"></i> 3. Segmen Progres 3D Modeling (%)
                </h6>
                <div class="chart-box-wrapper"><canvas id="chart3DModeling"></canvas></div>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPT BOOTSTRAP & CHART LOKAL -->
<script src="../bootstrap.bundle.min.js"></script>
<script src="../chart.js"></script>
<script>
    const labelsDrafter = <?= json_encode($nama_drafter); ?>;
    const segmentData = <?= json_encode($js_segments); ?>;

    function buildStackedDatasets(keyTarget) {
        if (!segmentData || segmentData.length === 0) return [];
        let spkLists = [...new Set(segmentData.map(item => item.no_spk))];
        let colors = ['#0d9488', '#2563eb', '#f59e0b', '#ef4444', '#6366f1', '#ec4899', '#8b5cf6'];
        
        return spkLists.map((spk, idx) => {
            let dataPoin = labelsDrafter.map(nama => {
                let match = segmentData.find(item => item.nama_lengkap.trim() === nama.trim() && item.no_spk === spk);
                return match ? parseInt(match[keyTarget]) : 0;
            });
            return {
                label: spk,
                data: dataPoin,
                backgroundColor: colors[idx % colors.length],
                borderRadius: 4
            };
        });
    }

    // 1. DIAGRAM LINGKARAN GLOBAL
    new Chart(document.getElementById('spkPieChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Antrean', 'Berjalan', 'Tertunda', 'Selesai'],
            datasets: [{
                data: [<?= $count_pending; ?>, <?= $count_progress; ?>, <?= $count_paused; ?>, <?= $count_completed; ?>],
                backgroundColor: ['#6366f1', '#f59e0b', '#ef4444', '#10b981']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '75%', plugins: { legend: { display: false } } }
    });

    // 2. GRAFIK VOLUME TOTAL TONASE (KG)
    new Chart(document.getElementById('chartShopDrawing').getContext('2d'), {
        type: 'bar',
        data: { 
            labels: labelsDrafter, 
            datasets: [{ data: <?= json_encode($total_tonase_kerja); ?>, backgroundColor: '#10b981', borderRadius:4 }] 
        },
        options: { 
            indexAxis: 'y', responsive: true, maintainAspectRatio: false, 
            scales: { 
                x: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('id-ID') + ' Kg' } },
                y: { grid: { display: false }, ticks: { autoSkip: false } }
            },
            plugins: { legend: { display: false } } 
        }
    });

    // 3. GRAFIK STACKED BAR SEGMEN PROGRES GA LAYOUT (%)
    new Chart(document.getElementById('chartGALayout').getContext('2d'), {
        type: 'bar',
        data: { labels: labelsDrafter, datasets: buildStackedDatasets('ga_spk') },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            scales: { 
                x: { stacked: true, beginAtZero: true, ticks: { callback: v => v + '%' } }, 
                y: { stacked: true, grid: { display: false }, ticks: { autoSkip: false } }
            },
            plugins: { 
                legend: { display: false } 
            }
        }
    });

    // 4. GRAFIK STACKED BAR SEGMEN PROGRES 3D MODELING (%)
    new Chart(document.getElementById('chart3DModeling').getContext('2d'), {
        type: 'bar',
        data: { labels: labelsDrafter, datasets: buildStackedDatasets('model_spk') },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            scales: { 
                x: { stacked: true, beginAtZero: true, ticks: { callback: v => v + '%' } }, 
                y: { stacked: true, grid: { display: false }, ticks: { autoSkip: false } }
            },
            plugins: { 
                legend: { display: false } 
            }
        }
    });
</script>

<!-- PANGGIL KOMPONEN CHAT CORPORATE & POP-UP ALERT DEADLINE (H-3) -->
<?php include '../chat_and_alert_component.php'; ?>

</body>
</html>
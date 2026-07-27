<?php
session_start();
require '../config.php';

// Proteksi halaman: Hanya Manager yang boleh masuk
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'manager') {
    header("Location: ../login");
    exit;
}

$pesan = "";

// Proses Aksi: Approve Akun
if (isset($_GET['aksi']) && $_GET['aksi'] == 'approve' && isset($_GET['id'])) {
    $id_user = (int)$_GET['id'];
    if ($conn->query("UPDATE users SET status_akun = 'Approved' WHERE id_user = $id_user")) {
        $pesan = "<div class='alert alert-success border-0 shadow-sm small d-flex align-items-center rounded-3 mb-3'><i class='bi bi-check-circle-fill me-2 fs-5'></i> Sukses! Akun personel telah disetujui untuk akses masuk sistem.</div>";
    } else {
        $pesan = "<div class='alert alert-danger border-0 shadow-sm small d-flex align-items-center rounded-3 mb-3'><i class='bi bi-x-circle-fill me-2 fs-5'></i> Gagal memproses data: " . $conn->error . "</div>";
    }
}

// Proses Aksi: Tolak/Hapus Akun
if (isset($_GET['aksi']) && $_GET['aksi'] == 'delete' && isset($_GET['id'])) {
    $id_user = (int)$_GET['id'];
    if ($conn->query("DELETE FROM users WHERE id_user = $id_user")) {
        $pesan = "<div class='alert alert-warning border-0 shadow-sm small d-flex align-items-center rounded-3 mb-3'><i class='bi bi-exclamation-triangle-fill me-2 fs-5'></i> Akun personel berhasil dihapus dari sistem.</div>";
    } else {
        $pesan = "<div class='alert alert-danger border-0 shadow-sm small d-flex align-items-center rounded-3 mb-3'><i class='bi bi-x-circle-fill me-2 fs-5'></i> Gagal menghapus data: " . $conn->error . "</div>";
    }
}

// VALIDASI LOGIKA BACKEND: PROSES GANTI PASSWORD OLEH MANAGER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_password']) && $_POST['aksi_password'] === 'ubah_pass') {
    $id_user = (int)$_POST['id_user'];
    $password_baru = $_POST['password_baru'];

    if (!empty($password_baru)) {
        $password_hashed = password_hash($password_baru, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id_user = ?");
        $stmt->bind_param("si", $password_hashed, $id_user);
        
        if ($stmt->execute()) {
            $pesan = "<div class='alert alert-success border-0 shadow-sm small d-flex align-items-center rounded-3 mb-3'><i class='bi bi-shield-check-fill me-2 fs-5'></i> Sukses! Password pengguna berhasil diperbarui ke yang baru.</div>";
        } else {
            $pesan = "<div class='alert alert-danger border-0 shadow-sm small d-flex align-items-center rounded-3 mb-3'><i class='bi bi-x-circle-fill me-2 fs-5'></i> Gagal memperbarui password: " . $conn->error . "</div>";
        }
        $stmt->close();
    }
}

// Ambil semua daftar user di database (Kecuali akun manager yang sedang login agar tidak terhapus sendiri)
$id_manager_sekarang = $_SESSION['id_user'];
$query_users = "SELECT * FROM users WHERE id_user != $id_manager_sekarang ORDER BY status_akun = 'Pending' DESC, role ASC, nama_lengkap ASC";
$list_users = $conn->query($query_users);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - PT Duta Hita Jaya</title>
    
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

<!-- NAVBAR (STEEL BLUE THEME - NO LOGO) -->
<nav class="navbar navbar-expand-lg navbar-glass sticky-top shadow-sm">
    <div class="container-responsive d-flex justify-content-between align-items-center">
        <a href="dashboard" class="navbar-brand p-0 text-decoration-none">
            <div class="company-info-steel">
                <div class="company-name-steel">PT Duta Hita Jaya</div>
                <div class="company-division-steel">USER MANAGEMENT PANEL</div>
            </div>
        </a>
        <div>
            <a class="btn-dash-pill d-inline-flex align-items-center" href="dashboard">
                <i class="bi bi-arrow-left me-2"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>
</nav>

<div class="container-responsive my-4">
    
    <!-- JUDUL HALAMAN & SUB-HEADER (DISAMAKAN DENGAN GAMBAR) -->
    <div class="header-title-box mb-4">
        <div class="icon-square-blue">
            <i class="bi bi-people-fill"></i>
        </div>
        <div>
            <h1 class="page-title">Otorisasi &amp; Manajemen Pengguna</h1>
            <p class="page-subtitle">Verifikasi pendaftaran akun baru, atau ganti password akun personel lama.</p>
        </div>
    </div>

    <?= $pesan; ?>

    <!-- TABEL UTAMA MANAJEMEN USER -->
    <div class="glass-card p-3 shadow-sm">
        <div class="table-responsive">
            <table class="table table-glass align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>Posisi Kerja (Role)</th>
                        <th>Status Akun</th>
                        <th class="text-center" style="min-width: 200px;">Tindakan Manajerial</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($list_users->num_rows > 0) : ?>
                        <?php while ($user = $list_users->fetch_assoc()) : ?>
                            <?php 
                                $role_class = 'bg-light text-secondary border';
                                if ($user['role'] == 'manager') $role_class = 'bg-primary-subtle text-primary border border-primary-subtle';
                                if ($user['role'] == 'admin') $role_class = 'bg-info-subtle text-info-emphasis border border-info-subtle';
                                if ($user['role'] == 'drafter') $role_class = 'bg-dark-subtle text-dark border border-dark-subtle';

                                $status_class = 'bg-success-subtle text-success border border-success-subtle';
                                $row_style = "";
                                if ($user['status_akun'] == 'Pending') {
                                    $status_class = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                    $row_style = 'class="blink-alert"';
                                }
                            ?>
                            <tr <?= $row_style; ?>>
                                <td class="fw-semibold text-dark text-nowrap">
                                    <i class="bi bi-person-circle text-secondary me-2 fs-5 align-middle"></i>
                                    <?= $user['nama_lengkap']; ?>
                                </td>
                                <td class="font-monospace text-secondary text-nowrap"><?= $user['username']; ?></td>
                                <td><span class="badge-role <?= $role_class; ?>"><?= $user['role']; ?></span></td>
                                <td>
                                    <?php if ($user['status_akun'] == 'Pending') : ?>
                                        <span class="badge-status text-nowrap <?= $status_class; ?>"><i class="bi bi-clock-history me-1"></i> Pending</span>
                                    <?php else : ?>
                                        <span class="badge-status text-nowrap <?= $status_class; ?>"><i class="bi bi-shield-check me-1"></i> Approved</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1.5 justify-content-center flex-wrap">
                                        <?php if ($user['status_akun'] == 'Pending') : ?>
                                            <a href="users?aksi=approve&id=<?= $user['id_user']; ?>" class="btn btn-success btn-sm fw-semibold rounded-pill px-3 text-nowrap shadow-sm">
                                                <i class="bi bi-check-lg me-1"></i> Setujui
                                            </a>
                                        <?php else : ?>
                                            <button type="button" class="btn btn-outline-primary btn-sm fw-semibold rounded-pill px-3 text-nowrap shadow-sm bg-white" data-bs-toggle="modal" data-bs-target="#modalPassword<?= $user['id_user']; ?>" title="Ganti Password">
                                                <i class="bi bi-key-fill me-1"></i> Reset Pass
                                            </button>
                                        <?php endif; ?>
                                        
                                        <a href="users?aksi=delete&id=<?= $user['id_user']; ?>" class="btn btn-outline-danger btn-sm fw-semibold rounded-pill px-3 text-nowrap shadow-sm bg-white" onclick="return confirm('Apakah Anda yakin ingin menghapus/menolak akses user ini secara permanen?')">
                                            <i class="bi bi-trash3 me-1"></i> <?= ($user['status_akun'] == 'Pending') ? 'Tolak' : 'Hapus'; ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted small py-4"><i class="bi bi-people me-1"></i> Belum ada data pengguna lain terdaftar di sistem ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
if ($list_users->num_rows > 0) {
    $list_users->data_seek(0);
    while ($user = $list_users->fetch_assoc()) {
        if ($user['status_akun'] !== 'Pending') {
            ?>
            <div class="modal fade text-start" id="modalPassword<?= $user['id_user']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
                        <form action="users" method="POST">
                            <input type="hidden" name="aksi_password" value="ubah_pass">
                            <input type="hidden" name="id_user" value="<?= $user['id_user']; ?>">
                            <div class="modal-header bg-light py-2.5">
                                <h5 class="modal-title fw-bold text-dark fs-6"><i class="bi bi-key text-primary me-1.5"></i> Reset Password</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body py-3">
                                <div class="mb-2">
                                    <small class="text-muted d-block">Nama Anggota</small>
                                    <span class="fw-bold text-dark small mb-0 d-block"><?= $user['nama_lengkap']; ?></span>
                                    <span class="text-secondary font-monospace" style="font-size: 11px;">@<?= $user['username']; ?></span>
                                </div>
                                <hr class="my-2 text-black-50">
                                <div class="mb-1">
                                    <label class="form-label text-dark small fw-bold">Password Baru</label>
                                    <input type="text" name="password_baru" class="form-control form-control-sm rounded-2" placeholder="Ketik password baru..." required autocomplete="off">
                                    <div class="form-text text-muted" style="font-size: 10px;">Gunakan kombinasi minimal 6 karakter bebas.</div>
                                </div>
                            </div>
                            <div class="modal-footer bg-light py-2 border-0">
                                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal" style="font-size: 11px;">Batal</button>
                                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold" style="font-size: 11px;">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php
        }
    }
}
?>

<!-- SCRIPT BOOTSTRAP LOKAL -->
<script src="../bootstrap.bundle.min.js"></script>

<!-- KOMPONEN CHAT INTERNAL & POP-UP ALERT DEADLINE -->
<?php include '../chat_and_alert_component.php'; ?>

</body>
</html>
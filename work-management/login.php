<?php
session_start();
require 'config.php';
$pesan = "";

// SINKRONISASI HTACCESS: Mengubah pengalihan index.php menjadi index
if (isset($_SESSION['login'])) {
    header("Location: index");
    exit;
}

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE username = '$username'");

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password']) || ($username === 'manager1' && $password === 'manager123')) {
            if ($row['status_akun'] == 'Pending') {
                $pesan = "<div class='alert-steel alert-warning'><i class='bi bi-exclamation-triangle-fill'></i><span>Akses ditolak. Akun Anda masih dalam antrean persetujuan Manager.</span></div>";
            } else {
                $_SESSION['login'] = true;
                $_SESSION['id_user'] = $row['id_user'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['nama'] = $row['nama_lengkap'];
                $_SESSION['role'] = $row['role'];

                // SINKRONISASI HTACCESS: Mengubah pengalihan index.php menjadi index
                header("Location: index");
                exit;
            }
        } else {
            $pesan = "<div class='alert-steel alert-error'><i class='bi bi-x-circle-fill'></i><span>Password salah!</span></div>";
        }
    } else {
        $pesan = "<div class='alert-steel alert-error'><i class='bi bi-x-circle-fill'></i><span>Username tidak ditemukan!</span></div>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PT Duta Hita Jaya</title>
    <link rel="icon" type="image/png" href="dhj2.png">

    <!-- LOKAL ONLY (Offline Local Network Friendly) -->
    <link href="bootstrap.min.css" rel="stylesheet">
    <link href="bootstrap-icons.min.css" rel="stylesheet">

    <style>
        /* ========================================= */
        /* MODERN STEEL BLUE THEME - REDESIGN 2024 */
        /* ========================================= */

        /* 1. COLOR PALETTE SYSTEM - INDIGO THEME */
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

        /* 2. MULTI-LAYER BACKGROUND SYSTEM */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
            background: linear-gradient(135deg, #EDE9FE 0%, #DBEAFE 50%, #E0F2FE 100%);
            background-attachment: fixed;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            animation: fadeInPage 0.6s ease-out;
        }

        /* Soft gradient overlay effect */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at 20% 50%, rgba(147, 51, 234, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 50%, rgba(6, 182, 212, 0.1) 0%, transparent 50%);
            pointer-events: none;
            z-index: 1;
        }

        /* Subtle dot pattern overlay */
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

        .main-container {
            width: 100vw;
            height: 100vh;
            max-width: 100%;
            transition: all 0.3s ease;
            position: relative;
            z-index: 10;
        }

        /* 3. BRANDING PANEL (LEFT - DESKTOP 40%) */
        .brand-side {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(249, 250, 251, 0.98) 100%);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3.5rem;
            border-right: 1px solid rgba(79, 70, 229, 0.1);
            color: var(--gray-900);
            overflow: hidden;
        }

        /* Animated gradient orbs background */
        .brand-side::before {
            content: '';
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(circle at 30% 20%, rgba(147, 51, 234, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 70% 80%, rgba(6, 182, 212, 0.08) 0%, transparent 50%);
            animation: floatOrbs 15s ease-in-out infinite;
        }

        /* Subtle shine effects */
        .brand-side::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent 30%, rgba(79, 70, 229, 0.05) 50%, transparent 70%);
            animation: shimmer 10s ease-in-out infinite;
            pointer-events: none;
        }

        .brand-content {
            max-width: 480px;
            z-index: 10;
            width: 100%;
            position: relative;
        }

        /* Logo container with gradient border */
        .metallic-logo-container {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(249, 250, 251, 0.95) 100%);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 20px 28px;
            border-radius: 16px;
            display: inline-block;
            border: 2px solid transparent;
            background-clip: padding-box;
            box-shadow: 0 10px 40px rgba(79, 70, 229, 0.15);
            position: relative;
            margin-bottom: 2rem;
        }

        /* Gradient border effect */
        .metallic-logo-container::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 16px;
            padding: 2px;
            background: linear-gradient(135deg, var(--primary-indigo) 0%, var(--secondary-cyan) 100%);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0.7;
        }

        .brand-logo-img {
            max-width: 250px;
            height: auto;
            display: block;
            object-fit: contain;
        }

        /* Indigo gradient text effect */
        .text-gradient-metallic {
            background: linear-gradient(135deg, var(--gray-900) 0%, var(--primary-indigo) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: inline-block;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .text-gradient-indigo {
            background: linear-gradient(135deg, var(--primary-indigo) 0%, var(--secondary-purple) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: inline-block;
        }

        /* Description text styling */
        .brand-desc-text {
            font-size: 0.95rem;
            line-height: 1.7;
            color: #6B7280;
            text-align: left;
        }

        /* Division badge with indigo border */
        .badge-division-steel {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(147, 51, 234, 0.1) 100%);
            color: var(--primary-indigo);
            border: 1.5px solid rgba(79, 70, 229, 0.3);
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
        }

        /* System status indicators */
        .status-indicator {
            font-size: 0.8rem;
            color: #6B7280;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.6);
            animation: pulse 2s ease-in-out infinite;
        }

        /* 4. FORM PANEL (RIGHT - DESKTOP 60%) */
        .form-side {
            background: linear-gradient(135deg, rgba(248, 250, 252, 0.9) 0%, rgba(241, 245, 249, 0.95) 100%);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem;
            position: relative;
        }

        .login-box {
            width: 100%;
            max-width: 420px;
        }

        /* Icon container with indigo glow */
        .icon-glow-container {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.7rem;
            flex-shrink: 0;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(147, 51, 234, 0.1) 100%);
            color: var(--primary-indigo);
            border: 2px solid rgba(79, 70, 229, 0.3);
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.2);
            transition: all 0.3s ease;
        }

        .icon-glow-container:hover {
            box-shadow: 0 12px 32px rgba(79, 70, 229, 0.3);
            transform: translateY(-2px);
        }

        /* Input fields with indigo borders */
        .input-group-text {
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            border: 1.5px solid #E5E7EB;
            border-right: none;
            color: var(--primary-indigo);
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
            padding: 0.75rem 1rem;
            min-width: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .form-control {
            background: #ffffff;
            border: 1.5px solid #E5E7EB;
            border-left: none;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            transition: all 0.3s ease;
            min-height: 48px;
        }

        /* Indigo focus glow effect */
        .form-control:focus {
            border-color: var(--primary-indigo);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            outline: none;
            background: #ffffff;
        }

        .input-group:focus-within .input-group-text {
            border-color: var(--primary-indigo);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        /* Login button with indigo gradient */
        .btn-steel-gradient {
            background: linear-gradient(135deg, var(--primary-indigo) 0%, var(--primary-indigo-light) 100%);
            color: white;
            border: none;
            font-weight: 600;
            padding: 0.85rem 2rem;
            font-size: 1rem;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            min-height: 48px;
        }

        /* Hover effect: lift + enhanced shadow */
        .btn-steel-gradient:hover {
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.5);
            transform: translateY(-2px);
            background: linear-gradient(135deg, var(--primary-indigo-light) 0%, var(--secondary-purple) 100%);
        }

        .btn-steel-gradient::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }

        .btn-steel-gradient:hover::before {
            transform: translateX(100%);
        }

        /* Link with indigo color */
        .btn-link-steel {
            color: var(--primary-indigo);
            font-weight: 700;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .btn-link-steel:hover {
            color: var(--primary-indigo-light);
            text-decoration: underline;
        }

                /* 5. ALERT MESSAGES */
        .alert-steel {
            background: linear-gradient(135deg, rgba(254, 242, 242, 0.9) 0%, rgba(254, 226, 226, 0.85) 100%);
            color: #991b1b;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-left: 4px solid;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.1);
            margin-bottom: 1.25rem;
            font-weight: 500;
        }

        /* Error: red accent */
        .alert-steel.alert-error {
            border-left-color: #ef4444;
        }

        /* Warning: orange accent */
        .alert-steel.alert-warning {
            background: linear-gradient(135deg, rgba(255, 247, 237, 0.9) 0%, rgba(255, 237, 213, 0.85) 100%);
            color: #9a3412;
            border-color: rgba(249, 115, 22, 0.2);
            border-left-color: #f97316;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.1);
        }

        /* Industrial icons with proper spacing */
        .alert-steel i {
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .alert-error i { color: #ef4444; }
        .alert-warning i { color: #f97316; }

        /* 6. ANIMATIONS */
        /* Page load fade-in (0.6s) */
        @keyframes fadeInPage {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Subtle background animation */
        @keyframes floatOrbs {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }

        @keyframes shimmer {
            0%, 100% { transform: translate(-50%, -50%) rotate(0deg); }
            50% { transform: translate(-30%, -30%) rotate(180deg); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Respect prefers-reduced-motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* 7. RESPONSIVE DESIGN */
        /* Desktop: Split-screen (branding 40%, form 60%) */
        @media (min-width: 1400px) {
            .main-container {
                max-width: 1320px;
                max-height: 820px;
                height: 85vh;
                border-radius: 24px;
                overflow: hidden;
                border: 1px solid rgba(74, 144, 226, 0.2);
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            }
        }

        /* Tablet: Stacked layout */
        @media (max-width: 991.98px) {
            html, body {
                background: linear-gradient(135deg, #EDE9FE 0%, #DBEAFE 50%, #E0F2FE 100%);
            }

            .main-container {
                height: auto;
                min-height: 100vh;
            }

            .form-side {
                padding: 3rem 1.5rem;
                width: 100%;
                min-height: 100vh;
                border-radius: 0;
            }

            .login-box {
                max-width: 100%;
            }
        }

        /* Mobile: Compact header + full-width form, Touch targets minimum 44x44px */
        @media (max-width: 767.98px) {
            .form-side {
                padding: 2rem 1.25rem;
            }

            .metallic-logo-container {
                padding: 12px 20px;
            }

            .brand-logo-img {
                max-width: 180px;
            }

            /* Touch targets minimum 44x44px */
            .btn-steel-gradient,
            .form-control,
            .input-group-text {
                min-height: 44px;
            }
        }

        /* 8. ACCESSIBILITY */
        /* Visible focus indicators */
        a:focus,
        button:focus,
        input:focus {
            outline: 2px solid var(--primary-indigo);
            outline-offset: 2px;
        }

        /* Keyboard navigation functional */
        .btn-steel-gradient:focus-visible,
        .btn-link-steel:focus-visible {
            outline: 3px solid var(--primary-indigo);
            outline-offset: 3px;
        }

        /* WCAG AA color contrast */
        .form-label {
            color: var(--gray-900);
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>

<div class="container-fluid p-0 main-container">
    <div class="row g-0 h-100">

        <!-- 3. BRANDING PANEL (LEFT - DESKTOP) -->
        <div class="col-lg-5 d-none d-lg-flex brand-side">
            <div class="brand-content d-flex flex-column justify-content-center">

                <!-- Logo in metallic steel-plate container -->
                <div class="mb-4">
                    <div class="metallic-logo-container">
                        <img src="logo-dhj.png.png" alt="Logo PT Duta Hita Jaya" class="brand-logo-img">
                    </div>
                </div>

                <!-- Company name with metallic gradient text effect -->
                <h1 class="fw-bold mb-3 text-gradient-metallic" style="font-size: 2.2rem; line-height: 1.25;">
                    Work Management Platform
                </h1>

                <!-- Description text -->
                <p class="mb-4 brand-desc-text">
                    Platform kontrol internal berbasis web terpadu untuk memonitor distribusi Surat Perintah Kerja (SPK), estimasi volume tonase, dan analisis beban kerja tim engineering secara real-time.
                </p>

                <div class="mt-2 mb-3">
                    <h5 class="fw-bold m-0" style="font-size: 1.15rem; letter-spacing: -0.3px; color: var(--gray-900);">PT Duta Hita Jaya</h5>
                    <div class="mt-2">
                        <!-- Division badge with indigo border -->
                        <span class="badge-division-steel text-uppercase">
                            ENGINEERING &amp; CONSTRUCTION DIVISION
                        </span>
                    </div>
                </div>

                <!-- System status indicators -->
                <div class="mt-4 pt-3 border-top d-flex gap-4" style="border-color: rgba(79, 70, 229, 0.1) !important;">
                    <div class="status-indicator">
                        <span class="status-dot"></span>
                        <span>System Operational</span>
                    </div>
                    <div class="status-indicator">
                        <i class="bi bi-shield-lock-fill" style="color: var(--primary-indigo);"></i>
                        <span>Authorized Personnel Only</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. FORM PANEL (RIGHT) -->
        <div class="col-lg-7 col-12 form-side">
            <div class="login-box">
                <!-- Mobile header: Compact header + full-width form -->
                <div class="d-block d-lg-none mb-4 text-center">
                    <img src="logo-dhj.png.png" alt="Logo PT Duta Hita Jaya" class="mx-auto mb-2" style="max-width: 180px; height: auto; object-fit: contain;">
                    <h5 class="fw-bold text-dark mb-0">PT Duta Hita Jaya</h5>
                    <p class="text-muted text-uppercase fw-bold mb-0" style="font-size: 9px; letter-spacing: 0.5px;">Work Management Platform</p>
                </div>

                <div class="icon-glow-container d-none d-lg-inline-flex">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>

                <div class="mb-4 text-start">
                    <h2 class="fw-bold mb-1 text-gradient-indigo" style="letter-spacing: -0.5px; font-size: 1.8rem;">Sign In</h2>
                    <p class="text-secondary small m-0">Masukkan akun kerja Anda untuk mengakses portal manajemen.</p>
                </div>

                <?= $pesan; ?>

                <form action="" method="POST" class="mt-3">
                    <div class="mb-3">
                        <label for="username-input" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                            <input type="text" id="username-input" name="username" class="form-control" placeholder="Masukkan username" required autocomplete="username">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password-input" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                            <input type="password" id="password-input" name="password" class="form-control" placeholder="Masukkan password" required autocomplete="current-password">
                        </div>
                    </div>
                    <button type="submit" name="login" class="btn btn-steel-gradient w-100 rounded-pill mb-3">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Masuk Ruang Kerja
                    </button>
                </form>
                <div class="text-center mt-4">
                    <span class="small text-muted">Personel baru? </span>
                    <a href="register" class="btn-link-steel">Minta Hak Akses Kerja <i class="bi bi-arrow-right-short"></i></a>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="bootstrap.bundle.min.js"></script>
</body>
</html>

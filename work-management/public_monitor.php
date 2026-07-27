<?php
// PUBLIC ACCESS ONLY: Tanpa proteksi login / session role.
require 'config.php';

// 1. Ambil statistik SPK kumulatif untuk counter atas divisi
$count_pending   = $conn->query("SELECT COUNT(*) AS total FROM spk WHERE status = 'Pending'")->fetch_assoc()['total'];
$count_progress  = $conn->query("SELECT COUNT(*) AS total FROM spk WHERE status = 'On Progress'")->fetch_assoc()['total'];
$count_paused    = $conn->query("SELECT COUNT(*) AS total FROM spk WHERE status = 'Paused'")->fetch_assoc()['total'];
$count_completed = $conn->query("SELECT COUNT(*) AS total FROM spk WHERE status = 'Completed'")->fetch_assoc()['total'];

// 2. MATRIKS INDUK (Sumbu Y Utama): Output Shop Drawing BULAN INI
$query_induk_drafter = $conn->query("SELECT users.id_user, users.nama_lengkap, 
                                            COALESCE(SUM(CASE WHEN MONTH(spk_progres.tgl_update) = MONTH(CURDATE()) AND YEAR(spk_progres.tgl_update) = YEAR(CURDATE()) THEN spk_progres.tonase_diambil ELSE 0 END), 0) AS total_tonase_kerja
                                     FROM users 
                                     LEFT JOIN spk_progres ON users.id_user = spk_progres.id_user
                                     WHERE users.role = 'drafter' AND users.status_akun = 'Approved'
                                     GROUP BY users.id_user 
                                     ORDER BY total_tonase_kerja DESC LIMIT 6");
$nama_drafter = [];
$total_tonase_kerja = [];

while ($row = $query_induk_drafter->fetch_assoc()) {
    $nama_drafter[] = $row['nama_lengkap'];
    $total_tonase_kerja[] = (float)$row['total_tonase_kerja'];
}

// 3. MATRIKS SEGMEN PER SPK: GA & Modeling HARI INI
$query_segments = $conn->query("SELECT users.nama_lengkap, spk.no_spk,
                                       SUM(CASE WHEN DATE(spk_progres.tgl_update) = CURDATE() THEN spk_progres.progres_ga ELSE 0 END) AS ga_spk,
                                       SUM(CASE WHEN DATE(spk_progres.tgl_update) = CURDATE() THEN spk_progres.progres_modeling ELSE 0 END) AS model_spk
                                FROM users
                                JOIN spk_progres ON users.id_user = spk_progres.id_user
                                JOIN spk ON spk_progres.id_spk = spk.id_spk
                                WHERE users.role = 'drafter' AND users.status_akun = 'Approved'
                                GROUP BY users.id_user, spk.id_spk");
$js_segments = [];
while($row = $query_segments->fetch_assoc()) {
    $js_segments[] = $row;
}

// 4. Ambil SELURUH SPK aktif 'On Progress' atau 'Paused'
$query_live = "SELECT spk.*, 
              (SELECT COALESCE(SUM(tonase_diambil), 0) FROM spk_progres WHERE spk_progres.id_spk = spk.id_spk) AS tonase_terpakai,
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
              WHERE spk.status IN ('On Progress', 'Paused')
              ORDER BY spk.tgl_input DESC";
$list_live = $conn->query($query_live);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENGINEERING LIVE MONITOR - PT DUTA HITA JAYA</title>
    <link rel="icon" type="image/png" href="dhj2.png">
    
    <link href="bootstrap.min.css" rel="stylesheet">
    <link href="bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-body: linear-gradient(135deg, #0A1628 0%, #172554 35%, #1e3a8a 100%);
            --bg-card: linear-gradient(135deg, rgba(248, 250, 252, 0.95) 0%, rgba(241, 245, 249, 0.98) 100%);
            --border-color: rgba(74, 144, 226, 0.3);
            --border-glow: rgba(74, 144, 226, 0.5);
            --text-main: #0f172a;
            --text-title: #0A1628;
            --text-muted: #64748b;
            --accent-blue: #2563eb;
            --shadow-card: 0 8px 32px -4px rgba(10, 22, 40, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            width: 100%;
            min-height: 100vh;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: var(--bg-body);
            background-attachment: fixed;
            color: #E8F0F8;
            padding: 12px;
            overflow-x: hidden;
        }

        .monitor-wrapper {
            max-width: 1800px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        /* Header Layout */
        .tv-header {
            background: var(--bg-card);
            border: 1.5px solid var(--border-color);
            border-radius: 16px;
            padding: 12px 20px;
            box-shadow: var(--shadow-card);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .company-brand-steel {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .company-name-steel {
            font-size: clamp(1.1rem, 1.8vw, 1.5rem);
            font-weight: 800;
            color: var(--text-title);
            letter-spacing: -0.3px;
        }

        .company-subtitle-steel {
            font-size: clamp(0.65rem, 0.9vw, 0.8rem);
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 8px #10b981;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.2); opacity: 1; }
        }

        .clock-digital {
            font-weight: 900;
            color: var(--accent-blue);
            font-size: clamp(1.3rem, 2.2vw, 2.2rem);
            font-family: monospace;
            text-align: right;
            line-height: 1;
        }

        .clock-date {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-align: right;
            margin-top: 4px;
        }

        /* Stats Counter Row */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .tv-stat-box {
            background: var(--bg-card);
            border: 1.5px solid var(--border-color);
            border-radius: 12px;
            padding: 10px 14px;
            text-align: center;
            box-shadow: var(--shadow-card);
        }

        .stat-label {
            font-size: clamp(0.65rem, 0.8vw, 0.75rem);
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .display-tv-number {
            font-weight: 900;
            font-size: clamp(1.4rem, 2.5vw, 2.2rem);
            line-height: 1.1;
            font-family: monospace;
        }

        /* Main Content Layout */
        .content-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 12px;
        }

        .card-tv {
            background: var(--bg-card);
            border: 1.5px solid var(--border-color);
            border-radius: 16px;
            padding: 16px;
            box-shadow: var(--shadow-card);
            display: flex;
            flex-direction: column;
        }

        .card-title-tv {
            font-size: 0.9rem;
            font-weight: 800;
            color: var(--text-title);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Table Area Area Smooth Infinite Scroll */
        .table-responsive-custom {
            max-height: 62vh;
            overflow-y: hidden; /* Sembunyikan scrollbar bawaan untuk Infinite Loop yang seamless */
            border-radius: 8px;
        }

        .table-tv {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.82rem;
        }

        /* Header Solid (Tidak Transparan) */
        .table-tv thead th {
            background-color: #e2e8f0 !important;
            color: #0F172A;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.7rem;
            padding: 12px;
            position: sticky;
            top: 0;
            z-index: 10;
            border-bottom: 2px solid #cbd5e1;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .table-tv tbody td {
            color: var(--text-main);
            border-bottom: 1px solid rgba(74, 144, 226, 0.15);
            padding: 10px 12px;
            vertical-align: middle;
            background-color: #f8fafc;
        }

        .table-tv tbody tr:hover td {
            background-color: #f1f5f9;
        }

        .progress-tv {
            height: 8px;
            border-radius: 6px;
            background: rgba(203, 213, 225, 0.6);
            overflow: hidden;
        }

        .badge-sub {
            font-size: 0.65rem;
            padding: 3px 6px;
            border-radius: 4px;
            font-weight: 700;
        }

        .blink-urgent {
            animation: urgentPulse 1s ease-in-out infinite;
        }

        @keyframes urgentPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Chart Section */
        .chart-container-box {
            height: 180px;
            position: relative;
            margin-bottom: 10px;
        }

        .chart-label {
            font-size: 0.72rem;
            font-weight: 800;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        @media (max-width: 992px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            .table-responsive-custom {
                max-height: 500px;
            }
        }

        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .tv-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .clock-digital, .clock-date {
                text-align: left;
            }
            .table-tv {
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>

<div class="monitor-wrapper">
    <!-- HEADER CORPORATE -->
    <div class="tv-header">
        <div class="company-brand-steel">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(74, 144, 226, 0.15); border: 1.5px solid var(--border-glow); display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-speedometer2" style="font-size: 1.4rem; color: #2563eb;"></i>
            </div>
            <div>
                <div class="company-name-steel">PT DUTA HITA JAYA</div>
                <div class="company-subtitle-steel">
                    <span class="pulse-dot"></span>
                    Engineering Live Monitor • Real-time Drafter Engginering Dashboard
                </div>
            </div>
        </div>
        <div>
            <div class="clock-digital" id="digital-clock">00:00:00</div>
            <div class="clock-date" id="digital-date">Loading...</div>
        </div>
    </div>

    <!-- STATS CARDS -->
    <div class="stats-grid">
        <div class="tv-stat-box">
            <div class="stat-label"><i class="bi bi-clock-history me-1"></i> SPK Antrian</div>
            <div class="display-tv-number text-primary"><?= $count_pending; ?></div>
        </div>
        <div class="tv-stat-box">
            <div class="stat-label"><i class="bi bi-play-circle me-1"></i> Sedang Berjalan</div>
            <div class="display-tv-number text-warning"><?= $count_progress; ?></div>
        </div>
        <div class="tv-stat-box">
            <div class="stat-label"><i class="bi bi-pause-circle me-1"></i> Tertunda (Delay)</div>
            <div class="display-tv-number text-danger"><?= $count_paused; ?></div>
        </div>
        <div class="tv-stat-box">
            <div class="stat-label"><i class="bi bi-check-circle me-1"></i> Total Selesai</div>
            <div class="display-tv-number text-success"><?= $count_completed; ?></div>
        </div>
    </div>

    <!-- MAIN CONTENT GRID -->
    <div class="content-grid">
        <!-- LEFT: TABLE PROGRES -->
        <div class="card-tv">
            <h6 class="card-title-tv">
                <i class="bi bi-layers-fill text-primary"></i>
                Monitoring Progres SPK Aktif Real-time
            </h6>
            <div class="table-responsive-custom" id="autoScrollTable">
                <table class="table table-tv align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 30%;">Drafter / Tim</th>
                            <th style="width: 40%;">Proyek & SPK</th>
                            <th style="width: 30%;">Progress Volume</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php if ($list_live->num_rows > 0) : ?>
                            <?php while ($live = $list_live->fetch_assoc()) : 
                                $tampilkan_drafter = !empty($live['nama_tim_gabungan']) ? $live['nama_tim_gabungan'] : $live['nama_pencetus'];
                                $persen_alokasi = ($live['total_tonase'] > 0) ? round(($live['tonase_terpakai'] / $live['total_tonase']) * 100) : 0;
                                $tv_ga = min(intval($live['total_ga']), 100);
                                $tv_model = min(intval($live['total_modeling']), 100);
                                
                                $st_badge = ($live['status'] == 'On Progress') ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle';
                                $urgensi_badge_cls = ($live['tingkat_urgensi'] == 'Urgent') ? 'bg-danger text-white blink-urgent' : (($live['tingkat_urgensi'] == 'High') ? 'bg-warning text-dark border border-warning' : 'bg-light text-secondary border');
                                $progress_bar_color = ($persen_alokasi >= 100) ? 'bg-info' : (($persen_alokasi >= 75) ? 'bg-success' : 'bg-warning');
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold mb-1 text-dark" style="font-size: 0.82rem;">
                                            <i class="bi bi-person-circle text-primary me-1"></i> 
                                            <?= htmlspecialchars($tampilkan_drafter); ?>
                                        </div>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <span class="badge-sub <?= $st_badge; ?>"><?= $live['status']; ?></span>
                                            <span class="badge-sub <?= $urgensi_badge_cls; ?>"><?= $live['tingkat_urgensi']; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 280px;" title="<?= htmlspecialchars($live['nama_proyek']); ?>">
                                            <?= htmlspecialchars($live['nama_proyek']); ?>
                                        </div>
                                        <div class="text-muted font-monospace" style="font-size: 0.72rem;">
                                            #<?= $live['no_spk']; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.72rem;">
                                            <span class="text-muted fw-medium">
                                                SD: <strong class="text-dark"><?= number_format($live['tonase_terpakai'], 0, ',', '.'); ?></strong> / <?= number_format($live['total_tonase'], 0, ',', '.'); ?> Kg
                                            </span>
                                            <span class="fw-bold text-primary"><?= $persen_alokasi; ?>%</span>
                                        </div>
                                        <div class="progress progress-tv mb-1">
                                            <div class="progress-bar <?= $progress_bar_color; ?>" 
                                                 role="progressbar" 
                                                 style="width: <?= min($persen_alokasi, 100); ?>%"></div>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <span class="badge-sub bg-teal-subtle text-teal border" style="background: rgba(13, 148, 136, 0.1); color: #0d9488;">
                                                GA: <?= $tv_ga; ?>%
                                            </span>
                                            <span class="badge-sub bg-blue-subtle text-primary border" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                                                Model: <?= $tv_model; ?>%
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted small">
                                    <i class="bi bi-check-all text-success d-block mb-1 fs-3"></i>
                                    Semua SPK aktif telah selesai diproduksi
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RIGHT: CHARTS -->
        <div class="card-tv">
            <h6 class="card-title-tv">
                <i class="bi bi-bar-chart-line-fill text-success"></i>
                Matriks Produktivitas Tim
            </h6>

            <div class="chart-label text-success">
                <i class="bi bi-calendar-month"></i> Output Shop Drawing Bulan Ini (Kg)
            </div>
            <div class="chart-container-box">
                <canvas id="tvChartShopDrawing"></canvas>
            </div>

            <div class="chart-label" style="color: #0d9488;">
                <i class="bi bi-calendar-day"></i> Segmen Progres GA Layout Hari Ini (%)
            </div>
            <div class="chart-container-box">
                <canvas id="tvChartGALayout"></canvas>
            </div>

            <div class="chart-label text-primary">
                <i class="bi bi-calendar-day"></i> Segmen Progres 3D Modeling Hari Ini (%)
            </div>
            <div class="chart-container-box">
                <canvas id="tvChart3DModeling"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="chart.js"></script>
<script src="bootstrap.bundle.min.js"></script>
<script>
    // 1. Digital Clock
    function updateClock() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', timeZone: 'Asia/Jakarta' };
        document.getElementById('digital-clock').innerText = now.toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta' });
        document.getElementById('digital-date').innerText = now.toLocaleDateString('id-ID', options);
    }
    setInterval(updateClock, 1000);
    updateClock();

    // 2. AUTO REFRESH HALAMAN SETIAP 60 DETIK (Diperpanjang agar siklus scroll berjalan halus)
    setInterval(() => { 
        window.location.reload(); 
    }, 60000);

    // 3. ENGINE INFINITE LOOP AUTO-SCROLL
    function startInfiniteScroll() {
        const scrollBox = document.getElementById('autoScrollTable');
        const tbody = document.getElementById('tableBody');
        
        if (!scrollBox || !tbody) return;

        // Cek jika tinggi isi tabel lebih besar dari container (perlu scroll)
        if (tbody.clientHeight <= scrollBox.clientHeight) return;

        // Simpan tinggi awal isi tabel asli sebelum diduplikat
        const originalHeight = tbody.clientHeight;

        // DUPLIKAT ISI BARIS TABEL (Membentuk loop tanpa batas)
        tbody.innerHTML += tbody.innerHTML;

        let scrollSpeed = 1; // Kecepatan gerak (piksel/tick)
        let isPaused = false;

        // Beri jeda 2 detik pertama saat halaman siap
        isPaused = true;
        setTimeout(() => { isPaused = false; }, 2000);

        setInterval(() => {
            if (isPaused) return;

            scrollBox.scrollTop += scrollSpeed;

            // Jika posisi scroll sudah melewati batas tinggi tabel asli
            if (scrollBox.scrollTop >= originalHeight) {
                // Sembunyi-sembunyi reset posisi scroll kembali ke 0 tanpa jeda visual
                scrollBox.scrollTop -= originalHeight;
            }
        }, 35); // Kecepatan pergerakan per milidetik
    }

    document.addEventListener("DOMContentLoaded", startInfiniteScroll);

    // 4. Chart Engine
    const labelsDrafter = <?= json_encode($nama_drafter); ?>;
    const segmentData = <?= json_encode($js_segments); ?>;

    function buildStackedDatasets(keyTarget, colorPalette) {
        if (!segmentData || segmentData.length === 0) return [];
        const spkLists = [...new Set(segmentData.map(item => item.no_spk))];
        const colorMaps = {
            'ga': ['#0d9488', '#14b8a6', '#5eead4', '#2dd4bf'],
            'model': ['#2563eb', '#3b82f6', '#60a5fa', '#1d4ed8']
        };
        
        return spkLists.map((spk, idx) => {
            const dataPoin = labelsDrafter.map(nama => {
                const match = segmentData.find(item => item.nama_lengkap.trim() === nama.trim() && item.no_spk === spk);
                return match ? parseInt(match[keyTarget]) || 0 : 0;
            });
            const colors = colorMaps[colorPalette] || colorMaps['model'];
            return {
                label: spk,
                data: dataPoin,
                backgroundColor: colors[idx % colors.length],
                borderRadius: 4
            };
        });
    }

    const commonChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: 'rgba(74, 144, 226, 0.1)' }, ticks: { font: { size: 9 } } },
            y: { grid: { display: false }, ticks: { color: '#0f172a', font: { size: 10, weight: '600' } } }
        }
    };

    // Render Charts
    new Chart(document.getElementById('tvChartShopDrawing').getContext('2d'), {
        type: 'bar',
        data: {
            labels: labelsDrafter,
            datasets: [{ data: <?= json_encode($total_tonase_kerja); ?>, backgroundColor: '#10b981', borderRadius: 4 }]
        },
        options: { ...commonChartOptions, indexAxis: 'y' }
    });

    new Chart(document.getElementById('tvChartGALayout').getContext('2d'), {
        type: 'bar',
        data: { labels: labelsDrafter, datasets: buildStackedDatasets('ga_spk', 'ga') },
        options: { ...commonChartOptions, indexAxis: 'y', scales: { ...commonChartOptions.scales, x: { stacked: true, max: 100 }, y: { stacked: true } } }
    });

    new Chart(document.getElementById('tvChart3DModeling').getContext('2d'), {
        type: 'bar',
        data: { labels: labelsDrafter, datasets: buildStackedDatasets('model_spk', 'model') },
        options: { ...commonChartOptions, indexAxis: 'y', scales: { ...commonChartOptions.scales, x: { stacked: true, max: 100 }, y: { stacked: true } } }
    });
</script>
</body>
</html>
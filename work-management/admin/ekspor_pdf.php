<?php
session_start();
require '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// TANGKAP PARAMETER FILTER DARI JANDELA MODAL POPUP
$filter_waktu = isset($_GET['filter_waktu']) ? $_GET['filter_waktu'] : 'all';
$search       = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$tgl_awal     = isset($_GET['tgl_awal']) ? mysqli_real_escape_string($conn, $_GET['tgl_awal']) : '';
$tgl_akhir    = isset($_GET['tgl_akhir']) ? mysqli_real_escape_string($conn, $_GET['tgl_akhir']) : '';

$where_clauses = [];
$info_periode  = "Semua Jangka Waktu Historis";

if ($filter_waktu === 'daily') {
    $where_clauses[] = " DATE(spk.tgl_input) = CURDATE() ";
    $info_periode  = "Hari Ini (" . date('d-m-Y') . ")";
} elseif ($filter_waktu === 'weekly') {
    $where_clauses[] = " YEARWEEK(spk.tgl_input, 1) = YEARWEEK(CURDATE(), 1) ";
    $info_periode  = "Minggu Ini";
} elseif ($filter_waktu === 'monthly') {
    $where_clauses[] = " MONTH(spk.tgl_input) = MONTH(CURDATE()) AND YEAR(spk.tgl_input) = YEAR(CURDATE()) ";
    $info_periode  = "Bulan Ini (" . date('F Y') . ")";
}

if (!empty($tgl_awal) && !empty($tgl_akhir)) {
    $where_clauses[] = " DATE(spk.tgl_input) BETWEEN '$tgl_awal' AND '$tgl_akhir' ";
    $info_periode  = date('d/m/Y', strtotime($tgl_awal)) . " s/d " . date('d/m/Y', strtotime($tgl_akhir));
}

if (!empty($search)) {
    $where_clauses[] = " (spk.no_spk LIKE '%$search%' OR spk.nama_proyek LIKE '%$search%' OR spk.nama_client LIKE '%$search%') ";
}

$where_clause = "";
if (count($where_clauses) > 0) {
    $where_clause = " WHERE " . implode(" AND ", $where_clauses);
}

$query = "SELECT spk.*, users.nama_lengkap AS nama_drafter 
          FROM spk 
          LEFT JOIN users ON spk.id_drafter = users.id_user 
          $where_clause
          ORDER BY spk.tgl_input DESC";
$data_spk = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cetak Eksekutif PDF Rekap SPK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: white; color: #334155; }
        .kop-title { font-size: 20px; font-weight: 800; color: #1e3a8a; letter-spacing: -0.5px; }
        .kop-sub { font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .table-pdf thead th { background-color: #1e3a8a !important; color: white !important; font-size: 11px; font-weight: 600; text-transform: uppercase; padding: 12px 8px; border: 1px solid #1e3a8a; }
        .table-pdf tbody td { font-size: 12px; padding: 12px 8px; border: 1px solid #e2e8f0; color: #334155; }
        
        /* Indikator Lencana Status Berwarna */
        .pdf-badge { font-size: 10px; font-weight: 700; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; display: inline-block; }
        .badge-completed { background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-progress { background-color: #fef9c3; color: #a16207; border: 1px solid #fef08a; }
        .badge-paused { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .badge-pending { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

        .approval-section { margin-top: 50px; font-size: 13px; }
        .signature-line { margin-top: 70px; border-top: 1.5px solid #334155; width: 200px; display: inline-block; font-weight: bold; color: #0f172a; }

        @media print {
            .no-print { display: none; }
            body { background: white; color: #000; }
            .table-pdf th { background-color: #1e3a8a !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom border-2 border-dark">
        <div>
            <div class="kop-title">PT DUTA HITA JAYA</div>
            <div class="kop-sub">Divisi Rekayasa Teknik &amp; Gambar Konstruksi (Engineering)</div>
        </div>
        <div class="text-end" style="font-size: 11px; color: #64748b; line-height: 1.4;">
            Jl. Jend. Sudirman No. 101, Bekasi<br>
            Email: engineering@dutahitajaya.co.id
        </div>
    </div>

    <div class="text-center my-4">
        <h4 class="fw-bold text-dark text-uppercase m-0" style="letter-spacing: -0.3px;">Laporan Rekapitulasi Progres Surat Perintah Kerja</h4>
    </div>

    <div class="row mb-4 small">
        <div class="col-6">
            <table class="table table-sm table-borderless m-0" style="width: auto;">
                <tr><td class="p-0 text-muted" style="width: 100px;">Dicetak Oleh</td><td class="p-0 px-2 text-muted">:</td><td class="p-0 fw-semibold text-dark"><?= $_SESSION['nama']; ?> (Admin)</td></tr>
                <tr><td class="p-0 text-muted">Waktu Cetak</td><td class="p-0 px-2 text-muted">:</td><td class="p-0 text-dark"><?= date('d F Y H:i:s'); ?></td></tr>
            </table>
        </div>
        <div class="col-6 text-end d-flex justify-content-end align-items-end">
            <div><span class="text-muted">Rentang Filter Periode:</span> <span class="fw-bold text-primary"><?= $info_periode; ?></span></div>
        </div>
    </div>

    <table class="table table-striped table-pdf align-middle mb-0">
        <thead class="text-center">
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">No. SPK</th>
                <th style="width: 28%;">Nama Proyek &amp; Uraian Kerja</th>
                <th style="width: 10%;">Urgensi</th>
                <th style="width: 11%;">Target Deadline</th>
                <th style="width: 11%;">Realisasi Selesai</th>
                <th style="width: 11%;">Drafter</th>
                <th style="width: 9%;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1; 
            if($data_spk->num_rows > 0) {
                while ($spk = $data_spk->fetch_assoc()) : 
                    // Konfigurasi dinamis lencana status
                    $status_low = strtolower($spk['status']);
                    $class_badge = 'badge-pending';
                    if($status_low === 'completed') $class_badge = 'badge-completed';
                    elseif($status_low === 'on progress') $class_badge = 'badge-progress';
                    elseif($status_low === 'paused') $class_badge = 'badge-paused';
                ?>
                    <tr>
                        <td class="text-center text-muted fw-bold"><?= $no++; ?></td>
                        <td class="font-monospace fw-bold text-dark"><?= $spk['no_spk']; ?></td>
                        <td>
                            <strong class="text-dark d-block mb-0.5"><?= $spk['nama_proyek']; ?></strong>
                            <span class="text-secondary small d-block" style="font-size: 11px; line-height: 1.3;"><?= $spk['deskripsi_tugas']; ?></span>
                        </td>
                        <td class="text-center fw-medium"><?= $spk['tingkat_urgensi']; ?></td>
                        <td class="text-center text-secondary font-monospace"><?= date('d/m/Y H:i', strtotime($spk['deadline'])); ?></td>
                        <td class="text-center text-secondary font-monospace"><?= $spk['tgl_selesai'] ? date('d/m/Y H:i', strtotime($spk['tgl_selesai'])) : '-'; ?></td>
                        <td class="fw-medium"><?= $spk['nama_drafter'] ? htmlspecialchars($spk['nama_drafter']) : '<em class="text-muted small">Belum diambil</em>'; ?></td>
                        <td class="text-center">
                            <span class="pdf-badge <?= $class_badge; ?>"><?= $spk['status']; ?></span>
                        </td>
                    </tr>
                <?php 
                endwhile; 
            } else {
                echo "<tr><td colspan='8' class='text-center text-muted py-4 small italic'>Tidak ditemukan rekaman data aktivitas SPK pada jangka penarikan filter ini.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <div class="row approval-section text-center">
        <div class="col-4">
            <p class="mb-0 text-muted">Dibuat Oleh,</p>
            <p class="fw-semibold text-dark mb-0">Staf Administrasi</p>
            <div class="signature-line"><?= $_SESSION['nama']; ?></div>
        </div>
        <div class="col-4 offset-4">
            <p class="mb-0 text-muted">Diketahui Oleh,</p>
            <p class="fw-semibold text-dark mb-0">Manager Engineering</p>
            <div class="signature-line">( .................................... )</div>
        </div>
    </div>

    <div class="text-center mt-5 no-print">
        <hr>
        <button onclick="window.close()" class="btn btn-sm btn-secondary px-4 fw-bold shadow-sm rounded-2">Tutup Halaman Cetak</button>
    </div>
</div>

</body>
</html>
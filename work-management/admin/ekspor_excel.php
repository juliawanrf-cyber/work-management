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

header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Rekap_SPK_Engineering.xls");

$query = "SELECT spk.*, users.nama_lengkap AS nama_drafter 
          FROM spk 
          LEFT JOIN users ON spk.id_drafter = users.id_user 
          $where_clause
          ORDER BY spk.tgl_input DESC";
$data_spk = $conn->query($query);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        /* Desain Gaya Visual Tabel Excel PT Duta Hita Jaya */
        .title-main { font-family: 'Segoe UI', Arial, sans-serif; font-size: 16pt; font-weight: bold; color: #1e3a8a; }
        .meta-text { font-family: 'Segoe UI', Arial, sans-serif; font-size: 10pt; color: #475569; }
        table { border-collapse: collapse; width: 100%; font-family: 'Segoe UI', Arial, sans-serif; font-size: 10pt; }
        th { background-color: #1e3a8a; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #000000; height: 30px; }
        td { border: 1px solid #cbd5e1; padding: 8px; vertical-align: middle; }
        .row-even { background-color: #f8fafc; } /* Zebra striping */
        .row-odd { background-color: #ffffff; }
        .text-center { text-align: center; }
        .font-code { font-family: 'Courier New', monospace; font-weight: bold; color: #0f172a; }
        .status-badge { font-weight: bold; text-align: center; text-transform: uppercase; }
    </style>
</head>
<body>

    <div class="title-main">PT DUTA HITA JAYA</div>
    <div style="font-family: 'Segoe UI', sans-serif; font-size: 11pt; font-weight: bold; color: #334155; margin-bottom: 5px;">REKAPITULASI SURAT PERINTAH KERJA (SPK) ENGINEERING</div>
    
    <table style="border: none; margin-bottom: 15px;">
        <tr style="border: none;"><td style="border: none; padding: 0;" class="meta-text">Jangka Periode Laporan</td><td style="border: none; padding: 0 5px;" class="meta-text">:</td><td style="border: none; padding: 0; font-weight: bold; color: #2563eb;" class="meta-text"><?= $info_periode; ?></td></tr>
        <tr style="border: none;"><td style="border: none; padding: 0;" class="meta-text">Petugas Administrator</td><td style="border: none; padding: 0 5px;" class="meta-text">:</td><td style="border: none; padding: 0;" class="meta-text"><?= $_SESSION['nama']; ?></td></tr>
        <tr style="border: none;"><td style="border: none; padding: 0;" class="meta-text">Waktu Unduh Berkas</td><td style="border: none; padding: 0 5px;" class="meta-text">:</td><td style="border: none; padding: 0;" class="meta-text"><?= date('d-m-Y H:i:s'); ?></td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 140px;">No. SPK</th>
                <th style="width: 220px;">Nama Proyek</th>
                <th style="width: 320px;">Deskripsi Uraian Tugas</th>
                <th style="width: 90px;">Urgensi</th>
                <th style="width: 130px;">Waktu Mulai</th>
                <th style="width: 130px;">Target Deadline</th>
                <th style="width: 130px;">Realisasi Selesai</th>
                <th style="width: 160px;">Nama Drafter</th>
                <th style="width: 110px;">Status SPK</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            if($data_spk->num_rows > 0) {
                while ($spk = $data_spk->fetch_assoc()) : 
                    $bg_row = ($no % 2 === 0) ? 'row-even' : 'row-odd';
                ?>
                    <tr class="<?= $bg_row; ?>">
                        <td class="text-center"><?= $no++; ?></td>
                        <td class="font-code text-center">'<?= $spk['no_spk']; ?></td>
                        <td><?= htmlspecialchars($spk['nama_proyek']); ?></td>
                        <td><?= htmlspecialchars($spk['deskripsi_tugas']); ?></td>
                        <td class="text-center"><?= $spk['tingkat_urgensi']; ?></td>
                        <td class="text-center"><?= $spk['tgl_mulai'] ? date('d/m/Y H:i', strtotime($spk['tgl_mulai'])) : '-'; ?></td>
                        <td class="text-center"><?= date('d/m/Y H:i', strtotime($spk['deadline'])); ?></td>
                        <td class="text-center"><?= $spk['tgl_selesai'] ? date('d/m/Y H:i', strtotime($spk['tgl_selesai'])) : '-'; ?></td>
                        <td><?= $spk['nama_drafter'] ? htmlspecialchars($spk['nama_drafter']) : 'Belum Diambil'; ?></td>
                        <td class="status-badge text-center"><?= $spk['status']; ?></td>
                    </tr>
                <?php 
                endwhile;
            } else {
                echo "<tr><td colspan='10' style='text-align:center; color:#ef4444; font-weight:bold; height:40px;'>Tidak ditemukan rekaman data SPK pada parameter tanggal ini.</td></tr>";
            }
            ?>
        </tbody>
    </table>

</body>
</html>
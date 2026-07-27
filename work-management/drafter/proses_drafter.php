<?php
session_start();
require '../config.php';

// Otorisasi pengamanan
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'drafter') {
    header("Location: ../login.php");
    exit;
}

// 1. PENANGANAN INPUT LAPORAN PROGRES HARIAN (METHOD POST FROM MODAL)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['aksi']) && $_GET['aksi'] === 'update_progress') {
    $id_spk = intval($_POST['id_spk']);
    $id_user = $_SESSION['id_user'];
    
    // Menangkap array pilihan scope kerja harian dari checkbox multiple choice
    $scope_kerja = isset($_POST['scope_kerja']) ? $_POST['scope_kerja'] : [];
    
    // BACA PARAMETER CHECKBOX PEKERJAAN TAMBAH (1 JIKA DICENTANG, 0 JIKA REGULER)
    $is_kerja_tambah = isset($_POST['is_kerja_tambah']) ? 1 : 0;
    
    // VALIDASI & AMBIL INPUT PERSEN SECARA INDEPENDEN
    $progres_ga = 0;
    $progres_modeling = 0;
    $tonase_diambil = 0;

    // --- VALIDASI HAK AKSES DAN KUOTA PERSEN UNTUK TAHAPAN GA ---
    if (in_array("GA", $scope_kerja)) {
        // Ambil nilai persen inputan (mencegah bypass input dari modal biasa atau kolaborasi)
        $progres_ga = isset($_POST['progres_ga_val']) ? intval($_POST['progres_ga_val']) : (isset($_POST['progres_ga_val_collab']) ? intval($_POST['progres_ga_val_collab']) : 0);
        
        // HANYA CEK BATAS KUOTA (100%) JIKA BUKAN PEKERJAAN TAMBAH
        if ($progres_ga > 0 && $is_kerja_tambah == 0) {
            // Cek akumulasi tersimpan di database saat ini (hanya yang reguler)
            $cek_ga = $conn->query("SELECT COALESCE(SUM(progres_ga), 0) AS total FROM spk_progres WHERE id_spk = $id_spk AND is_kerja_tambah = 0")->fetch_assoc();
            $total_ga_sekarang = $cek_ga['total'];
            $sisa_kuota_ga = 100 - $total_ga_sekarang;

            // Jika input melebihi sisa kuota, stop transaksi dan keluarkan alert pengunci
            if ($progres_ga > $sisa_kuota_ga) {
                echo "<script>
                        alert('Gagal Simpan! Akumulasi progres GA reguler saat ini sudah $total_ga_sekarang%, sisa kuota aman maksimal hanya $sisa_kuota_ga%. Input Anda ($progres_ga%) melebihi batas 100%! Jika ini revisi tambahan, centang Opsi Pekerjaan Tambah (VO).');
                        window.location='dashboard.php';
                      </script>";
                exit;
            }
        }
    }

    // --- VALIDASI HAK AKSES DAN KUOTA PERSEN UNTUK TAHAPAN MODELING ---
    if (in_array("Modeling", $scope_kerja)) {
        // Ambil nilai persen inputan
        $progres_modeling = isset($_POST['progres_modeling_val']) ? intval($_POST['progres_modeling_val']) : (isset($_POST['progres_modeling_val_collab']) ? intval($_POST['progres_modeling_val_collab']) : 0);
        
        // HANYA CEK BATAS KUOTA (100%) JIKA BUKAN PEKERJAAN TAMBAH
        if ($progres_modeling > 0 && $is_kerja_tambah == 0) {
            // Cek akumulasi tersimpan di database saat ini (hanya yang reguler)
            $cek_model = $conn->query("SELECT COALESCE(SUM(progres_modeling), 0) AS total FROM spk_progres WHERE id_spk = $id_spk AND is_kerja_tambah = 0")->fetch_assoc();
            $total_model_sekarang = $cek_model['total'];
            $sisa_kuota_model = 100 - $total_model_sekarang;

            // Jika input melebihi sisa kuota, stop transaksi dan keluarkan alert pengunci
            if ($progres_modeling > $sisa_kuota_model) {
                echo "<script>
                        alert('Gagal Simpan! Akumulasi progres Modeling reguler saat ini sudah $total_model_sekarang%, sisa kuota aman maksimal hanya $sisa_kuota_model%. Input Anda ($progres_modeling%) melebihi batas 100%! Jika ini revisi tambahan, centang Opsi Pekerjaan Tambah (VO).');
                        window.location='dashboard.php';
                      </script>";
                exit;
            }
        }
    }

    // --- EVALUASI PENENTUAN BOBOT TONASE DESIMAL (SHOP DRAWING) ---
    if (in_array("Shop Drawing", $scope_kerja)) {
        $tonase_diambil = isset($_POST['tonase_diambil']) ? (float)$_POST['tonase_diambil'] : 0;
    }

    // Ambil keterangan secara dinamis
    $ket_ga = !empty($_POST['keterangan_ga']) ? $_POST['keterangan_ga'] : (isset($_POST['keterangan_ga_collab']) ? $_POST['keterangan_ga_collab'] : '');
    $ket_modeling = !empty($_POST['keterangan_modeling']) ? $_POST['keterangan_modeling'] : (isset($_POST['keterangan_modeling_collab']) ? $_POST['keterangan_modeling_collab'] : '');
    $ket_shop = !empty($_POST['keterangan_shop']) ? $_POST['keterangan_shop'] : (isset($_POST['keterangan_shop_collab']) ? $_POST['keterangan_shop_collab'] : '');

    // Mulai merakit kembali data Log informatif berdasarkan scope yang dicentang
    $log_lines = [];
    if ($is_kerja_tambah == 1) {
        $log_lines[] = "=== [PEKERJAAN TAMBAH / ADDENDUM (VO)] ===";
    }
    if (count($scope_kerja) > 0) {
        $log_lines[] = "Fokus Kerja Hari Ini (" . implode(", ", $scope_kerja) . "):";
    }

    if (in_array("GA", $scope_kerja) && !empty(trim($ket_ga))) {
        $log_lines[] = "• GA (Kontribusi: + " . $progres_ga . "%): " . trim($ket_ga);
    }
    if (in_array("Modeling", $scope_kerja) && !empty(trim($ket_modeling))) {
        $log_lines[] = "• Modeling (Kontribusi: + " . $progres_modeling . "%): " . trim($ket_modeling);
    }
    if (in_array("Shop Drawing", $scope_kerja) && !empty(trim($ket_shop))) {
        $log_lines[] = "• Shop Drawing (Kontribusi: " . number_format($tonase_diambil, 2, ',', '.') . " Kg): " . trim($ket_shop);
    }

    // Satukan baris teks penjelasan menjadi kesatuan log riwayat pengerjaan (\n)
    $keterangan_kerja_final = implode("\n", $log_lines);
    $keterangan_bersih = mysqli_real_escape_string($conn, $keterangan_kerja_final);

    // Validasi: Progres disimpan jika ada minimal 1 scope kerja yang dipilih
    if (count($scope_kerja) > 0 && $id_spk > 0) {
        // Simpan baris riwayat pengerjaan ke database beserta tanda is_kerja_tambah
        $query_insert = "INSERT INTO spk_progres (id_spk, id_user, tonase_diambil, progres_ga, progres_modeling, keterangan_kerja, is_kerja_tambah, tgl_update) 
                         VALUES ($id_spk, $id_user, $tonase_diambil, $progres_ga, $progres_modeling, '$keterangan_bersih', $is_kerja_tambah, NOW())";
        
        if ($conn->query($query_insert)) {
            // Cek status SPK saat ini
            $cek_status = $conn->query("SELECT status, id_drafter FROM spk WHERE id_spk = $id_spk")->fetch_assoc();
            
            // Jika status proyek masih Pending, otomatis naikkan ke On Progress dan kunci ke drafter ini
            if ($cek_status['status'] === 'Pending') {
                $conn->query("UPDATE spk SET status = 'On Progress', id_drafter = $id_user, tgl_mulai = NOW() WHERE id_spk = $id_spk");
            }
            // Jika ini proyek kolaborasi dan drafter utamanya belum diset, update datanya
            elseif (empty($cek_status['id_drafter'])) {
                $conn->query("UPDATE spk SET id_drafter = $id_user WHERE id_spk = $id_spk");
            }
        }
    }
}

// 2. PENANGANAN AKSI OPERASIONAL STATUS VIA LINK GET
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_spk = intval($_GET['id']);
    $id_drafter = $_SESSION['id_user'];
    $aksi = $_GET['aksi'];

    if ($aksi === 'claim') {
        // Ambil alih dokumen proyek secara utuh & set status On Progress
        $query = "UPDATE spk SET status = 'On Progress', id_drafter = $id_drafter, tgl_mulai = NOW() WHERE id_spk = $id_spk";
        if($conn->query($query)) {
            // Buat baris logs inisialisasi awal ke database
            $conn->query("INSERT INTO spk_progres (id_spk, id_user, tonase_diambil, progres_ga, progres_modeling, keterangan_kerja, is_kerja_tambah, tgl_update) VALUES ($id_spk, $id_drafter, 0, 0, 0, 'Mengonfirmasi klaim pengerjaan dokumen SPK utama secara penuh ke meja produksi.', 0, NOW())");
        }
    } 
    elseif ($aksi === 'pause') {
        $query = "UPDATE spk SET status = 'Paused' WHERE id_spk = $id_spk AND id_drafter = $id_drafter";
        $conn->query($query);
    } 
    elseif ($aksi === 'resume') {
        $query = "UPDATE spk SET status = 'On Progress' WHERE id_spk = $id_spk AND id_drafter = $id_drafter";
        $conn->query($query);
    } 
    elseif ($aksi === 'complete') {
        $query = "UPDATE spk SET status = 'Completed', tgl_selesai = NOW() WHERE id_spk = $id_spk AND id_drafter = $id_drafter";
        $conn->query($query);
    }
}

// Kembalikan alur ke halaman utama dashboard drafter
header("Location: dashboard.php");
exit;
?>
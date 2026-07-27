<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$id_user_curr = (int)$_SESSION['id_user'];
$action       = isset($_GET['action']) ? $_GET['action'] : '';

// Update Timestamp Aktivitas User (Heartbeat Online)
$conn->query("UPDATE users SET last_active = NOW() WHERE id_user = $id_user_curr");

// -----------------------------------------------------------------------------
// 1. AMBIL DAFTAR USER / PERSONEL (DIURUTKAN CHAT TERBARU & UNREAD)
// -----------------------------------------------------------------------------
if ($action === 'get_users') {
    $query = "SELECT u.id_user, u.nama_lengkap, u.role,
                     IF(u.last_active >= NOW() - INTERVAL 5 MINUTE, 1, 0) AS is_online,
                     
                     (SELECT pesan FROM pesan_chat 
                      WHERE ((id_pengirim = u.id_user AND id_penerima = $id_user_curr) 
                             OR (id_pengirim = $id_user_curr AND id_penerima = u.id_user))
                      ORDER BY waktu_kirim DESC LIMIT 1) AS pesan_terakhir,
                     
                     (SELECT waktu_kirim FROM pesan_chat 
                      WHERE ((id_pengirim = u.id_user AND id_penerima = $id_user_curr) 
                             OR (id_pengirim = $id_user_curr AND id_penerima = u.id_user))
                      ORDER BY waktu_kirim DESC LIMIT 1) AS waktu_terakhir,
                     
                     (SELECT COUNT(*) FROM pesan_chat 
                      WHERE id_pengirim = u.id_user AND id_penerima = $id_user_curr AND status_baca = 'Unread') AS unread_count

              FROM users u
              WHERE u.id_user != $id_user_curr AND u.status_akun = 'Approved'
              ORDER BY unread_count DESC, waktu_terakhir DESC, is_online DESC, u.nama_lengkap ASC";

    $result = $conn->query($query);
    $users  = [];

    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $users[] = [
                'id_user'        => (int)$r['id_user'],
                'nama_lengkap'   => htmlspecialchars($r['nama_lengkap']),
                'role'           => $r['role'],
                'is_online'      => (int)$r['is_online'],
                'pesan_terakhir' => !empty($r['pesan_terakhir']) ? htmlspecialchars($r['pesan_terakhir']) : 'Belum ada obrolan',
                'waktu_terakhir' => !empty($r['waktu_terakhir']) ? date('H:i', strtotime($r['waktu_terakhir'])) : '',
                'unread_count'   => (int)$r['unread_count']
            ];
        }
    }

    echo json_encode(['status' => 'success', 'data' => $users]);
    exit;
}

// -----------------------------------------------------------------------------
// 2. AMBIL PESAN (GRUP DIVISI ATAU DIRECT MESSAGE)
// -----------------------------------------------------------------------------
if ($action === 'get_messages') {
    $target_raw = isset($_GET['target_id']) ? trim($_GET['target_id']) : 'group';

    // Jika target_id adalah 'group', amankan query khusus pesan grup (id_penerima IS NULL atau 0)
    if ($target_raw === 'group' || $target_raw === '' || $target_raw === '0') {
        $query = "SELECT p.*, u.nama_lengkap AS nama_pengirim, u.role AS role_pengirim 
                  FROM pesan_chat p 
                  JOIN users u ON p.id_pengirim = u.id_user 
                  WHERE p.id_penerima IS NULL OR p.id_penerima = 0 
                  ORDER BY p.waktu_kirim ASC LIMIT 100";
    } else {
        $target_user_id = (int)$target_raw;
        
        // Tandai pesan dari kontak ini sebagai 'Read'
        $conn->query("UPDATE pesan_chat SET status_baca = 'Read' WHERE id_pengirim = $target_user_id AND id_penerima = $id_user_curr");

        // Query Pesan Personal Murni Antara User Login dan Target Kontak
        $query = "SELECT p.*, u.nama_lengkap AS nama_pengirim, u.role AS role_pengirim 
                  FROM pesan_chat p 
                  JOIN users u ON p.id_pengirim = u.id_user 
                  WHERE (p.id_pengirim = $id_user_curr AND p.id_penerima = $target_user_id)
                     OR (p.id_pengirim = $target_user_id AND p.id_penerima = $id_user_curr)
                  ORDER BY p.waktu_kirim ASC LIMIT 100";
    }

    $result   = $conn->query($query);
    $messages = [];

    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $messages[] = [
                'id_chat'       => (int)$r['id_chat'],
                'pesan'         => htmlspecialchars($r['pesan']),
                'id_pengirim'   => (int)$r['id_pengirim'],
                'nama_pengirim' => htmlspecialchars($r['nama_pengirim']),
                'role_pengirim' => $r['role_pengirim'],
                'is_me'         => ((int)$r['id_pengirim'] === $id_user_curr),
                'waktu'         => date('H:i', strtotime($r['waktu_kirim']))
            ];
        }
    }

    echo json_encode(['status' => 'success', 'data' => $messages]);
    exit;
}

// -----------------------------------------------------------------------------
// 3. KIRIM PESAN CHAT
// -----------------------------------------------------------------------------
if ($action === 'send_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pesan      = isset($_POST['pesan']) ? mysqli_real_escape_string($conn, trim($_POST['pesan'])) : '';
    $target_raw = isset($_POST['target_id']) ? trim($_POST['target_id']) : 'group';

    if (!empty($pesan)) {
        if ($target_raw === 'group' || $target_raw === '' || $target_raw === '0') {
            // Pesan Grup Divisi (id_penerima = NULL)
            $query = "INSERT INTO pesan_chat (id_pengirim, id_penerima, pesan, status_baca, waktu_kirim) 
                      VALUES ($id_user_curr, NULL, '$pesan', 'Read', NOW())";
        } else {
            // Pesan Personal Direct Message
            $target_user_id = (int)$target_raw;
            $query = "INSERT INTO pesan_chat (id_pengirim, id_penerima, pesan, status_baca, waktu_kirim) 
                      VALUES ($id_user_curr, $target_user_id, '$pesan', 'Unread', NOW())";
        }

        if ($conn->query($query)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Pesan tidak boleh kosong']);
    }
    exit;
}
?>
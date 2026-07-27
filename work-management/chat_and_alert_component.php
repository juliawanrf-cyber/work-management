<?php
// Pastikan file ini di-include pada halaman yang memiliki session aktif
if (!isset($_SESSION['login'])) return;

$id_user_curr = (int)$_SESSION['id_user'];
$role_curr    = $_SESSION['role'];

// Query SPK Kritis (Sisa Deadline <= 3 Hari ATAU Sudah Overdue yang Belum Completed)
$where_kritis = "WHERE spk.status IN ('Pending', 'On Progress', 'Paused') 
                 AND DATEDIFF(spk.deadline, CURDATE()) <= 3";

if ($role_curr === 'drafter') {
    $where_kritis .= " AND spk.id_spk IN (SELECT DISTINCT id_spk FROM spk_progres WHERE id_user = $id_user_curr)";
}

$query_alert = "SELECT spk.id_spk, spk.no_spk, spk.nama_proyek, spk.deadline, spk.status, spk.tingkat_urgensi,
                       DATEDIFF(spk.deadline, CURDATE()) AS sisa_hari
                FROM spk $where_kritis ORDER BY sisa_hari ASC";
$list_alert = $conn->query($query_alert);
$total_kritis = $list_alert ? $list_alert->num_rows : 0;
?>

<!-- ========================================================================= -->
<!-- 1. POP-UP MODAL ALERT DEADLINE (H-3 & OVERDUE) -->
<!-- ========================================================================= -->
<?php if ($total_kritis > 0) : ?>
<div class="modal fade" id="modalDeadlineAlert" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold fs-6">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> PERINGATAN DEADLINE SPK KRITIS (H-3)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <p class="text-secondary small mb-3">
                    Daftar proyek berikut telah <strong>mendekati batas waktu (H-3)</strong> atau <strong>melewati tenggat hari ini (Overdue)</strong>. Harap segera dilakukan tindak lanjut!
                </p>
                <div class="table-responsive" style="max-height: 280px;">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th>No. SPK</th>
                                <th>Nama Proyek</th>
                                <th>Target Deadline</th>
                                <th>Status Tenggat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row_alert = $list_alert->fetch_assoc()) : 
                                $sisa = (int)$row_alert['sisa_hari'];
                                if ($sisa < 0) {
                                    $status_lbl = "<span class='badge bg-danger'>TERLAMBAT (" . abs($sisa) . " Hari)</span>";
                                } elseif ($sisa == 0) {
                                    $status_lbl = "<span class='badge bg-danger text-white fw-bold'>HARI INI DEADLINE!</span>";
                                } else {
                                    $status_lbl = "<span class='badge bg-warning text-dark'>Sisa $sisa Hari</span>";
                                }
                                $detail_link = "detail_spk?id=";
                            ?>
                                <tr>
                                    <td class="font-monospace fw-bold text-primary">
                                        <a href="<?= $detail_link . $row_alert['id_spk']; ?>" class="text-decoration-none"><?= $row_alert['no_spk']; ?></a>
                                    </td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($row_alert['nama_proyek']); ?></td>
                                    <td><?= date('d M Y', strtotime($row_alert['deadline'])); ?></td>
                                    <td><?= $status_lbl; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm rounded-2 fw-semibold" data-bs-dismiss="modal">Saya Mengerti</button>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (!sessionStorage.getItem('alert_deadline_shown')) {
            var alertModal = new bootstrap.Modal(document.getElementById('modalDeadlineAlert'));
            alertModal.show();
            sessionStorage.setItem('alert_deadline_shown', 'true');
        }
    });
</script>
<?php endif; ?>


<!-- ========================================================================= -->
<!-- 2. FLOATING CORPORATE CHAT DOCK DENGAN DIRECT MESSAGE REAL-TIME -->
<!-- ========================================================================= -->
<style>
    /* MODERN INDIGO CHAT THEME */
    .chat-dock-trigger {
        position: fixed; bottom: 20px; right: 20px; z-index: 1040;
        background: linear-gradient(135deg, #4F46E5 0%, #06B6D4 100%);
        color: #fff; border: 1.5px solid rgba(79, 70, 229, 0.4);
        border-radius: 50px; padding: 12px 22px; 
        box-shadow: 0 6px 24px rgba(79, 70, 229, 0.4), 0 0 20px rgba(79, 70, 229, 0.2);
        font-weight: 600; font-size: 0.875rem; cursor: pointer; 
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex; align-items: center; gap: 8px;
    }
    .chat-dock-trigger:hover { 
        transform: translateY(-3px); 
        background: linear-gradient(135deg, #6366F1 0%, #0EA5E9 100%);
        box-shadow: 0 8px 32px rgba(79, 70, 229, 0.5), 0 0 30px rgba(79, 70, 229, 0.3);
    }
    
    .chat-badge-counter {
        background: linear-gradient(135deg, #EA580C 0%, #F59E0B 100%);
        color: white; font-size: 0.7rem; font-weight: 800;
        padding: 3px 8px; border-radius: 20px; display: none;
        box-shadow: 0 0 12px rgba(234, 88, 12, 0.6);
        animation: pulse-badge 1.5s infinite;
    }
    @keyframes pulse-badge {
        0% { transform: scale(1); }
        50% { transform: scale(1.15); }
        100% { transform: scale(1); }
    }

    .chat-dock-container {
        position: fixed; bottom: 80px; right: 20px; z-index: 1040;
        width: 360px; height: 490px; 
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(249, 250, 251, 0.95) 100%);
        backdrop-filter: blur(20px);
        border-radius: 16px;
        box-shadow: 0 16px 48px rgba(79, 70, 229, 0.15), 0 0 0 1.5px rgba(79, 70, 229, 0.1);
        display: none; flex-direction: column;
        border: 2px solid rgba(79, 70, 229, 0.15);
        overflow: hidden; 
        font-family: system-ui, -apple-system, sans-serif;
    }
    
    .chat-header { 
        background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%);
        border-bottom: 2px solid rgba(79, 70, 229, 0.4);
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.2);
        color: white; padding: 14px 16px; 
        display: flex; justify-content: space-between; align-items: center; 
    }
    
    .chat-nav-tabs { 
        display: flex; 
        background: linear-gradient(135deg, rgba(79, 70, 229, 0.05) 0%, rgba(99, 102, 241, 0.02) 100%);
        border-bottom: 2px solid rgba(79, 70, 229, 0.1);
        position: relative; 
    }
    
    .chat-tab-btn { 
        flex: 1; padding: 10px; text-align: center; border: none; 
        background: transparent; font-size: 0.75rem; font-weight: 600; 
        color: #6b7280; cursor: pointer; position: relative; 
        transition: all 0.3s ease;
    }
    
    .chat-tab-btn:hover {
        background: rgba(79, 70, 229, 0.05);
        color: #4F46E5;
    }
    
    .chat-tab-btn.active { 
        background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(99, 102, 241, 0.05) 100%);
        color: #4F46E5; 
        border-bottom: 3px solid #4F46E5;
        font-weight: 700;
    }
    
    .tab-notif-dot {
        width: 8px; height: 8px; 
        background: linear-gradient(135deg, #EA580C 0%, #F59E0B 100%);
        border-radius: 50%;
        position: absolute; top: 6px; right: 12px; display: none;
        box-shadow: 0 0 8px rgba(234, 88, 12, 0.6);
    }

    .chat-body-area { 
        flex: 1; overflow-y: auto; padding: 14px; 
        background: linear-gradient(135deg, #F9FAFB 0%, #F3F4F6 100%);
        font-size: 0.8rem; 
    }
    
    .chat-bubble { 
        max-width: 80%; padding: 10px 14px; border-radius: 12px; 
        margin-bottom: 10px; position: relative; word-wrap: break-word;
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.05);
    }
    
    /* MY MESSAGE - Indigo Bubble */
    .chat-bubble.me { 
        background: linear-gradient(135deg, #4F46E5 0%, #6366F1 100%);
        color: #ffffff; 
        margin-left: auto; 
        border-bottom-right-radius: 4px;
        border: 1px solid rgba(79, 70, 229, 0.3);
    }
    
    /* OTHER MESSAGE - Light Gray Bubble */
    .chat-bubble.other { 
        background: linear-gradient(135deg, #ffffff 0%, #F9FAFB 100%);
        color: #111827; 
        margin-right: auto; 
        border: 1.5px solid rgba(79, 70, 229, 0.1);
        border-bottom-left-radius: 4px;
    }
    
    .chat-meta { 
        font-size: 0.65rem; 
        color: rgba(255, 255, 255, 0.8);
        margin-top: 4px; 
        display: flex; 
        justify-content: space-between; 
        gap: 8px; 
    }
    
    .chat-bubble.other .chat-meta {
        color: #6b7280;
    }

    .user-online-dot { 
        width: 9px; height: 9px; 
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border-radius: 50%; 
        display: inline-block;
        box-shadow: 0 0 6px rgba(16, 185, 129, 0.5);
    }
    
    .user-offline-dot { 
        width: 9px; height: 9px; 
        background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
        border-radius: 50%; 
        display: inline-block; 
    }
    
    .user-item-row { 
        display: flex; align-items: center; justify-content: space-between; 
        padding: 12px 12px; border-radius: 10px; cursor: pointer; 
        transition: all 0.3s ease; 
        border-bottom: 1px solid rgba(79, 70, 229, 0.05);
        background: linear-gradient(135deg, #ffffff 0%, #F9FAFB 100%);
        margin-bottom: 6px;
        border: 1px solid rgba(79, 70, 229, 0.1);
    }
    
    .user-item-row:hover { 
        background: linear-gradient(135deg, rgba(79, 70, 229, 0.05) 0%, rgba(99, 102, 241, 0.02) 100%);
        border-color: rgba(79, 70, 229, 0.2);
        transform: translateX(-3px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.1);
    }
    
    .unread-badge-user {
        background: linear-gradient(135deg, #EA580C 0%, #F59E0B 100%);
        color: white; font-size: 0.65rem; font-weight: 800;
        padding: 3px 7px; border-radius: 10px; min-width: 20px; text-align: center;
        box-shadow: 0 2px 8px rgba(234, 88, 12, 0.4);
    }
    
    /* Input Area Styling */
    #chatInputWrapper {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(249, 250, 251, 0.9) 100%);
        border-top: 2px solid rgba(79, 70, 229, 0.1);
    }
    
    #chatInputWrapper input {
        border: 1.5px solid rgba(79, 70, 229, 0.2);
        border-radius: 10px;
    }
    
    #chatInputWrapper input:focus {
        border-color: #4F46E5;
        box-shadow: 0 0 0 0.25rem rgba(79, 70, 229, 0.2), 0 0 12px rgba(79, 70, 229, 0.15);
    }
    
    #chatInputWrapper button {
        background: linear-gradient(135deg, #4F46E5 0%, #6366F1 100%);
        border: none;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        transition: all 0.3s ease;
    }
    
    #chatInputWrapper button:hover {
        background: linear-gradient(135deg, #6366F1 0%, #818cf8 100%);
        box-shadow: 0 6px 16px rgba(79, 70, 229, 0.3);
        transform: translateY(-2px);
    }
</style>

<!-- Tautan Pembuka Chat dengan Badge Merah Unread -->
<button class="chat-dock-trigger" id="btnOpenChat">
    <i class="bi bi-chat-dots-fill"></i>
    <span>Chat Internal</span>
    <span class="chat-badge-counter" id="mainChatBadge">0</span>
</button>

<!-- Window Container Chat -->
<div class="chat-dock-container" id="chatContainer">
    <div class="chat-header">
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-link text-white p-0 me-1" id="btnBackToUsers" style="display: none;" onclick="backToUsersList()">
                <i class="bi bi-arrow-left fs-5"></i>
            </button>
            <i class="bi bi-building-fill text-info" id="chatHeaderIcon"></i>
            <div>
                <div class="fw-bold" style="font-size: 0.85rem;" id="chatHeaderTitle">Grup Tim Engineering</div>
                <div style="font-size: 0.65rem;" class="text-white-50" id="chatHeaderSub">PT Duta Hita Jaya</div>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" id="btnCloseChat" style="font-size: 0.75rem;"></button>
    </div>

    <div class="chat-nav-tabs">
        <button type="button" class="chat-tab-btn active" id="tabGroup">
            <i class="bi bi-people-fill me-1"></i> Grup Divisi
            <span class="tab-notif-dot" id="dotGroupNotif"></span>
        </button>
        <button type="button" class="chat-tab-btn" id="tabUsers">
            <i class="bi bi-person-lines-fill me-1"></i> Personel Online
            <span class="tab-notif-dot" id="dotUsersNotif"></span>
        </button>
    </div>

    <!-- Area Layar Chat / Kontak -->
    <div class="chat-body-area" id="chatBody">
        <!-- Konten diisi dinamis via JavaScript -->
    </div>

    <!-- Input Form Pengiriman Pesan -->
    <div class="p-2 bg-white border-top d-flex gap-2 align-items-center" id="chatInputWrapper">
        <input type="text" id="inputPesanChat" class="form-control form-control-sm" placeholder="Ketik pesan internal..." autocomplete="off">
        <button type="button" class="btn btn-primary btn-sm px-3" onclick="kirimPesanChat()"><i class="bi bi-send-fill"></i></button>
    </div>
</div>

<script>
    let activeMode = 'group'; // 'group', 'users', atau 'direct'
    let directUserId = null;  // menyimpan ID User target jika activeMode === 'direct'
    let isChatOpen = false;
    let lastKnownMessagesCount = 0;

    const ajaxChatUrl = '<?= (file_exists('ajax_chat.php')) ? 'ajax_chat.php' : '../ajax_chat.php'; ?>';

    // EFEK NOTIFIKASI NADA POP
    function playNotificationSound() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime);
            gain.gain.setValueAtTime(0.08, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.00001, ctx.currentTime + 0.25);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.25);
        } catch (e) {}
    }

    // EVENT LISTENER TAB PEMINDAH
    document.getElementById('tabGroup').addEventListener('click', function(e) {
        e.preventDefault();
        switchChatTab('group');
    });

    document.getElementById('tabUsers').addEventListener('click', function(e) {
        e.preventDefault();
        switchChatTab('users');
    });

    // LISTENER TOMBOL OPEN / CLOSE CHAT
    document.getElementById('btnOpenChat').addEventListener('click', () => {
        const container = document.getElementById('chatContainer');
        isChatOpen = !isChatOpen;
        container.style.display = isChatOpen ? 'flex' : 'none';
        
        if (isChatOpen) {
            document.getElementById('mainChatBadge').style.display = 'none';
            document.getElementById('mainChatBadge').innerText = '0';
            
            if (activeMode === 'group') {
                switchChatTab('group');
            } else if (activeMode === 'users') {
                switchChatTab('users');
            } else if (activeMode === 'direct') {
                loadChatMessages();
            }
        }
    });

    document.getElementById('btnCloseChat').addEventListener('click', () => {
        document.getElementById('chatContainer').style.display = 'none';
        isChatOpen = false;
    });

    // ENGINE POLLING TIAP 3 DETIK
    setInterval(() => {
        refreshChatEngine();
    }, 3000);

    function refreshChatEngine() {
        if (isChatOpen) {
            if (activeMode === 'users') {
                loadUsersOnline();
            } else {
                loadChatMessages(true); // Silent update pesan grup / direct
            }
        } else {
            checkBackgroundNotifications();
        }
    }

    function checkBackgroundNotifications() {
        fetch(`${ajaxChatUrl}?action=get_users`)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    let totalUnreadPersonal = 0;
                    res.data.forEach(u => {
                        totalUnreadPersonal += u.unread_count;
                    });

                    if (totalUnreadPersonal > 0) {
                        const badge = document.getElementById('mainChatBadge');
                        badge.innerText = totalUnreadPersonal;
                        badge.style.display = 'inline-block';
                        document.getElementById('dotUsersNotif').style.display = 'block';
                    }
                }
            });
    }

    function switchChatTab(tab) {
        const btnGroup = document.getElementById('tabGroup');
        const btnUsers = document.getElementById('tabUsers');
        const chatBody = document.getElementById('chatBody');
        
        chatBody.innerHTML = '';
        document.getElementById('btnBackToUsers').style.display = 'none';

        if (tab === 'group') {
            activeMode = 'group';
            directUserId = null;

            btnGroup.classList.add('active');
            btnUsers.classList.remove('active');

            document.getElementById('dotGroupNotif').style.display = 'none';
            document.getElementById('chatHeaderTitle').innerText = "Grup Tim Engineering";
            document.getElementById('chatHeaderSub').innerText = "PT Duta Hita Jaya";
            document.getElementById('chatInputWrapper').style.display = 'flex';
            
            loadChatMessages();
        } else {
            activeMode = 'users';
            directUserId = null;

            btnUsers.classList.add('active');
            btnGroup.classList.remove('active');

            document.getElementById('chatHeaderTitle').innerText = "Daftar Personel";
            document.getElementById('chatHeaderSub').innerText = "Personal Direct Message";
            document.getElementById('chatInputWrapper').style.display = 'none';
            
            loadUsersOnline();
        }
    }

    function loadUsersOnline() {
        fetch(`${ajaxChatUrl}?action=get_users`)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    let totalUnread = 0;
                    let html = '';

                    res.data.forEach(u => {
                        totalUnread += u.unread_count;
                        const dot = u.is_online == 1 ? '<span class="user-online-dot"></span>' : '<span class="user-offline-dot"></span>';
                        const unreadBadge = u.unread_count > 0 ? `<span class="unread-badge-user ms-1">${u.unread_count}</span>` : '';
                        
                        html += `
                            <div class="user-item-row" onclick="startDirectMessage(${u.id_user}, '${u.nama_lengkap.replace(/'/g, "\\'")}')">
                                <div style="max-width: 70%;">
                                    <div class="d-flex align-items-center gap-1">
                                        <strong class="text-dark" style="font-size:0.8rem;">${u.nama_lengkap}</strong>
                                        <span class="badge bg-light text-dark border" style="font-size:8px;">${u.role.toUpperCase()}</span>
                                    </div>
                                    <div class="text-muted text-truncate" style="font-size:0.7rem; max-width: 200px;">
                                        ${u.pesan_terakhir}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div style="font-size:0.65rem;" class="text-muted mb-1">${u.waktu_terakhir}</div>
                                    <div class="d-flex align-items-center justify-content-end gap-1">
                                        ${dot}
                                        ${unreadBadge}
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    if (totalUnread > 0) {
                        document.getElementById('dotUsersNotif').style.display = 'block';
                    } else {
                        document.getElementById('dotUsersNotif').style.display = 'none';
                    }

                    if (activeMode === 'users') {
                        document.getElementById('chatBody').innerHTML = html || '<div class="text-center text-muted small py-4">Tidak ada personel terdaftar.</div>';
                    }
                }
            });
    }

    function startDirectMessage(userId, userName) {
        activeMode = 'direct';
        directUserId = parseInt(userId);

        document.getElementById('chatHeaderTitle').innerText = userName;
        document.getElementById('chatHeaderSub').innerText = "Personal Direct Message";
        document.getElementById('btnBackToUsers').style.display = 'inline-block';
        document.getElementById('chatInputWrapper').style.display = 'flex';
        
        document.getElementById('tabGroup').classList.remove('active');
        document.getElementById('tabUsers').classList.remove('active');
        
        loadChatMessages();
    }

    function backToUsersList() {
        switchChatTab('users');
    }

    function loadChatMessages(silent = false) {
        if (activeMode === 'users') return;

        const targetParam = (activeMode === 'group') ? 'group' : directUserId;

        fetch(`${ajaxChatUrl}?action=get_messages&target_id=${targetParam}`)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    if (activeMode === 'users') return;

                    const chatBody = document.getElementById('chatBody');
                    const newCount = res.data.length;
                    
                    if (silent && newCount > lastKnownMessagesCount && newCount > 0) {
                        const lastMsg = res.data[newCount - 1];
                        if (!lastMsg.is_me) {
                            playNotificationSound();
                        }
                    }
                    lastKnownMessagesCount = newCount;

                    let html = '';
                    res.data.forEach(m => {
                        const bubbleClass = m.is_me ? 'me' : 'other';
                        
                        // DI TAMPILAN DIRECT MESSAGE (PERSONAL), NAMA TIDAK DIULANG DI BUBBLE CHAT
                        const showSenderHeader = (activeMode === 'group' && !m.is_me);

                        html += `
                            <div class="chat-bubble ${bubbleClass}">
                                ${showSenderHeader ? `<div class="fw-bold text-primary" style="font-size:0.7rem;">${m.nama_pengirim} <span class="badge bg-secondary" style="font-size:8px;">${m.role_pengirim}</span></div>` : ''}
                                <div>${m.pesan}</div>
                                <div class="chat-meta">
                                    <span>${m.waktu} WIB</span>
                                </div>
                            </div>
                        `;
                    });
                    
                    chatBody.innerHTML = html || '<div class="text-center text-muted small py-4">Belum ada obrolan. Mulai pesan pertama!</div>';
                    
                    if (!silent || (chatBody.scrollHeight - chatBody.scrollTop <= chatBody.clientHeight + 120)) {
                        chatBody.scrollTop = chatBody.scrollHeight;
                    }
                }
            });
    }

    function kirimPesanChat() {
        const input = document.getElementById('inputPesanChat');
        const pesan = input.value.trim();
        if (!pesan) return;

        const targetParam = (activeMode === 'group') ? 'group' : directUserId;

        const formData = new FormData();
        formData.append('pesan', pesan);
        formData.append('target_id', targetParam);

        fetch(`${ajaxChatUrl}?action=send_message`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                input.value = '';
                loadChatMessages();
            } else {
                alert("Gagal mengirim pesan: " + res.message);
            }
        })
        .catch(err => {
            console.error("Error sending message:", err);
        });
    }

    document.getElementById('inputPesanChat').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            kirimPesanChat();
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        refreshChatEngine();
    });
</script>
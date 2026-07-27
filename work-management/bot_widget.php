<div id="chat-widget-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; font-family: 'Segoe UI', sans-serif;">
    
    <button id="chat-button" class="btn btn-primary rounded-circle shadow-lg d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: #4F46E5; border: none; transition: all 0.3s ease;">
        <i class="bi bi-chat-dots-fill text-white fs-3"></i>
    </button>

    <div id="chat-box" class="card shadow-lg d-none" style="width: 360px; height: 480px; border-radius: 12px; overflow: hidden; border: none; display: flex; flex-direction: column;">
        
        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color: #4F46E5; padding: 12px 15px;">
            <div class="d-flex align-items-center">
                <i class="bi bi-robot fs-4 me-2"></i>
                <div>
                    <h6 class="mb-0 fw-bold small">Asisten Virtual WM</h6>
                    <span style="font-size: 10px; color: #cbd5e1;"><i class="bi bi-circle-fill text-success" style="font-size: 8px;"></i> Gemini AI Integrated</span>
                </div>
            </div>
            <button id="close-chat" class="btn-close btn-close-white btn-sm" type="button"></button>
        </div>
        
        <div id="chat-body" class="card-body bg-light" style="overflow-y: auto; flex: 1; padding: 15px; font-size: 13px; display: flex; flex-direction: column; gap: 10px;">
            <div class="text-start">
                <div class="d-inline-block bg-white text-dark p-2 rounded shadow-sm" style="max-width: 85%; border-left: 3px solid #4F46E5;">
                    Halo! Saya asisten pintar sistem <b>Work Management</b>. Ada yang bisa saya bantu mengenai pelacakan SPK, alokasi beban kerja (Kg), target performa Drafter, atau logika koding program ini?
                </div>
            </div>
        </div>

        <div class="card-footer bg-white p-2 border-top">
            <div class="input-group">
                <input type="text" id="user-input" class="form-control form-control-sm shadow-none" placeholder="Ketik pertanyaan Anda..." autocomplete="off">
                <button id="send-button" class="btn btn-primary btn-sm" style="background-color: #4F46E5; border: none;" type="button">
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
    // Fungsi mengamankan input dari XSS / Tag HTML berbahaya
    function escapeHtml(text) {
        return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    }

    // Buka Tutup Panel Chat
    $('#chat-button').click(function(){
        $('#chat-box').toggleClass('d-none');
        if(!$('#chat-box').hasClass('d-none')) {
            $('#user-input').focus();
        }
    });
    
    $('#close-chat').click(function(){
        $('#chat-box').addClass('d-none');
    });

    // Eksekusi Kirim Pesan
    function kirimPesan() {
        let teksUser = $('#user-input').val().trim();
        if(teksUser === '') return;

        // Render pesan pengguna ke dalam chat body
        $('#chat-body').append(`
            <div class="text-end mb-1">
                <div class="d-inline-block bg-primary" style="background-color: #4F46E5 !important; text-white p-2 rounded shadow-sm" style="max-width: 85%; text-align: left;">
                    ${escapeHtml(teksUser)}
                </div>
            </div>
        `);
        
        $('#user-input').val('');
        $('#chat-body').scrollTop($('#chat-body')[0].scrollHeight);

        // Tambahkan Animasi Loading Mengetik Sementara
        let loadingId = "typing_" + Date.now();
        $('#chat-body').append(`
            <div class="text-start mb-1" id="${loadingId}">
                <div class="d-inline-block bg-white text-muted p-2 rounded shadow-sm" style="max-width: 85%;">
                    <span class="spinner-border spinner-border-sm text-primary me-1" role="status"></span> Asisten sedang berpikir...
                </div>
            </div>
        `);
        $('#chat-body').scrollTop($('#chat-body')[0].scrollHeight);

        // AJAX Request menggunakan path relatif agar aman dari error CORS / localhost mismatch
        $.ajax({
            url: 'proses_bot.php', 
            type: 'POST',
            data: { pesan: teksUser },
            dataType: 'json',
            success: function(response){
                $(`#${loadingId}`).remove(); // Hapus animasi loading
                
                // Tampilkan balasan sukses dari server backend
                $('#chat-body').append(`
                    <div class="text-start mb-1">
                        <div class="d-inline-block bg-white text-dark p-2 rounded shadow-sm" style="max-width: 85%; border-left: 3px solid #4F46E5;">
                            ${response.balasan}
                        </div>
                    </div>
                `);
                $('#chat-body').scrollTop($('#chat-body')[0].scrollHeight);
            },
            error: function(){
                $(`#${loadingId}`).remove(); // Hapus animasi loading
                $('#chat-body').append(`
                    <div class="text-start mb-1">
                        <div class="d-inline-block bg-danger-subtle text-danger p-2 rounded shadow-sm" style="max-width: 85%;">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Gagal terhubung dengan server asisten virtual.
                        </div>
                    </div>
                `);
                $('#chat-body').scrollTop($('#chat-body')[0].scrollHeight);
            }
        });
    }

    // Trigger Klik Tombol & Enter Keyboard
    $('#send-button').click(function(){ kirimPesan(); });
    $('#user-input').keypress(function(e){ if(e.which == 13) { kirimPesan(); } });
});
</script>
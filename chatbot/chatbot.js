$(document).ready(function() {
    // === CẤU HÌNH ===
    const CHAT_PROCESS_URL = 'ajax_chat_process.php';   // File xử lý gửi tin
    const GET_MESSAGES_URL = 'ajax.get_messages.php';   // File lấy lịch sử chat
    const POLL_INTERVAL = 3000;                         // Thời gian cập nhật tin mới (3s)

    // === BIẾN TOÀN CỤC ===
    var chatPollInterval = null;
    var isChatOpen = false;

    // === CÁC ELEMENT ===
    const $chatWindow = $('#chat-window');
    const $chatBody = $('#chat-body');
    const $chatInput = $('#chat-input');
    const $typingIndicator = $('#typing-indicator');

    // 1. MỞ CỬA SỔ CHAT
    $('#chat-bubble').click(function() {
        // Ẩn nút bubble, hiện cửa sổ chat (dùng css flex)
        $(this).fadeOut(200);
        $chatWindow.css('display', 'flex').hide().fadeIn(300);
        
        isChatOpen = true;
        loadChatMessages(); // Tải lịch sử tin nhắn
        scrollToBottom();
    });

    // 2. ĐÓNG CỬA SỔ CHAT
    $('#close-chat').click(function() {
        $chatWindow.fadeOut(300);
        $('#chat-bubble').fadeIn(300);
        
        isChatOpen = false;
        stopPolling(); // Dừng cập nhật để tiết kiệm tài nguyên
    });

    // 3. GỬI TIN NHẮN KHI NHẤN NÚT
    $('#send-btn').click(function() {
        sendMessage();
    });

    // 4. GỬI TIN NHẮN KHI NHẤN ENTER
    $chatInput.keypress(function(e) {
        if (e.which == 13) {
            sendMessage();
        }
    });

    // === HÀM XỬ LÝ CHÍNH ===

    function sendMessage() {
        var msg = $chatInput.val().trim();
        if (msg === '') return;

        // Hiển thị tin nhắn User ngay lập tức (cho cảm giác mượt)
        appendMessage(msg, 'user');
        $chatInput.val('');
        
        // Hiện hiệu ứng "Đang trả lời..."
        $typingIndicator.show();
        scrollToBottom();

        // Gửi Ajax sang PHP xử lý
        $.ajax({
            url: CHAT_PROCESS_URL,
            method: 'POST',
            dataType: 'json',
            data: { message: msg },
            success: function(response) {
                $typingIndicator.hide();

                // Nếu Bot trả lời
                if (response.reply) {
                    appendMessage(response.reply, 'bot');
                }

                // Kiểm tra trạng thái: Nếu đang chờ Nhân viên -> Bật chế độ Polling
                if (response.status === 'waiting' || response.conversation_status === 'human_requested') {
                    startPolling();
                }
                
                scrollToBottom();
            },
            error: function(xhr, status, error) {
                $typingIndicator.hide();
                console.error("Lỗi gửi tin nhắn:", error);
                appendMessage("Lỗi kết nối. Vui lòng thử lại sau.", 'bot');
            }
        });
    }

    // Tải toàn bộ lịch sử chat
    function loadChatMessages() {
        $.ajax({
            url: GET_MESSAGES_URL,
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                // Xử lý HTML trả về để khớp với CSS mới (.chat-message .user/bot)
                // (Phòng trường hợp file PHP trả về class cũ msg-row)
                var fixedHtml = data.html
                    .replace(/msg-row/g, '') 
                    .replace(/msg-bubble/g, '')
                    .replace(/msg-user/g, 'chat-message user')
                    .replace(/msg-bot/g, 'chat-message bot');

                $chatBody.html(fixedHtml);
                scrollToBottom();

                // Nếu trạng thái hội thoại là đang chat với người -> Bật Polling
                if (data.status === 'in_progress' || data.status === 'human_requested') {
                    startPolling();
                }
            },
            error: function() {
                console.log("Không tải được lịch sử chat.");
            }
        });
    }

    // Bắt đầu tự động cập nhật tin nhắn (Polling)
    function startPolling() {
        if (chatPollInterval) return; // Nếu đang chạy rồi thì thôi

        chatPollInterval = setInterval(function() {
            if (!isChatOpen) return;

            $.ajax({
                url: GET_MESSAGES_URL,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    // Chuẩn hóa HTML
                    var fixedHtml = data.html
                        .replace(/msg-row/g, '')
                        .replace(/msg-bubble/g, '')
                        .replace(/msg-user/g, 'chat-message user')
                        .replace(/msg-bot/g, 'chat-message bot');

                    // Chỉ cập nhật DOM nếu có sự thay đổi nội dung
                    var currentHtml = $chatBody.html();
                    if (fixedHtml.length !== currentHtml.length) {
                        $chatBody.html(fixedHtml);
                        scrollToBottom();
                    }

                    // Nếu hội thoại đã đóng hoặc quay về Bot -> Dừng polling
                    if (data.status === 'closed' || data.status === 'bot') {
                        stopPolling();
                    }
                }
            });
        }, POLL_INTERVAL);
    }

    // Dừng cập nhật
    function stopPolling() {
        if (chatPollInterval) {
            clearInterval(chatPollInterval);
            chatPollInterval = null;
        }
    }

    // Hàm thêm tin nhắn vào giao diện
    function appendMessage(text, sender) {
        // sender: 'user' hoặc 'bot'
        var html = `<div class="chat-message ${sender}">${escapeHtml(text)}</div>`;
        $chatBody.append(html);
    }

    // Cuộn xuống cuối khung chat
    function scrollToBottom() {
        $chatBody.scrollTop($chatBody[0].scrollHeight);
    }

    // Hàm bảo mật: Ngăn chặn mã độc XSS
    function escapeHtml(text) {
        if (!text) return "";
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
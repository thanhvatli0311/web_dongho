<?php
session_start();
require __DIR__ . '/../includes/db.php'; 

if (file_exists(__DIR__ . '/../templates/adminheader.php')) {
    include __DIR__ . '/../templates/adminheader.php';
}

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../pages/login.php");
    exit;
}

// Lấy danh sách hội thoại
$stmt_conv = $pdo->query("
    SELECT c.id, c.user_identifier, c.status, 
           (SELECT m.message_text FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) as last_message
    FROM conversations c
    WHERE c.status IN ('human_requested', 'in_progress')
    ORDER BY 
        CASE WHEN c.status = 'human_requested' THEN 1 ELSE 2 END,
        c.updated_at DESC
");
$conversations = $stmt_conv->fetchAll();

$stmt_intents = $pdo->query("SELECT id, name FROM intents ORDER BY name ASC");
$intents = $stmt_intents->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Live Chat Support</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .chat-container { display: flex; height: 80vh; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; margin-top: 20px; }
        
        /* Cột trái */
        .conversation-list { width: 300px; border-right: 1px solid #dee2e6; overflow-y: auto; background: #fff; }
        .conversation-item { padding: 15px; border-bottom: 1px solid #f1f1f1; cursor: pointer; transition: 0.2s; position: relative; }
        .conversation-item:hover { background-color: #f8f9fa; }
        .conversation-item.active { background-color: #e3f2fd; border-left: 4px solid #007bff; }
        .status-badge { font-size: 11px; padding: 2px 6px; border-radius: 4px; color: white; float: right; }
        .status-human_requested { background-color: #ffc107; color: #333; animation: blink 1s infinite; }
        .status-in_progress { background-color: #28a745; }
        @keyframes blink { 50% { opacity: 0.6; } }

        /* Cột phải */
        .chat-window { flex: 1; display: flex; flex-direction: column; background: #fff; }
        .messages { flex-grow: 1; padding: 20px; overflow-y: auto; background-color: #f8f9fa; display: flex; flex-direction: column; gap: 10px; }
        
        .message { padding: 10px 15px; border-radius: 15px; max-width: 75%; position: relative; word-wrap: break-word; font-size: 14px; margin-bottom: 5px; }
        
        /* User (Trái) */
        .message.user { background: #e9ecef; align-self: flex-start; border-bottom-left-radius: 2px; color: #333; }
        
        /* Admin (Phải) */
        .message.admin { background: #007bff; color: white; align-self: flex-end; border-bottom-right-radius: 2px; }
        
        /* Bot (Trái - Xám đậm) */
        .message.bot { background: #6c757d; color: white; align-self: flex-start; border-bottom-left-radius: 2px; font-style: italic; }

        .reply-area { padding: 15px; border-top: 1px solid #dee2e6; background: #fff; }
        
        /* Nút Dạy Bot (ĐÃ SỬA CSS CHO DỄ NHÌN HƠN) */
        .teach-bot-btn {
            display: none; 
            position: absolute; 
            top: -12px; 
            right: -10px;
            font-size: 11px; 
            padding: 3px 10px; 
            cursor: pointer;
            background-color: #28a745; 
            color: white; 
            border: 2px solid white; 
            border-radius: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 10;
        }
        /* Hiện nút khi hover vào tin nhắn User */
        .message.user:hover .teach-bot-btn { display: block; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="chat-container">
        <!-- LIST -->
        <div class="conversation-list" id="conversation-list">
            <?php if (empty($conversations)): ?>
                <div class="p-4 text-center text-muted">Không có yêu cầu nào.</div>
            <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                    <?php 
                        $statusClass = 'status-' . $conv['status'];
                        $statusText = ($conv['status'] == 'human_requested') ? 'Đang chờ' : 'Đang chat';
                    ?>
                    <div class="conversation-item" data-id="<?= $conv['id'] ?>">
                        <strong>User: <?= htmlspecialchars(substr($conv['user_identifier'], 0, 10)) ?>...</strong>
                        <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                        <p class="mb-0 text-truncate text-muted small mt-1">
                            <?= htmlspecialchars($conv['last_message'] ?? '') ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- CHAT -->
        <div class="chat-window">
            <div class="messages" id="messages-container">
                <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                    <p>Chọn khách hàng để bắt đầu chat.</p>
                </div>
            </div>
            <div class="reply-area">
                <form id="reply-form" style="display: none;">
                    <div class="input-group">
                        <input type="text" id="admin-message-input" class="form-control" placeholder="Nhập tin nhắn..." autocomplete="off" required>
                        <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane"></i> Gửi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DẠY BOT -->
<div class="modal fade" id="teachBotModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="fas fa-graduation-cap"></i> Dạy Bot Kiến Thức Mới</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="teach-form">
            <div class="mb-3">
                <label class="fw-bold">Khách đã hỏi:</label>
                <div class="p-2 bg-light border rounded" id="user-question-text"></div>
            </div>
            <div class="mb-3">
                <label class="fw-bold">Câu trả lời nên là (Bot sẽ học):</label>
                <textarea class="form-control" id="admin-answer-text" rows="2" placeholder="Nhập câu trả lời chuẩn tại đây..."></textarea>
            </div>
            <hr>
            <div class="mb-3">
                <label>Gán vào chủ đề (Intent) có sẵn:</label>
                <select class="form-select" id="intent-select">
                    <option value="">-- Tạo chủ đề mới bên dưới --</option>
                    <?php foreach ($intents as $intent): ?>
                        <option value="<?= $intent['id'] ?>"><?= htmlspecialchars($intent['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Hoặc tạo tên chủ đề mới (VD: #GIA_CA, #BAO_HANH):</label>
                <input type="text" class="form-control" id="new-intent-input" placeholder="#TEN_CHU_DE">
            </div>
            <input type="hidden" id="hidden-user-question">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
        <button type="button" class="btn btn-success" id="save-teaching-btn">Lưu & Huấn Luyện</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let teachModal = new bootstrap.Modal(document.getElementById('teachBotModal'));
    let activeConversationId = null;
    let messageInterval = null;

    // 1. Chọn hội thoại
    document.getElementById('conversation-list').addEventListener('click', function(e) {
        const item = e.target.closest('.conversation-item');
        if (item) {
            document.querySelectorAll('.conversation-item').forEach(el => el.classList.remove('active'));
            item.classList.add('active');
            activeConversationId = item.dataset.id;
            
            document.getElementById('reply-form').style.display = 'block';
            document.getElementById('messages-container').innerHTML = '<div class="text-center p-3">Đang tải...</div>';
            
            loadMessages(activeConversationId);
            if (messageInterval) clearInterval(messageInterval);
            messageInterval = setInterval(() => loadMessages(activeConversationId, false), 3000); 
        }
    });

    // 2. Tải tin nhắn (ĐÃ SỬA LOGIC HIỂN THỊ NÚT DẠY)
    async function loadMessages(convId, scroll = true) {
        try {
            const res = await fetch(`ajax_get_messages.php?id=${convId}`);
            const msgs = await res.json();
            
            if(scroll) document.getElementById('messages-container').innerHTML = '';
            
            let html = '';
            msgs.forEach((msg, i) => {
                const sender = msg.sender; 
                const text = escapeHtml(msg.message_text);
                
                let btnHtml = '';
                
                // === LOGIC MỚI: Hiện nút dạy ngay trên tin nhắn USER ===
                if (sender === 'user') {
                    btnHtml = `<button class="teach-bot-btn" 
                                data-q="${text}" 
                                onclick="openTeachModal(this)">
                                <i class="fas fa-graduation-cap"></i> Dạy Bot
                                </button>`;
                }

                html += `<div class="message ${sender}">
                            ${text} ${btnHtml}
                         </div>`;
            });
            
            const container = document.getElementById('messages-container');
            if (container.innerHTML !== html) {
                container.innerHTML = html;
                if(scroll) container.scrollTop = container.scrollHeight;
            }
        } catch (err) { console.error(err); }
    }

    // 3. Gửi tin nhắn
    document.getElementById('reply-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const input = document.getElementById('admin-message-input');
        const text = input.value.trim();
        if (!text) return;

        await fetch('ajax_admin_reply.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ conversation_id: activeConversationId, message: text })
        });
        
        input.value = '';
        loadMessages(activeConversationId, true);
    });

    // 4. Mở Modal (Được gọi từ onclick trong HTML)
    window.openTeachModal = function(btn) {
        const question = btn.dataset.q;
        
        // Điền câu hỏi của khách vào modal
        document.getElementById('user-question-text').textContent = question;
        document.getElementById('hidden-user-question').value = question;
        
        // Reset câu trả lời để Admin tự nhập
        document.getElementById('admin-answer-text').value = ""; 
        document.getElementById('intent-select').value = "";
        document.getElementById('new-intent-input').value = "";
        
        teachModal.show();
    };

    // 5. Lưu Dạy Bot
    document.getElementById('save-teaching-btn').addEventListener('click', async function() {
        const q = document.getElementById('hidden-user-question').value;
        const a = document.getElementById('admin-answer-text').value.trim(); // Lấy từ Textarea
        const iid = document.getElementById('intent-select').value;
        const newI = document.getElementById('new-intent-input').value.trim();

        if(!a) { alert("Bạn chưa nhập câu trả lời mẫu cho Bot!"); return; }
        if(!iid && !newI) { alert("Vui lòng chọn hoặc tạo chủ đề (Intent)!"); return; }

        this.disabled = true; this.textContent = "Đang gửi...";
        
        try {
            const res = await fetch('ajax_teach_bot.php', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ question: q, answer: a, intentId: iid, newIntent: newI })
            });
            const data = await res.json();

            if(data.status === 'success') {
                alert("✅ Đã dạy xong! Bot đã thông minh hơn.");
                teachModal.hide();
            } else {
                alert("❌ Lỗi: " + data.message);
            }
        } catch(e) {
            alert("Lỗi kết nối server");
        }

        this.disabled = false; this.textContent = "Lưu & Huấn Luyện";
    });

    function escapeHtml(text) {
        if(!text) return "";
        return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    }
});
</script>
</body>
</html>
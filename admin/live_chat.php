<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../templates/adminheader.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

$stmt_conv = $pdo->query("
    SELECT c.id, c.user_identifier, c.status, 
           (SELECT m.message_text FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) as last_message
    FROM conversations c
    WHERE c.status = 'human_requested' OR c.status = 'in_progress'
    ORDER BY c.updated_at DESC
");
$conversations = $stmt_conv->fetchAll();

$stmt_intents = $pdo->query("SELECT id, name FROM intents ORDER BY name ASC");
$intents = $stmt_intents->fetchAll();
?>
<style>
    .chat-container { display: flex; height: 80vh; }
    .conversation-list { width: 30%; border-right: 1px solid #ccc; overflow-y: auto; }
    .chat-window { width: 70%; display: flex; flex-direction: column; }
    .messages { display: flex; flex-direction: column; flex-grow: 1; padding: 20px; overflow-y: auto; background: #f9f9f9; }
    .message { padding: 8px 12px; border-radius: 18px; margin-bottom: 10px; max-width: 70%; position: relative;}
    .message.user { background: #e9e9eb; align-self: flex-start; }
    .message.admin { background: #007bff; color: white; align-self: flex-end; }
    .reply-area { padding: 10px; border-top: 1px solid #ccc; }
    .conversation-item { padding: 15px; border-bottom: 1px solid #eee; cursor: pointer; }
    .conversation-item:hover, .conversation-item.active { background-color: #f0f0f0; }
    .conversation-item strong { display: block; }
    .conversation-item small { color: #888; }
    .teach-bot-btn {
        position: absolute; bottom: -5px; left: -10px; font-size: 10px;
        padding: 2px 6px; cursor: pointer; background-color: #28a745;
        color: white; border: none; border-radius: 10px;
        opacity: 0; transition: opacity 0.2s; z-index: 10;
    }
    .message.admin:hover .teach-bot-btn { opacity: 1; }
</style>

<div class="container-fluid mt-4">
    <h2>Live Chat Support</h2>
    <div class="chat-container card">
        <div class="conversation-list" id="conversation-list">
            <?php foreach ($conversations as $conv): ?>
                <div class="conversation-item" data-id="<?= $conv['id'] ?>">
                    <strong>User: <?= htmlspecialchars(substr($conv['user_identifier'], 0, 12)) ?>...</strong>
                    <small>Trạng thái: <?= htmlspecialchars($conv['status']) ?></small>
                    <p class="mb-0 text-truncate"><?= htmlspecialchars($conv['last_message']) ?></p>
                </div>
            <?php endforeach; ?>
             <?php if (empty($conversations)): ?>
                <p class="p-3">Không có yêu cầu hỗ trợ nào.</p>
            <?php endif; ?>
        </div>

        <div class="chat-window">
            <div class="messages" id="messages-container">
                <p class="text-center text-muted pt-3">Vui lòng chọn một cuộc trò chuyện để bắt đầu.</p>
            </div>
            <div class="reply-area">
                <form id="reply-form" style="display: none;">
    <input type="hidden" id="conversation-id-input" name="conversation_id">
    <div class="input-group">
        <input type="text" id="admin-message-input" class="form-control" placeholder="Nhập tin nhắn trả lời..." required>
        <!-- ĐÃ BỎ DIV input-group-append THỪA -->
        <button class="btn btn-primary" type="submit">Gửi</button>
    </div>
</form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="teachBotModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Dạy lại cho Chatbot</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="teach-form">
            <div class="mb-3">
                <label class="form-label">Câu hỏi của khách:</label>
                <p class="border p-2 bg-light rounded" id="user-question-text"></p>
            </div>
            <div class="mb-3">
                <label class="form-label">Câu trả lời của bạn:</label>
                <p class="border p-2 bg-light rounded" id="admin-answer-text"></p>
            </div>
            <hr>
            <div class="mb-3">
                <label for="intent-select" class="form-label">Chọn một Intent có sẵn:</label>
                <select class="form-select" id="intent-select">
                    <option value="">-- Chọn Intent --</option>
                    <?php foreach ($intents as $intent): ?>
                        <option value="<?= $intent['id'] ?>"><?= htmlspecialchars($intent['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="new-intent-input" class="form-label">Hoặc tạo Intent mới:</label>
                <input type="text" class="form-control" id="new-intent-input" placeholder="Ví dụ: #TRA_GOP">
            </div>
            <input type="hidden" id="hidden-user-question">
            <input type="hidden" id="hidden-admin-answer">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
        <button type="button" class="btn btn-primary" id="save-teaching-btn">Lưu lại & Dạy Bot</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const conversationList = document.getElementById('conversation-list');
    const messagesContainer = document.getElementById('messages-container');
    const replyForm = document.getElementById('reply-form');
    const conversationIdInput = document.getElementById('conversation-id-input');
    const adminMessageInput = document.getElementById('admin-message-input');
    const teachModalEl = document.getElementById('teachBotModal');
    let teachModal;
    if (typeof bootstrap !== 'undefined') {
        teachModal = new bootstrap.Modal(teachModalEl);
    }
    const saveTeachingBtn = document.getElementById('save-teaching-btn');
    let activeConversationId = null;
    let messageInterval = null;

    conversationList.addEventListener('click', function(e) {
        const item = e.target.closest('.conversation-item');
        if (item) {
            document.querySelectorAll('.conversation-item').forEach(el => el.classList.remove('active'));
            item.classList.add('active');
            activeConversationId = item.dataset.id;
            conversationIdInput.value = activeConversationId;
            replyForm.style.display = 'flex';
            messagesContainer.innerHTML = '<p class="text-center text-muted pt-3">Đang tải tin nhắn...</p>';
            loadMessages(activeConversationId);
            if (messageInterval) clearInterval(messageInterval);
            messageInterval = setInterval(() => loadMessages(activeConversationId, false), 3000); 
        }
    });

    async function loadMessages(convId, showLoading = true) {
        try {
            const response = await fetch(`ajax_get_messages.php?id=${convId}`);
            if (!response.ok) {
                messagesContainer.innerHTML = `<p class="text-danger">Lỗi khi tải tin nhắn. Status: ${response.status}</p>`;
                return;
            }
            const messages = await response.json();
            if (showLoading) messagesContainer.innerHTML = '';
            let html = '';
            for (let i = 0; i < messages.length; i++) {
                const msg = messages[i];
                let messageHtml = `<div class="message ${msg.sender}">${escapeHtml(msg.message_text)}`;
                if (msg.sender === 'admin' && i > 0 && messages[i-1].sender === 'user') {
                    const userQuestion = messages[i-1].message_text;
                    const adminAnswer = msg.message_text;
                    messageHtml += `<button class="teach-bot-btn" data-question="${escapeHtml(userQuestion)}" data-answer="${escapeHtml(adminAnswer)}">Dạy lại</button>`;
                }
                messageHtml += `</div>`;
                html += messageHtml;
            }
            if (messagesContainer.innerHTML !== html) {
                 messagesContainer.innerHTML = html;
                 messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        } catch (error) {
            console.error('Fetch Error:', error);
        }
    }
    
    replyForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const messageText = adminMessageInput.value.trim();
        if (!messageText) return;
        try {
            await fetch('ajax_admin_reply.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ conversation_id: activeConversationId, message: messageText })
            });
            adminMessageInput.value = '';
            loadMessages(activeConversationId);
        } catch (err) {
            alert('Lỗi khi gửi tin nhắn');
        }
    });

    messagesContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('teach-bot-btn')) {
            const btn = e.target;
            document.getElementById('user-question-text').textContent = btn.dataset.question;
            document.getElementById('admin-answer-text').textContent = btn.dataset.answer;
            document.getElementById('hidden-user-question').value = btn.dataset.question;
            document.getElementById('hidden-admin-answer').value = btn.dataset.answer;
            document.getElementById('intent-select').selectedIndex = 0;
            document.getElementById('new-intent-input').value = '';
            if (teachModal) teachModal.show();
        }
    });

    saveTeachingBtn.addEventListener('click', async function() {
        const question = document.getElementById('hidden-user-question').value;
        const answer = document.getElementById('hidden-admin-answer').value;
        const intentId = document.getElementById('intent-select').value;
        const newIntentName = document.getElementById('new-intent-input').value.trim();
        if (!intentId && !newIntentName) {
            alert('Vui lòng chọn một Intent hoặc tạo mới.');
            return;
        }
        this.disabled = true;
        this.textContent = 'Đang lưu...';
        try {
            const response = await fetch('ajax_teach_bot.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ question, answer, intentId, newIntent: newIntentName })
            });
            const result = await response.json();
            if (result.status === 'success') {
                alert('Đã lưu kiến thức mới thành công!\nHãy nhớ nhấn "Huấn luyện lại AI" để chatbot cập nhật.');
                if (teachModal) teachModal.hide();
            } else {
                alert('Lỗi: ' + result.message);
            }
        } catch (err) {
            alert('Lỗi kết nối: ' + err);
        }
        this.disabled = false;
        this.textContent = 'Lưu lại & Dạy Bot';
    });

    function escapeHtml(unsafe) {
        return unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
});
</script>
document.addEventListener('DOMContentLoaded', function() {
    const chatBubble = document.getElementById('chat-bubble');
    const chatWindow = document.getElementById('chat-window');
    const closeChatBtn = document.getElementById('close-chat');
    const sendBtn = document.getElementById('send-btn');
    const chatInput = document.getElementById('chat-input');
    const chatBody = document.getElementById('chat-body');

    // Mở cửa sổ chat
    chatBubble.addEventListener('click', () => {
        chatWindow.style.display = 'flex';
        chatBubble.style.display = 'none';
    });

    // Đóng cửa sổ chat
    closeChatBtn.addEventListener('click', () => {
        chatWindow.style.display = 'none';
        chatBubble.style.display = 'block';
    });

    // Gửi tin nhắn khi nhấn nút
    sendBtn.addEventListener('click', sendMessage);
    
    // Gửi tin nhắn khi nhấn Enter
    chatInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    function sendMessage() {
        const userMessage = chatInput.value.trim();
        if (userMessage === '') return;

        // Hiển thị tin nhắn của người dùng
        appendMessage(userMessage, 'user');
        chatInput.value = '';

        // Gửi tin nhắn đến backend và nhận phản hồi
        fetch('../chatbot/handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'message=' + encodeURIComponent(userMessage)
        })
        .then(response => response.json())
        .then(data => {
            appendMessage(data.reply, 'bot');
        })
        .catch(error => {
            console.error('Error:', error);
            appendMessage('Xin lỗi, đã có lỗi xảy ra.', 'bot');
        });
    }

    function appendMessage(message, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('chat-message', sender);
        messageDiv.innerHTML = `<p>${message}</p>`;
        chatBody.appendChild(messageDiv);
        // Cuộn xuống tin nhắn mới nhất
        chatBody.scrollTop = chatBody.scrollHeight;
    }
});
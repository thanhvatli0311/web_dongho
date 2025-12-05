<?php
session_start();
// Kết nối DB
require __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// Lấy user_identifier (ưu tiên session đã lưu, nếu không có thì lấy session_id)
$user_identifier = $_SESSION['user_identifier'] ?? session_id();

// 1. Lấy cuộc hội thoại gần nhất
$stmt = $pdo->prepare("SELECT id, status FROM conversations WHERE user_identifier = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$user_identifier]);
$conv = $stmt->fetch();

if (!$conv) {
    // Nếu chưa có hội thoại nào, trả về rỗng
    echo json_encode(['html' => '', 'status' => 'bot']);
    exit;
}

// 2. Lấy tin nhắn (SỬA TÊN CỘT CHO KHỚP DB: sender, message_text)
$stmt = $pdo->prepare("SELECT sender, message_text FROM messages WHERE conversation_id = ? ORDER BY created_at ASC");
$stmt->execute([$conv['id']]);
$msgs = $stmt->fetchAll();

$html = '';
foreach ($msgs as $m) {
    // Kiểm tra người gửi để gán class CSS (User bên phải, Bot/Admin bên trái)
    // Cột trong DB là 'sender'
    $cls = ($m['sender'] == 'user') ? 'msg-user' : 'msg-bot'; 
    
    // Cột nội dung là 'message_text'
    $html .= '<div class="msg-row '.$cls.'"><div class="msg-bubble">'.htmlspecialchars($m['message_text']).'</div></div>';
}

// Trả về HTML và trạng thái hiện tại (để JS biết có cần polling không)
echo json_encode(['html' => $html, 'status' => $conv['status']]);
?>
<?php
// Bắt đầu session để có thể lưu conversation_id
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../includes/db.php'; 

header('Content-Type: application/json');

$userMessage = isset($_POST['message']) ? trim($_POST['message']) : '';
$userSessionId = session_id();
$conversation_id = null;

if (empty($userMessage)) {
    echo json_encode(['reply' => 'Vui lòng nhập tin nhắn.']);
    exit;
}

try {
    // --- BƯỚC 1: TÌM HOẶC TẠO MỚI CUỘC TRÒ CHUYỆN ---
    // Kiểm tra xem đã có conversation nào đang hoạt động cho user này chưa
    if (isset($_SESSION['conversation_id'])) {
        $stmt = $pdo->prepare("SELECT id, status FROM conversations WHERE id = ? AND status != 'closed'");
        $stmt->execute([$_SESSION['conversation_id']]);
        $conversation = $stmt->fetch();
        if ($conversation) {
            $conversation_id = $conversation['id'];
        }
    }

    // Nếu không có, tạo mới
    if (!$conversation_id) {
        $stmt = $pdo->prepare("INSERT INTO conversations (user_identifier, status) VALUES (?, 'bot')");
        $stmt->execute([$userSessionId]);
        $conversation_id = $pdo->lastInsertId();
        $_SESSION['conversation_id'] = $conversation_id;
        $conversation = ['id' => $conversation_id, 'status' => 'bot'];
    }

    // --- BƯỚC 2: LƯU TIN NHẮN CỦA NGƯỜI DÙNG ---
    $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender, message_text) VALUES (?, 'user', ?)");
    $stmt->execute([$conversation_id, $userMessage]);

    // --- BƯỚC 3: XỬ LÝ LOGIC VÀ LẤY CÂU TRẢ LỜI ---
    $reply = '';

    // Nếu admin đã tiếp nhận, bot sẽ không trả lời nữa
    if ($conversation['status'] === 'in_progress') {
        echo json_encode(['reply' => '']); // Trả về rỗng, admin sẽ trả lời
        exit;
    }
    
    // Logic gọi API Python để lấy intent...
    $pythonApiUrl = 'http://127.0.0.1:5000/get_intent';
    $response = @file_get_contents($pythonApiUrl, false, stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => 'Content-type: application/json',
            'content' => json_encode(['message' => $userMessage])
        ]
    ]));

    if ($response === FALSE) {
        $reply = 'Xin lỗi, hệ thống AI đang gặp sự cố. Vui lòng thử lại sau.';
    } else {
        $result = json_decode($response, true);
        $intentName = $result['intent'] ?? '#KHONG_HIEU';

        // --- BƯỚC 4: KÍCH HOẠT HUMAN HANDOFF NẾU CẦN ---
        if ($intentName === '#KHONG_HIEU' || strpos(strtolower($userMessage), 'gặp nhân viên') !== false) {
            $stmt = $pdo->prepare("UPDATE conversations SET status = 'human_requested' WHERE id = ?");
            $stmt->execute([$conversation_id]);
            $reply = 'Tôi chưa hiểu rõ ý bạn. Tôi đã chuyển yêu cầu của bạn đến chuyên viên tư vấn. Vui lòng chờ trong giây lát...';
        } else {
            // Lấy câu trả lời từ CSDL như cũ
            $stmt = $pdo->prepare("SELECT r.response_text FROM responses r JOIN intents i ON r.intent_id = i.id WHERE i.name = ? ORDER BY RAND() LIMIT 1");
            $stmt->execute([$intentName]);
            $reply = $stmt->fetchColumn();
            if (!$reply) {
                 $reply = 'Tôi hiểu ý bạn nhưng chưa có câu trả lời. Tôi sẽ học hỏi thêm.';
            }
        }
    }
    
    // --- BƯỚC 5: LƯU CÂU TRẢ LỜI CỦA BOT ---
    if (!empty($reply)) {
        $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender, message_text) VALUES (?, 'bot', ?)");
        $stmt->execute([$conversation_id, $reply]);
    }

    echo json_encode(['reply' => $reply]);

} catch (PDOException $e) {
    // Ghi log lỗi thay vì die()
    error_log("Chatbot Error: " . $e->getMessage());
    echo json_encode(['reply' => 'Xin lỗi, hệ thống đang có lỗi.']);
}
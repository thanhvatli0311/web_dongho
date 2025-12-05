<?php
session_start();
require __DIR__ . '/../includes/db.php'; 

header('Content-Type: application/json');

$userMessage = isset($_POST['message']) ? trim($_POST['message']) : '';
$userSessionId = $_SESSION['user_identifier'] ?? session_id(); 

if (empty($userMessage)) {
    echo json_encode(['reply' => '']); exit;
}

try {
    // 1. Tìm hoặc Tạo hội thoại
    $stmt = $pdo->prepare("SELECT id, status FROM conversations WHERE user_identifier = ? AND status != 'closed' ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute([$userSessionId]);
    $conversation = $stmt->fetch();

    if ($conversation) {
        $conversation_id = $conversation['id'];
        $status = $conversation['status'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO conversations (user_identifier, status) VALUES (?, 'bot')");
        $stmt->execute([$userSessionId]);
        $conversation_id = $pdo->lastInsertId();
        $status = 'bot';
    }

    // 2. Lưu tin nhắn User
    $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender, message_text) VALUES (?, 'user', ?)");
    $stmt->execute([$conversation_id, $userMessage]);
    $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?")->execute([$conversation_id]);

    // =================================================================================
    // ★ TÍNH NĂNG MỚI: TỰ ĐỘNG BẮT SỐ ĐIỆN THOẠI (REGEX)
    // =================================================================================
    // Regex tìm số điện thoại VN (10 số, bắt đầu bằng 0)
    $phoneRegex = "/(03|05|07|08|09|01[2|6|8|9])+([0-9]{8})\b/";
    
    if (preg_match($phoneRegex, $userMessage, $matches)) {
        $phoneNumber = $matches[0];
        
        // Lưu vào bảng customer_leads
        $stmtLead = $pdo->prepare("INSERT INTO customer_leads (user_identifier, phone_number, message_content) VALUES (?, ?, ?)");
        $stmtLead->execute([$userSessionId, $phoneNumber, $userMessage]);

        // Bot phản hồi ngay lập tức
        $bot_reply = "Em đã ghi nhận số điện thoại <b>$phoneNumber</b>. Nhân viên cửa hàng sẽ liên hệ với anh/chị sớm nhất ạ! 🥰";
        
        // Lưu tin Bot trả lời
        $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender, message_text) VALUES (?, 'bot', ?)");
        $stmt->execute([$conversation_id, $bot_reply]);

        // (Tùy chọn) Chuyển trạng thái sang chờ nhân viên để Bot không nói leo nữa
        // $pdo->prepare("UPDATE conversations SET status = 'human_requested' WHERE id = ?")->execute([$conversation_id]);

        echo json_encode(['status' => 'success', 'reply' => $bot_reply, 'conversation_status' => $status]);
        exit; // Kết thúc luôn, không cần hỏi Python
    }
    // =================================================================================


    // 3. Nếu đang chờ nhân viên -> Bot im lặng (Logic cũ)
    if ($status === 'in_progress' || $status === 'human_requested') {
        echo json_encode(['status' => 'waiting', 'reply' => null, 'conversation_status' => $status]); 
        exit;
    }

    // 4. GỌI API PYTHON (Logic cũ)
    $pythonApiUrl = 'http://127.0.0.1:5000/get_intent';
    $ch = curl_init($pythonApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['message' => $userMessage]));
    curl_setopt($ch, CURLOPT_TIMEOUT, 2); 
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $bot_reply = "";
    $is_fallback = false;

    if ($httpCode !== 200 || !$response) {
        $bot_reply = "Hệ thống đang bảo trì AI.";
        $is_fallback = true;
    } else {
        $result = json_decode($response, true);
        if (isset($result['is_fallback']) && $result['is_fallback'] == true) {
            $is_fallback = true;
        } else {
            $intentName = $result['intent'];
            $stmt = $pdo->prepare("SELECT r.response_text FROM responses r JOIN intents i ON r.intent_id = i.id WHERE i.name = ? ORDER BY RAND() LIMIT 1");
            $stmt->execute([$intentName]);
            $fetched_reply = $stmt->fetchColumn();
            
            if ($fetched_reply) $bot_reply = $fetched_reply;
            else $is_fallback = true;
        }
    }

    // 5. Xử lý Fallback
    if ($is_fallback) {
        $stmt = $pdo->prepare("UPDATE conversations SET status = 'human_requested' WHERE id = ?");
        $stmt->execute([$conversation_id]);
        $status = 'human_requested';
        $bot_reply = 'Em chưa hiểu rõ ý của anh/chị. Em đã chuyển cuộc hội thoại cho nhân viên tư vấn. Vui lòng để lại SĐT hoặc đợi giây lát ạ!';
    }

    // 6. Lưu câu trả lời của Bot
    $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender, message_text) VALUES (?, 'bot', ?)");
    $stmt->execute([$conversation_id, $bot_reply]);

    echo json_encode(['status' => 'success', 'reply' => $bot_reply, 'conversation_status' => $status]);

} catch (PDOException $e) {
    echo json_encode(['reply' => 'Lỗi: ' . $e->getMessage()]);
}
?>
<?php
// Bắt đầu session
session_start();
require __DIR__ . '/../includes/db.php';

if (!isset($pdo)) {
    die("Lỗi: Không thể kết nối CSDL.");
}

// === 1. XỬ LÝ THÊM / XÓA DỮ LIỆU ===
if (isset($_POST['add_intent'])) {
    $name = trim($_POST['intent_name']);
    if (!empty($name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO intents (name) VALUES (?)");
            $stmt->execute([$name]);
        } catch (PDOException $e) {}
    }
    header("Location: chatbot_manager.php"); exit;
}
if (isset($_POST['add_phrase'])) {
    $stmt = $pdo->prepare("INSERT INTO training_phrases (intent_id, phrase_text) VALUES (?, ?)");
    $stmt->execute([$_POST['intent_id'], $_POST['phrase_text']]);
    header("Location: chatbot_manager.php"); exit;
}
if (isset($_POST['add_response'])) {
    $stmt = $pdo->prepare("INSERT INTO responses (intent_id, response_text) VALUES (?, ?)");
    $stmt->execute([$_POST['intent_id'], $_POST['response_text']]);
    header("Location: chatbot_manager.php"); exit;
}
// Xóa
if (isset($_GET['delete_phrase'])) {
    $pdo->prepare("DELETE FROM training_phrases WHERE id = ?")->execute([$_GET['delete_phrase']]);
    header("Location: chatbot_manager.php"); exit;
}
if (isset($_GET['delete_response'])) {
    $pdo->prepare("DELETE FROM responses WHERE id = ?")->execute([$_GET['delete_response']]);
    header("Location: chatbot_manager.php"); exit;
}
if (isset($_GET['delete_intent'])) {
    $pdo->prepare("DELETE FROM intents WHERE id = ?")->execute([$_GET['delete_intent']]);
    header("Location: chatbot_manager.php"); exit;
}

// === 2. LẤY DỮ LIỆU HIỂN THỊ ===
$intents = $pdo->query("SELECT * FROM intents ORDER BY name")->fetchAll();

$phrasesByIntent = [];
$responsesByIntent = [];

if (!empty($intents)) {
    $phrases = $pdo->query("SELECT * FROM training_phrases")->fetchAll();
    foreach ($phrases as $p) $phrasesByIntent[$p['intent_id']][] = $p;

    $responses = $pdo->query("SELECT * FROM responses")->fetchAll();
    foreach ($responses as $r) $responsesByIntent[$r['intent_id']][] = $r;
}

// === 3. [QUAN TRỌNG] LẤY CÂU HỎI BOT BÓ TAY TỪ LIVE CHAT ===
// Dùng message_text cho đúng với DB của bạn
$missedQuestions = [];
try {
    $sqlMissed = "SELECT DISTINCT m.message_text 
                  FROM messages m 
                  JOIN conversations c ON m.conversation_id = c.id 
                  WHERE c.status = 'human_requested' AND m.sender = 'user' 
                  ORDER BY m.created_at DESC LIMIT 10";
    $missedQuestions = $pdo->query($sqlMissed)->fetchAll();
} catch (Exception $e) { /* Bỏ qua nếu lỗi */ }
if (file_exists(__DIR__ . '/../templates/adminheader.php')) {
    include __DIR__ . '/../templates/adminheader.php';
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Chatbot AI</title>
    <style>
        :root { --primary: #007bff; --danger: #dc3545; --success: #28a745; --bg: #f4f7f9; }
        body { font-family: sans-serif; background-color: var(--bg); padding: 20px; }
        
        .page-layout { display: flex; gap: 20px; align-items: flex-start; }
        .main-col { flex: 3; }
        .side-col { flex: 1; background: white; padding: 15px; border-radius: 8px; position: sticky; top: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }

        h1 { text-align: center; color: #333; margin-bottom: 20px; }
        
        .intent-block { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .intent-header { display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .intent-header h2 { margin: 0; color: var(--primary); font-size: 1.4em; }

        ul { list-style: none; padding: 0; }
        li { background: #f8f9fa; padding: 8px 12px; margin-bottom: 5px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #eee; }
        
        .btn { padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; color: white; text-decoration: none; font-size: 0.9em; }
        .btn-del { background: var(--danger); font-size: 0.8em; }
        .btn-add { background: var(--primary); padding: 10px 15px; }
        
        .retrain-section { text-align: center; margin-bottom: 30px; }
        .btn-train { background: var(--success); font-size: 1.2em; padding: 12px 25px; border-radius: 25px; box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3); transition: 0.3s; }
        .btn-train:hover { transform: scale(1.05); }

        form { display: flex; gap: 10px; margin-top: 10px; }
        input[type="text"], textarea { flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        
        /* Sidebar Styles */
        .suggestion-item { cursor: pointer; background: #fff3cd; color: #856404; border: 1px solid #ffeeba; margin-bottom: 8px; padding: 10px; border-radius: 5px; transition: 0.2s; }
        .suggestion-item:hover { transform: translateX(-5px); background: #ffeeba; }
        .hint-text { font-size: 0.9em; color: #666; font-style: italic; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container">
    <h1>🧠 Trung tâm Huấn luyện Chatbot</h1>

    <div class="retrain-section">
        <button id="retrain-button" class="btn btn-train">🚀 Cập nhật Trí tuệ cho Bot</button>
        <p id="retrain-status" style="margin-top: 10px; font-weight: bold; height: 20px;"></p>
    </div>

    <div class="page-layout">
        <!-- CỘT CHÍNH: QUẢN LÝ INTENT -->
        <div class="main-col">
            <!-- Form thêm Intent mới -->
            <div class="intent-block" style="background: #e9ecef; text-align: center;">
                <h3>Tạo chủ đề mới</h3>
                <form method="POST">
                    <input type="text" name="intent_name" placeholder="VD: #CHINH_SACH_BAO_HANH" required>
                    <button type="submit" name="add_intent" class="btn btn-add">Tạo mới</button>
                </form>
            </div>

            <?php if (empty($intents)): ?>
                <p style="text-align: center;">Chưa có dữ liệu.</p>
            <?php else: ?>
                <?php foreach ($intents as $intent): ?>
                    <div class="intent-block">
                        <div class="intent-header">
                            <h2><?= htmlspecialchars($intent['name']) ?></h2>
                            <a href="?delete_intent=<?= $intent['id'] ?>" class="btn btn-del" onclick="return confirm('Xóa chủ đề này?')">Xóa</a>
                        </div>

                        <!-- Training Phrases -->
                        <div>
                            <strong>Khách hỏi:</strong>
                            <ul>
                                <?php if (!empty($phrasesByIntent[$intent['id']])): ?>
                                    <?php foreach ($phrasesByIntent[$intent['id']] as $phrase): ?>
                                        <li>
                                            <?= htmlspecialchars($phrase['phrase_text']) ?>
                                            <a href="?delete_phrase=<?= $phrase['id'] ?>" class="btn btn-del">X</a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                            <form method="POST">
                                <input type="hidden" name="intent_id" value="<?= $intent['id'] ?>">
                                <input type="text" name="phrase_text" placeholder="Thêm câu hỏi mẫu..." required>
                                <button type="submit" name="add_phrase" class="btn btn-add">Thêm</button>
                            </form>
                        </div>

                        <!-- Responses -->
                        <div style="margin-top: 20px; border-top: 1px dashed #ccc; padding-top: 15px;">
                            <strong>Bot trả lời:</strong>
                            <ul>
                                <?php if (!empty($responsesByIntent[$intent['id']])): ?>
                                    <?php foreach ($responsesByIntent[$intent['id']] as $res): ?>
                                        <li style="background: #e2e6ea;">
                                            <?= htmlspecialchars($res['response_text']) ?>
                                            <a href="?delete_response=<?= $res['id'] ?>" class="btn btn-del">X</a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                            <form method="POST">
                                <input type="hidden" name="intent_id" value="<?= $intent['id'] ?>">
                                <textarea name="response_text" placeholder="Nhập câu trả lời..." required rows="1"></textarea>
                                <button type="submit" name="add_response" class="btn btn-add">Thêm</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- CỘT PHỤ: GỢI Ý TỪ LIVE CHAT -->
        <div class="side-col">
            <h3 style="color: #dc3545; margin-top: 0;">🔥 Cần dạy ngay</h3>
            <p class="hint-text">Những câu hỏi khách hàng mà Bot không hiểu (đã chuyển nhân viên):</p>
            
            <?php if(empty($missedQuestions)): ?>
                <p style="color: green;">Bot đang làm tốt!</p>
            <?php else: ?>
                <?php foreach($missedQuestions as $q): ?>
                    <div class="suggestion-item" onclick="copyText('<?= htmlspecialchars(addslashes($q['message_text'])) ?>')">
                        <?= htmlspecialchars($q['message_text']) ?>
                        <div style="font-size: 11px; color: #666; margin-top: 5px;">(Bấm để copy)</div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function copyText(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert("Đã copy: " + text + "\nBây giờ hãy Paste vào ô 'Thêm câu hỏi mẫu' ở chủ đề phù hợp.");
        });
    }

    document.getElementById('retrain-button').addEventListener('click', function() {
        const statusEl = document.getElementById('retrain-status');
        statusEl.textContent = '⏳ Đang gửi dữ liệu sang Python...';
        statusEl.style.color = '#d39e00';
        
        fetch('http://127.0.0.1:5000/retrain', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    statusEl.textContent = '✅ Thành công! Bot đã học được kiến thức mới.';
                    statusEl.style.color = 'green';
                } else {
                    statusEl.textContent = '❌ Lỗi: ' + data.message;
                    statusEl.style.color = 'red';
                }
            })
            .catch(err => {
                statusEl.textContent = '❌ Lỗi kết nối đến server Python (api.py).';
                statusEl.style.color = 'red';
            });
    });
</script>

</body>
</html>
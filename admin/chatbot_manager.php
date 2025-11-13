<?php
// Phần code PHP xử lý logic giữ nguyên
session_start();

require __DIR__ . '/../includes/db.php';

if (file_exists(__DIR__ . '/../templates/adminheader.php')) {
    include __DIR__ . '/../templates/adminheader.php';
}

if (!isset($pdo)) {
    die("Lỗi: Không thể kết nối CSDL. Vui lòng kiểm tra file includes/db.php.");
}

// === XỬ LÝ FORM SUBMIT (THÊM/XÓA) ===
// ... (toàn bộ code PHP xử lý form của bạn vẫn giữ nguyên ở đây) ...
// Thêm Intent mới
if (isset($_POST['add_intent'])) {
    $name = trim($_POST['intent_name']);
    if (!empty($name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO intents (name) VALUES (?)");
            $stmt->execute([$name]);
        } catch (PDOException $e) { /* Bỏ qua lỗi trùng lặp */ }
    }
    header("Location: chatbot_manager.php"); exit;
}
// Thêm câu mẫu (Training Phrase)
if (isset($_POST['add_phrase'])) {
    $stmt = $pdo->prepare("INSERT INTO training_phrases (intent_id, phrase_text) VALUES (?, ?)");
    $stmt->execute([$_POST['intent_id'], $_POST['phrase_text']]);
    header("Location: chatbot_manager.php"); exit;
}
// Thêm câu trả lời (Response)
if (isset($_POST['add_response'])) {
    $stmt = $pdo->prepare("INSERT INTO responses (intent_id, response_text) VALUES (?, ?)");
    $stmt->execute([$_POST['intent_id'], $_POST['response_text']]);
    header("Location: chatbot_manager.php"); exit;
}
// Xóa
if (isset($_GET['delete_phrase'])) {
    $stmt = $pdo->prepare("DELETE FROM training_phrases WHERE id = ?");
    $stmt->execute([$_GET['delete_phrase']]);
    header("Location: chatbot_manager.php"); exit;
}
if (isset($_GET['delete_response'])) {
    $stmt = $pdo->prepare("DELETE FROM responses WHERE id = ?");
    $stmt->execute([$_GET['delete_response']]);
    header("Location: chatbot_manager.php"); exit;
}
if (isset($_GET['delete_intent'])) {
    $stmt = $pdo->prepare("DELETE FROM intents WHERE id = ?");
    $stmt->execute([$_GET['delete_intent']]);
    header("Location: chatbot_manager.php"); exit;
}


// === LẤY DỮ LIỆU ĐỂ HIỂN THỊ ===
$intentsStmt = $pdo->query("SELECT * FROM intents ORDER BY name");
$intents = $intentsStmt->fetchAll();

$phrasesByIntent = [];
if (!empty($intents)) {
    $phrasesStmt = $pdo->query("SELECT * FROM training_phrases");
    foreach ($phrasesStmt->fetchAll() as $phrase) {
        $phrasesByIntent[$phrase['intent_id']][] = $phrase;
    }
}


$responsesByIntent = [];
if (!empty($intents)) {
    $responsesStmt = $pdo->query("SELECT * FROM responses");
    foreach ($responsesStmt->fetchAll() as $response) {
        $responsesByIntent[$response['intent_id']][] = $response;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Chatbot AI</title>
    <!-- ================================================================ -->
    <!-- ======================= CSS ĐÃ ĐƯỢC LÀM MỚI ====================== -->
    <!-- ================================================================ -->
    <style>
        :root {
            --primary-color: #007bff;
            --danger-color: #dc3545;
            --success-color: #28a745;
            --light-gray: #f8f9fa;
            --border-color: #dee2e6;
            --text-color: #212529;
            --text-secondary: #6c757d;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            background-color: #f4f7f9;
            color: var(--text-color);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
        }

        h1 {
            text-align: center;
            color: #343a40;
            margin-bottom: 30px;
        }

        .intent-block {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 25px;
            padding: 20px;
            background: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: box-shadow 0.3s ease;
        }
        .intent-block:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
        }

        .intent-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        
        .intent-header h2 {
            margin: 0;
            color: var(--primary-color);
            font-size: 1.5em;
        }

        h3 {
            font-size: 1.1em;
            color: var(--text-secondary);
            margin-top: 20px;
            margin-bottom: 10px;
        }

        ul {
            list-style: none;
            padding-left: 0;
        }
        
        li {
            background-color: var(--light-gray);
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid var(--border-color);
        }

        .delete-btn {
            color: var(--danger-color);
            text-decoration: none;
            font-size: 0.9em;
            border: 1px solid var(--danger-color);
            padding: 3px 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        .delete-btn:hover {
            background: var(--danger-color);
            color: white;
        }

        /* --- Cải tiến quan trọng nhất cho FORM --- */
        form {
            margin-top: 15px;
        }

        .form-group {
            display: flex;
            gap: 10px; /* Khoảng cách giữa ô input và nút bấm */
        }

        .form-group input[type="text"],
        .form-group textarea {
            flex-grow: 1; /* Cho phép ô input co giãn chiếm hết không gian */
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 1rem;
        }
        .form-group textarea {
            resize: vertical;
        }

        button {
            padding: 10px 20px;
            cursor: pointer;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            transition: background-color 0.2s ease;
        }
        button:hover {
            background-color: #0056b3;
        }

        .retrain-btn {
            background-color: var(--success-color);
            font-size: 1.2em;
            padding: 15px;
            margin-bottom: 25px;
            width: 100%;
            border-radius: 8px;
        }
        .retrain-btn:hover {
            background-color: #218838;
        }
        
        #retrain-status {
            font-weight: bold;
            text-align: center;
            margin-top: -15px;
            margin-bottom: 25px;
            font-size: 1.1em;
            height: 20px;
        }

        .add-intent-block {
            background-color: #e9ecef;
            text-align: center;
        }
        .add-intent-block h2 {
            color: #495057;
            font-size: 1.3em;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Quản lý Cơ sở Tri thức Chatbot</h1>

        <button id="retrain-button" class="retrain-btn">🚀 Huấn luyện lại AI</button>
        <p id="retrain-status"></p>

        <div class="intent-block add-intent-block">
            <h2>Thêm Intent Mới</h2>
            <form method="POST">
                <div class="form-group">
                    <input type="text" name="intent_name" placeholder="Ví dụ: #TRA_GOP, #KHUYEN_MAI,..." required>
                    <button type="submit" name="add_intent">Thêm Intent</button>
                </div>
            </form>
        </div>

        <?php if (empty($intents)): ?>
            <p style="text-align: center;">Chưa có dữ liệu. Hãy thêm Intent đầu tiên!</p>
        <?php else: ?>
            <?php foreach ($intents as $intent): ?>
                <div class="intent-block">
                    <div class="intent-header">
                        <h2>Intent: <?= htmlspecialchars($intent['name']) ?></h2>
                        <a href="?delete_intent=<?= $intent['id'] ?>" class="delete-btn" onclick="return confirm('CẢNH BÁO: Xóa intent này sẽ xóa toàn bộ câu mẫu và câu trả lời bên trong. Bạn có chắc không?')">Xóa Intent</a>
                    </div>
                    
                    <!-- Phần Training Phrases -->
                    <div>
                        <h3>Câu mẫu (Training Phrases)</h3>
                        <ul>
                            <?php if (!empty($phrasesByIntent[$intent['id']])): ?>
                                <?php foreach ($phrasesByIntent[$intent['id']] as $phrase): ?>
                                    <li>
                                        <span><?= htmlspecialchars($phrase['phrase_text']) ?></span>
                                        <a href="?delete_phrase=<?= $phrase['id'] ?>" class="delete-btn">Xóa</a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li style="color: #888; font-style: italic;">Chưa có câu mẫu nào.</li>
                            <?php endif; ?>
                        </ul>
                        <form method="POST">
                            <input type="hidden" name="intent_id" value="<?= $intent['id'] ?>">
                            <!-- Đã thêm class 'form-group' để CSS hoạt động -->
                            <div class="form-group">
                                <input type="text" name="phrase_text" placeholder="Thêm câu mẫu mới (VD: Mua trả góp được không)" required>
                                <button type="submit" name="add_phrase">Thêm</button>
                            </div>
                        </form>
                    </div>

                    <!-- Phần Responses -->
                    <div style="margin-top: 30px;">
                        <h3>Câu trả lời của Bot (Responses)</h3>
                        <ul>
                             <?php if (!empty($responsesByIntent[$intent['id']])): ?>
                                <?php foreach ($responsesByIntent[$intent['id']] as $response): ?>
                                    <li>
                                        <span><?= htmlspecialchars($response['response_text']) ?></span>
                                        <a href="?delete_response=<?= $response['id'] ?>" class="delete-btn">Xóa</a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                 <li style="color: #888; font-style: italic;">Chưa có câu trả lời nào.</li>
                            <?php endif; ?>
                        </ul>
                        <form method="POST">
                            <input type="hidden" name="intent_id" value="<?= $intent['id'] ?>">
                            <!-- Đã thêm class 'form-group' để CSS hoạt động -->
                            <div class="form-group">
                                <textarea name="response_text" placeholder="Thêm câu trả lời mới" required rows="2"></textarea>
                                <button type="submit" name="add_response">Thêm</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        // Phần Javascript giữ nguyên
        document.getElementById('retrain-button').addEventListener('click', function() {
            const statusEl = document.getElementById('retrain-status');
            statusEl.textContent = '⏳ Đang gửi yêu cầu huấn luyện đến AI service...';
            statusEl.style.color = '#d39e00';
            
            fetch('http://127.0.0.1:5000/retrain', { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        statusEl.textContent = '✅ Huấn luyện thành công! Chatbot đã được cập nhật.';
                        statusEl.style.color = 'green';
                        setTimeout(() => { statusEl.textContent = ''; }, 4000);
                    } else {
                        statusEl.textContent = '❌ Huấn luyện thất bại: ' + data.message;
                        statusEl.style.color = 'red';
                    }
                })
                .catch(error => {
                    statusEl.textContent = '❌ Lỗi: Không kết nối được với Python AI (Port 5000). Hãy kiểm tra xem file api.py có đang chạy không.';
                    statusEl.style.color = 'red';
                    console.error('Error:', error);
                });
        });
    </script>
</body>
</html>
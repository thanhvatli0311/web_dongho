<?php
// Bắt đầu file admin/ajax_teach_bot.php
session_start();
require __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

// Kiểm tra quyền và dữ liệu đầu vào
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$question = $data['question'] ?? null;
$answer = $data['answer'] ?? null;
$intentId = $data['intentId'] ?? null;
$newIntent = $data['newIntent'] ?? null;

if (!$question || !$answer || (!$intentId && !$newIntent)) {
    echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Nếu admin tạo intent mới
    if (!empty($newIntent)) {
        // Kiểm tra xem intent đã tồn tại chưa để tránh lỗi
        $stmt = $pdo->prepare("SELECT id FROM intents WHERE name = ?");
        $stmt->execute([$newIntent]);
        $existingId = $stmt->fetchColumn();

        if ($existingId) {
            $intentId = $existingId;
        } else {
            $stmt = $pdo->prepare("INSERT INTO intents (name) VALUES (?)");
            $stmt->execute([$newIntent]);
            $intentId = $pdo->lastInsertId();
        }
    }

    // Thêm câu hỏi vào training_phrases
    $stmt = $pdo->prepare("INSERT INTO training_phrases (intent_id, phrase_text) VALUES (?, ?)");
    $stmt->execute([$intentId, $question]);

    // Thêm câu trả lời vào responses
    $stmt = $pdo->prepare("INSERT INTO responses (intent_id, response_text) VALUES (?, ?)");
    $stmt->execute([$intentId, $answer]);

    $pdo->commit();
    echo json_encode(['status' => 'success']);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
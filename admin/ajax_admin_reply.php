<?php
session_start();
require __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') exit;

$data = json_decode(file_get_contents('php://input'), true);
$cid = $data['conversation_id'] ?? 0;
$msg = $data['message'] ?? '';

if ($cid && $msg) {
    try {
        // SỬA: Insert vào 'sender' và 'message_text'
        $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender, message_text) VALUES (?, 'admin', ?)");
        $stmt->execute([$cid, $msg]);

        // Cập nhật thời gian
        $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?")->execute([$cid]);

        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
}
?>
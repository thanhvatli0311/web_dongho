<?php
session_start();
require __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin' || !isset($_GET['id'])) {
    echo json_encode([]);
    exit;
}

$conversation_id = (int)$_GET['id'];

// Đánh dấu cuộc trò chuyện là "in_progress" khi admin xem lần đầu
$stmt_check = $pdo->prepare("SELECT status FROM conversations WHERE id = ?");
$stmt_check->execute([$conversation_id]);
if ($stmt_check->fetchColumn() === 'human_requested') {
    $stmt_update = $pdo->prepare("UPDATE conversations SET status = 'in_progress' WHERE id = ?");
    $stmt_update->execute([$conversation_id]);
}

// Lấy tất cả tin nhắn
$stmt = $pdo->prepare("SELECT sender, message_text FROM messages WHERE conversation_id = ? ORDER BY created_at ASC");
$stmt->execute([$conversation_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($messages);
<?php
session_start();
require __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin' || !isset($_GET['id'])) {
    echo json_encode([]); exit;
}

$id = (int)$_GET['id'];

// Chuyển trạng thái sang 'in_progress' để Bot ngừng trả lời
$stmt = $pdo->prepare("UPDATE conversations SET status = 'in_progress' WHERE id = ? AND status = 'human_requested'");
$stmt->execute([$id]);

// Lấy tin nhắn (SỬA: sender, message_text)
$stmt = $pdo->prepare("SELECT sender, message_text, created_at FROM messages WHERE conversation_id = ? ORDER BY created_at ASC");
$stmt->execute([$id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($messages);
?>
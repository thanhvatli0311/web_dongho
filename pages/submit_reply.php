<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include '../includes/db.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Phương thức không hợp lệ.';
    echo json_encode($response);
    exit;
}

if (!isset($_SESSION['username'])) {
    $response['message'] = 'Bạn cần đăng nhập để phản hồi.';
    echo json_encode($response);
    exit;
}

$username = $_SESSION['username'];
$review_id = intval($_POST['review_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

if ($review_id <= 0 || $content === '') {
    $response['message'] = 'Thiếu thông tin phản hồi.';
    echo json_encode($response);
    exit;
}

// Kiểm tra xem review_id có tồn tại
$check = $conn->prepare("SELECT id FROM tbreview WHERE id = ?");
$check->bind_param("i", $review_id);
$check->execute();
$result_check = $check->get_result();
if ($result_check->num_rows === 0) {
    $response['message'] = 'Đánh giá không tồn tại.';
    echo json_encode($response);
    exit;
}

$stmt = $conn->prepare("INSERT INTO tbreview_reply (review_id, username, content) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $review_id, $username, $content);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Phản hồi đã được gửi.';
} else {
    $response['message'] = 'Lỗi khi lưu phản hồi: ' . $stmt->error;
}

echo json_encode($response);
$conn->close();
?>

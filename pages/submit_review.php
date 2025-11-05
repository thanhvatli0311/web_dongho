<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

include '../includes/db.php'; // Đường dẫn tới file kết nối DB (mysqli)

// Format response
$response = ['success' => false, 'message' => ''];

// 1) Kiểm tra phương thức
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Phương thức truy cập không hợp lệ.';
    echo json_encode($response);
    exit;
}

// 2) Kiểm tra đăng nhập
if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    $response['message'] = 'Bạn cần đăng nhập để thực hiện đánh giá.';
    echo json_encode($response);
    exit;
}

$username = $_SESSION['username'];
$mahang = trim($_POST['mathang_id'] ?? '');
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

// 3) Validate input
$min_comment_length = 10;
$max_comment_length = 500;

if ($mahang === '' || $rating < 1 || $rating > 5 || $comment === '') {
    $response['message'] = 'Vui lòng cung cấp đầy đủ Mã hàng, số sao (1-5) và bình luận.';
    echo json_encode($response);
    exit;
}

$comment_length = mb_strlen($comment, 'UTF-8');
if ($comment_length < $min_comment_length || $comment_length > $max_comment_length) {
    $response['message'] = "Bình luận phải dài từ {$min_comment_length} đến {$max_comment_length} ký tự.";
    echo json_encode($response);
    exit;
}

// 4) Kiểm tra quyền đánh giá: user đã mua và đơn hàng có trạng thái 'Đã giao'?
$purchase_sql = "
    SELECT COUNT(*) AS cnt
    FROM tbdonhang dh
    JOIN tbchitietdonhang ctdh ON dh.madonhang = ctdh.madonhang
    JOIN tbkhachhang kh ON dh.makhach = kh.makhach
    WHERE kh.username = ? AND ctdh.mahang = ? AND dh.tinhtrang = 'Đã giao'
";
if ($stmt = $conn->prepare($purchase_sql)) {
    $stmt->bind_param('ss', $username, $mahang);
    $stmt->execute();
    $stmt->bind_result($cnt);
    $stmt->fetch();
    $stmt->close();

    if ((int)$cnt === 0) {
        $response['message'] = 'Bạn không có quyền đánh giá sản phẩm này vì bạn chưa mua (hoặc đơn hàng chưa ở trạng thái "Đã giao").';
        echo json_encode($response);
        exit;
    }
} else {
    $response['message'] = 'Lỗi hệ thống khi kiểm tra quyền đánh giá.';
    echo json_encode($response);
    exit;
}

// 5) Kiểm tra xem user đã đánh giá sản phẩm này trước đó chưa
$existing_id = null;
$check_sql = "SELECT id FROM tbreview WHERE username = ? AND mahang = ? LIMIT 1";
if ($stmt = $conn->prepare($check_sql)) {
    $stmt->bind_param('ss', $username, $mahang);
    $stmt->execute();
    $stmt->bind_result($existing_id);
    $stmt->fetch();
    $stmt->close();
} else {
    $response['message'] = 'Lỗi hệ thống khi kiểm tra đánh giá cũ.';
    echo json_encode($response);
    exit;
}

// 6) Insert hoặc Update vào bảng tbreview
if ($existing_id) {
    // Cập nhật đánh giá cũ
    $update_sql = "UPDATE tbreview SET rating = ?, content = ?, created_at = NOW() WHERE id = ?";
    if ($stmt = $conn->prepare($update_sql)) {
        $stmt->bind_param('isi', $rating, $comment, $existing_id);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Cập nhật đánh giá thành công.';
        } else {
            $response['message'] = 'Lỗi khi cập nhật đánh giá: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = 'Lỗi hệ thống khi chuẩn bị câu lệnh cập nhật.';
    }
} else {
    // Thêm đánh giá mới
    $insert_sql = "INSERT INTO tbreview (mahang, username, rating, content, created_at) VALUES (?, ?, ?, ?, NOW())";
    if ($stmt = $conn->prepare($insert_sql)) {
        $stmt->bind_param('ssis', $mahang, $username, $rating, $comment);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Đánh giá của bạn đã được gửi thành công.';
        } else {
            $response['message'] = 'Lỗi khi lưu đánh giá: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = 'Lỗi hệ thống khi chuẩn bị câu lệnh lưu đánh giá.';
    }
}

$conn->close();
echo json_encode($response);
?>
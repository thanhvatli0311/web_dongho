<?php
session_start();
header('Content-Type: application/json');

include '../includes/db.php'; // Đường dẫn đến file kết nối CSDL

$response = ['success' => false, 'message' => ''];

// 1. Kiểm tra phương thức
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Phương thức truy cập không hợp lệ.';
    echo json_encode($response);
    exit;
}

// Lấy user_id từ SESSION thay vì POST để đảm bảo an toàn (NGUYÊN TẮC: Không tin tưởng user_id từ POST)
if (!isset($_SESSION['username'])) {
    $response['message'] = 'Bạn cần đăng nhập để thực hiện đánh giá.';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['username'];
$mahang = $_POST['mathang_id'] ?? '';
$rating = $_POST['rating'] ?? 0;
// Sử dụng trim() để loại bỏ khoảng trắng dư thừa
$comment = trim($_POST['comment'] ?? '');

$rating = (int)$rating;

// 3. Kiểm tra tính hợp lệ của dữ liệu
$min_comment_length = 10;
$max_comment_length = 500;

if (empty($mahang) || $rating < 1 || $rating > 5 || empty($comment)) {
    $response['message'] = 'Vui lòng cung cấp đầy đủ Mã hàng, số sao (1-5) và bình luận.';
    echo json_encode($response);
    exit;
}
if (mb_strlen($comment, 'UTF-8') < $min_comment_length || mb_strlen($comment, 'UTF-8') > $max_comment_length) {
    $response['message'] = "Bình luận phải dài từ {$min_comment_length} đến {$max_comment_length} ký tự.";
    echo json_encode($response);
    exit;
}


// --- 4. Kiểm tra Quyền Đánh giá Lần cuối (Bảo vệ phía Server) ---

// Kiểm tra: Đã mua (Đã giao)
$sql_check_purchase = "SELECT COUNT(*) FROM tbdonhang dh JOIN tbchitietdonhang ctdh ON dh.madonhang = ctdh.madonhang WHERE dh.makhach = ? AND ctdh.mahang = ? AND dh.tinhtrang = 'Đã giao'";
$stmt_check_purchase = $conn->prepare($sql_check_purchase);
$stmt_check_purchase->bind_param("ss", $user_id, $mahang); // Giả định makhach = user_id (username)
$stmt_check_purchase->execute();
$purchase_exist_count = $stmt_check_purchase->get_result()->fetch_row()[0];
$stmt_check_purchase->close();

if ($purchase_exist_count == 0) {
    $response['message'] = 'Bạn không có quyền đánh giá sản phẩm này vì bạn chưa mua (hoặc đơn hàng chưa ở trạng thái "Đã giao").';
    echo json_encode($response);
    exit;
}

// Kiểm tra: Đã gửi đánh giá (approved hoặc pending)
$sql_check_review_exist = "SELECT COUNT(*) FROM reviews WHERE user_id = ? AND mathang_id = ? AND (status = 'approved' OR status = 'pending')";
$stmt_check_review_exist = $conn->prepare($sql_check_review_exist);
$stmt_check_review_exist->bind_param("ss", $user_id, $mahang);
$stmt_check_review_exist->execute();
$review_exist_count = $stmt_check_review_exist->get_result()->fetch_row()[0];
$stmt_check_review_exist->close();

if ($review_exist_count > 0) {
    $response['message'] = 'Bạn đã gửi đánh giá cho sản phẩm này rồi.';
    echo json_encode($response);
    exit;
}

// 5. Thêm đánh giá vào CSDL với trạng thái 'Đã giao'
// LƯU Ý QUAN TRỌNG: Đã sửa tên cột từ date_created thành created_at để khớp với lược đồ CSDL
$sql_insert = "INSERT INTO reviews (mathang_id, user_id, rating, comment, status, created_at) VALUES (?, ?, ?, ?, 'Đã giao', NOW())";
$stmt_insert = $conn->prepare($sql_insert);
// ssist (string, string, integer, string, time/date) -> NOW() được thêm trực tiếp vào SQL
$stmt_insert->bind_param("ssis", $mahang, $user_id, $rating, $comment); 

if ($stmt_insert->execute()) {
    $response['success'] = true;
    $response['message'] = 'Đánh giá của bạn đã được gửi thành công và đang chờ quản trị viên duyệt.';
} else {
    $response['message'] = 'Lỗi hệ thống khi lưu đánh giá: ' . $stmt_insert->error;
}

$stmt_insert->close();
$conn->close();

echo json_encode($response);
?>

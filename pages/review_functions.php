<?php
/**
 * File: review_functions.php
 * Chứa các hàm logic độc lập liên quan đến chức năng đánh giá sản phẩm.
 * Các hàm này giả lập việc kết nối CSDL để có thể kiểm thử tĩnh/độc lập.
 */

// Định nghĩa các hằng số trạng thái đơn hàng và đánh giá
define('ORDER_STATUS_DELIVERED', 'Đã giao');
define('REVIEW_STATUS_PENDING', 'pending');
define('REVIEW_STATUS_APPROVED', 'approved');

/**
 * Hàm kiểm tra quyền đánh giá của người dùng cho một mặt hàng cụ thể.
 * Logic: Đã mua (trạng thái 'Đã giao') VÀ chưa có đánh giá (pending/approved).
 *
 * @param string $user_id ID người dùng (username).
 * @param string $mahang Mã mặt hàng.
 * @param mysqli|null $conn Đối tượng kết nối CSDL (nếu muốn dùng CSDL thật).
 * @return array Kết quả kiểm tra: ['can_review' => bool, 'reason' => string]
 */
function check_can_review($user_id, $mahang, $conn = null) {
    // 1. Kiểm tra đăng nhập
    if (empty($user_id)) {
        return ['can_review' => false, 'reason' => 'user_not_logged_in'];
    }

    // --- Giả lập kết nối CSDL nếu không có $conn (Kiểm thử độc lập) ---
    // Trong môi trường production, bạn sẽ dùng $conn thật.
    if ($conn === null) {
        return simulate_can_review_check($user_id, $mahang);
    }
    // --------------------------------------------------------------------


    // 2. Kiểm tra đã mua (trạng thái Đã giao)
    $sql_check_purchase = "SELECT COUNT(*) FROM tbdonhang dh JOIN tbchitietdonhang ctdh ON dh.madonhang = ctdh.madonhang WHERE dh.makhach = ? AND ctdh.mahang = ? AND dh.tinhtrang = ?";
    $stmt_check_purchase = $conn->prepare($sql_check_purchase);
    
    if (!$stmt_check_purchase) {
        error_log("SQL Prepare Error (Purchase Check): " . $conn->error);
        return ['can_review' => false, 'reason' => 'db_error'];
    }
    
    $status_delivered = ORDER_STATUS_DELIVERED;
    $stmt_check_purchase->bind_param("sss", $user_id, $mahang, $status_delivered);
    $stmt_check_purchase->execute();
    $result_purchase = $stmt_check_purchase->get_result()->fetch_row();
    $purchase_exist_count = $result_purchase ? $result_purchase[0] : 0;
    $stmt_check_purchase->close();

    if ($purchase_exist_count == 0) {
        return ['can_review' => false, 'reason' => 'not_purchased_or_not_delivered'];
    }

    // 3. Kiểm tra đã đánh giá (pending/approved)
    $sql_check_review_exist = "SELECT COUNT(*) FROM reviews WHERE user_id = ? AND mathang_id = ? AND (status = ? OR status = ?)";
    $stmt_check_review_exist = $conn->prepare($sql_check_review_exist);

    if (!$stmt_check_review_exist) {
        error_log("SQL Prepare Error (Review Exist Check): " . $conn->error);
        return ['can_review' => false, 'reason' => 'db_error'];
    }

    $status_pending = REVIEW_STATUS_PENDING;
    $status_approved = REVIEW_STATUS_APPROVED;
    $stmt_check_review_exist->bind_param("ssss", $user_id, $mahang, $status_pending, $status_approved);
    $stmt_check_review_exist->execute();
    $result_review = $stmt_check_review_exist->get_result()->fetch_row();
    $review_exist_count = $result_review ? $result_review[0] : 0;
    $stmt_check_review_exist->close();

    if ($review_exist_count > 0) {
        return ['can_review' => false, 'reason' => 'review_already_submitted'];
    }

    return ['can_review' => true, 'reason' => 'can_review'];
}

/**
 * Hàm GIẢ LẬP kết quả kiểm tra quyền đánh giá cho mục đích kiểm thử độc lập (Static Testing).
 * Dữ liệu giả định có thể được điều chỉnh để kiểm tra các trường hợp biên.
 */
function simulate_can_review_check($user_id, $mahang) {
    // Giả định 1: Người dùng A đã mua sản phẩm X và chưa đánh giá
    if ($user_id === 'user_A' && $mahang === 'SP001') {
        return ['can_review' => true, 'reason' => 'can_review'];
    }
    // Giả định 2: Người dùng B đã mua sản phẩm X và đã đánh giá (approved)
    if ($user_id === 'user_B' && $mahang === 'SP001') {
        return ['can_review' => false, 'reason' => 'review_already_submitted'];
    }
    // Giả định 3: Người dùng C đã mua sản phẩm X nhưng đơn hàng đang 'Chờ duyệt'
    if ($user_id === 'user_C' && $mahang === 'SP001') {
        return ['can_review' => false, 'reason' => 'not_purchased_or_not_delivered'];
    }
    // Giả định 4: Người dùng A mua sản phẩm Y (không tồn tại)
    if ($user_id === 'user_A' && $mahang === 'SP999') {
        return ['can_review' => false, 'reason' => 'not_purchased_or_not_delivered'];
    }
    // Mặc định
    return ['can_review' => false, 'reason' => 'unknown_case'];
}

/**
 * Hàm lấy các đánh giá ĐÃ DUYỆT cho một mặt hàng.
 *
 * @param string $mahang Mã mặt hàng.
 * @param mysqli|null $conn Đối tượng kết nối CSDL (nếu muốn dùng CSDL thật).
 * @return array Danh sách các đánh giá.
 */
function get_approved_reviews($mahang, $conn = null) {
    // --- Giả lập kết nối CSDL (Kiểm thử độc lập) ---
    if ($conn === null) {
        return simulate_get_approved_reviews($mahang);
    }
    // --------------------------------------------------------------------

    $reviews = [];
    $sql_reviews = "SELECT r.*, u.username FROM reviews r JOIN tbuser u ON r.user_id = u.username WHERE r.mathang_id = ? AND r.status = ? ORDER BY r.created_at DESC";
    $stmt_reviews = $conn->prepare($sql_reviews);
    
    if (!$stmt_reviews) {
        error_log("SQL Prepare Error (Get Reviews): " . $conn->error);
        return [];
    }

    $status_approved = REVIEW_STATUS_APPROVED;
    $stmt_reviews->bind_param("ss", $mahang, $status_approved);
    $stmt_reviews->execute();
    $reviews = $stmt_reviews->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_reviews->close();

    return $reviews;
}

/**
 * Hàm GIẢ LẬP dữ liệu đánh giá đã duyệt cho mục đích kiểm thử độc lập.
 */
function simulate_get_approved_reviews($mahang) {
    if ($mahang === 'SP001') {
        return [
            [
                'username' => 'user_B',
                'rating' => 5,
                'comment' => 'Sản phẩm tuyệt vời, giao hàng nhanh chóng!',
                'created_at' => '2025-10-30 10:00:00'
            ],
            [
                'username' => 'admin_user',
                'rating' => 4,
                'comment' => 'Chất lượng tốt, nhưng giá hơi cao.',
                'created_at' => '2025-10-25 15:30:00'
            ]
        ];
    }
    return [];
}
?>

<?php
/*
 * TỆP TRANG SẢN PHẨM (product_page.php)
 * Nơi gọi hàm và hiển thị ra cho người dùng.
 */

// --- BƯỚC 1: NẠP (INCLUDE) CÁC TỆP CẦN THIẾT ---
// Nạp tệp kết nối DB (sẽ tạo ra biến $pdo)
require_once 'db_connect.php';
// Nạp tệp chứa hàm (sẽ có hàm getApprovedReviewsByProductId)
require_once 'functions.php';

// Giả sử ID sản phẩm được lấy từ URL (ví dụ: product_page.php?id=1)
// (Cần kiểm tra và lọc dữ liệu đầu vào cẩn thận trong thực tế)
$current_product_id = 1; // Lấy ID của "Sản phẩm Mẫu 1"

// --- BƯỚC 2: GỌI HÀM (TRUY VẤN) ---
// Gọi hàm đã định nghĩa ở functions.php
// $pdo được lấy từ tệp db_connect.php
$reviews = getApprovedReviewsByProductId($pdo, $current_product_id);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết sản phẩm</title>
    <style>
        /* CSS đơn giản để demo */
        .review-list { margin-top: 20px; }
        .review-item { border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; background: #f9f9f9; }
        .review-item strong { color: #0056b3; }
        .review-item .rating { color: #f39c12; font-weight: bold; }
        .review-item .date { font-size: 0.9em; color: #777; }
    </style>
</head>
<body>

    <h1>Tên Sản Phẩm Mẫu 1</h1>
    <p>Đây là mô tả sản phẩm...</p>

    <div class="review-section">
        <h2>Đánh giá của khách hàng (<?php echo count($reviews); ?>)</h2>

        <div class="review-list">
            <?php
            // Kiểm tra xem có đánh giá nào không
            if (empty($reviews)) {
                // Nếu mảng $reviews rỗng
                echo "<p>Chưa có đánh giá nào cho sản phẩm này.</p>";
            } else {
                // Nếu có đánh giá, dùng vòng lặp (foreach) để duyệt qua từng đánh giá
                foreach ($reviews as $review) {
                    // $review là một mảng (ví dụ: ['username' => 'Nguyen Van A', 'rating' => 5, ...])

                    // QUAN TRỌNG: Sử dụng htmlspecialchars()
                    // để "thoát" (escape) dữ liệu do người dùng nhập (comment, username)
                    // Điều này giúp chống Lỗi XSS (Cross-Site Scripting).
                    $username = htmlspecialchars($review['username']);
                    $comment = htmlspecialchars($review['comment']);
                    $rating = (int)$review['rating']; // Ép kiểu về số nguyên
                    
                    // Định dạng lại ngày tháng (tùy chọn)
                    $date = date('d/m/Y H:i', strtotime($review['created_at']));
            ?>

                    <div class="review-item">
                        <p class="rating">
                            <?php echo $rating; ?>/5 Sao
                            </p>
                            <p><strong><?php echo $username; ?></strong></p>
                        <p><?php echo $comment; ?></p>
                        <p class="date">Ngày: <?php echo $date; ?></p>
                    </div>

            <?php
                } // Kết thúc vòng lặp foreach
            } // Kết thúc khối else
            ?>
        </div>
    </div>

</body>
</html>
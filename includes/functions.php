<?php
/*
 * TỆP CHỨC NĂNG (functions.php)
 * Nơi định nghĩa các hàm xử lý nghiệp vụ.
 */

/**
 * Khởi tạo hàm: getApprovedReviewsByProductId
 * Mục đích: Lấy tất cả các đánh giá đã được duyệt ('approved') cho một sản phẩm.
 *
 * @param PDO $pdo           Đối tượng kết nối PDO (từ db_connect.php).
 * @param int $productId     ID của sản phẩm cần lấy đánh giá.
 * @return array             Một mảng chứa các đánh giá.
 */
function getApprovedReviewsByProductId(PDO $pdo, int $tbmathangMahang): array
{
    // --- BƯỚC 1: VIẾT CÂU TRUY VẤN (SQL QUERY) ---
    // Chúng ta cần JOIN 2 bảng:
    // 1. `reviews` (để lấy rating, comment, ngày tạo)
    // 2. `users` (để lấy tên người dùng 'username')
    //
    // Điều kiện (WHERE):
    // 1. `r.product_id = ?`     (Đúng sản phẩm)
    // 2. `r.status = 'approved'` (Chỉ lấy đánh giá đã duyệt)
    //
    // Sắp xếp (ORDER BY):
    // `r.created_at DESC` (Các đánh giá mới nhất lên đầu)

    $sql = "
        SELECT
            r.rating,
            r.comment,
            r.created_at,
            u.username
        FROM
            reviews AS r
        JOIN
            users AS u ON r.user_id = u.username
        WHERE
            r.mathang_id = ? AND r.status = 'approved'
        ORDER BY
            r.created_at DESC
    ";

    try {
        // --- BƯỚC 2: CHUẨN BỊ VÀ THỰC THI (Prepared Statements) ---
        // Đây là bước CỰC KỲ QUAN TRỌNG để chống Lỗi SQL Injection.
        // Tuyệt đối không bao giờ chèn biến $productId trực tiếp vào chuỗi $sql.

        // Chuẩn bị câu lệnh
        $stmt = $pdo->prepare($sql);

        // Gán giá trị $productId vào dấu chấm hỏi (?) và thực thi
        $stmt->execute([$tbmathangMahang]);

        // --- BƯỚC 3: TRẢ VỀ KẾT QUẢ ---
        // Lấy tất cả các dòng kết quả dưới dạng mảng
        return $stmt->fetchAll();

    } catch (\PDOException $e) {
        // Xử lý nếu có lỗi truy vấn
        // Trong thực tế, bạn nên log lỗi này thay vì in ra màn hình
        error_log('Lỗi truy vấn: ' . $e->getMessage());
        return []; // Trả về mảng rỗng nếu có lỗi
    }
}
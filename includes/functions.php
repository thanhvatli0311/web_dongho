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


// Hàm theo dõi đơn hàng từ GHTK


/**
 * Ánh xạ trạng thái chi tiết của GHTK sang trạng thái tổng quát của hệ thống.
 * * @param string $ghtk_label Nhãn trạng thái nhận được từ API GHTK.
 * @return string Trạng thái tổng quát của hệ thống.
 */
function mapGHTKStatusToSystem(string $ghtk_label): string {
    // Chuyển nhãn GHTK về chữ thường để đảm bảo việc so sánh không phân biệt hoa thường
    $normalized_label = mb_strtolower($ghtk_label, 'UTF-8'); 

    // Mảng quy tắc ánh xạ: 
    // Key là từ khóa GHTK, Value là trạng thái hệ thống tương ứng
    $map_rules = [
        // Quy tắc 1: Đã giao/Hoàn thành
        'đã giao' => 'Đã giao',
        'hoàn thành' => 'Đã giao',
        
        // Quy tắc 2: Hủy
        'hủy' => 'Đã hủy',
        
        // Quy tắc 3: Đang vận chuyển / Đang giao
        'đang vận chuyển' => 'Đang giao hàng',
        'đang giao' => 'Đang giao hàng', // Bao gồm "Đang giao hàng"
        
        // Quy tắc 4: Đang xử lý / Đang lấy hàng
        'đang lấy hàng' => 'Đang xử lý',
        'đã tiếp nhận' => 'Đang xử lý',
        'đang xử lý' => 'Đang xử lý',
        'đã lấy hàng' => 'Đang xử lý',
    ];

    // Lặp qua các quy tắc để tìm trạng thái phù hợp
    foreach ($map_rules as $ghtk_keyword => $system_status) {
        // Sử dụng strpos để kiểm tra xem từ khóa có tồn tại trong nhãn GHTK hay không.
        // Điều này giúp xử lý các nhãn dài như "Đã giao hàng thành công" hoặc "Đơn hàng đang vận chuyển đến Hà Nội".
        if (strpos($normalized_label, $ghtk_keyword) !== false) {
            return $system_status;
        }
    }

    // Trạng thái mặc định nếu không tìm thấy bất kỳ từ khóa nào
    return 'Đang xử lý'; 
}

// áp dụng khuyến mãi

/**
 * Tính toán giá cuối cùng sau khi áp dụng khuyến mãi
 *
 * @param PDO $pdo Đối tượng kết nối PDO
 * @param string|null $makhuyenmai Mã khuyến mãi cần áp dụng
 * @param float $tonggiatridonhang Tổng giá trị gốc của đơn hàng (chưa giảm, chỉ tính sản phẩm được chọn)
 * @return array Mảng chứa 'tonggiatrigiam', 'giacuoicung' và 'thongbao'
 */
function tinhGiaSauKhuyenMai(PDO $pdo, ?string $makhuyenmai, float $tonggiatridonhang): array
{
    // Nếu không có mã khuyến mãi, trả về giá gốc
    if (empty($makhuyenmai)) {
        return [
            'tonggiatrigiam' => 0.00,
            'giacuoicung' => $tonggiatridonhang,
            'thongbao' => null
        ];
    }

    // Đảm bảo tổng giá trị đơn hàng không âm
    if ($tonggiatridonhang <= 0) {
         return [
            'tonggiatrigiam' => 0.00,
            'giacuoicung' => $tonggiatridonhang,
            'thongbao' => 'Tổng giá trị đơn hàng phải lớn hơn 0 để áp dụng khuyến mãi.'
        ];
    }

    try {
        // 1. Lấy thông tin khuyến mãi
        $stmt = $pdo->prepare("SELECT loai, giatri, dieukientoithieu, ngaybatdau, ngayketthuc, trangthai
                               FROM tbkhuyenmai
                               WHERE makhuyenmai = :makhuyenmai");
        $stmt->execute(['makhuyenmai' => $makhuyenmai]);
        $khuyenmai = $stmt->fetch();

        $now = new DateTime();

        // 2. Kiểm tra tính hợp lệ của khuyến mãi
        if (!$khuyenmai || !(bool)$khuyenmai['trangthai']) {
            return [
                'tonggiatrigiam' => 0.00,
                'giacuoicung' => $tonggiatridonhang,
                'thongbao' => 'Mã khuyến mãi không tồn tại hoặc đã bị vô hiệu hóa.'
            ];
        }

        $ngaybatdau = new DateTime($khuyenmai['ngaybatdau']);
        $ngayketthuc = new DateTime($khuyenmai['ngayketthuc']);
        $dieukientoithieu = (float)$khuyenmai['dieukientoithieu'];

        if ($now < $ngaybatdau) {
            return [
                'tonggiatrigiam' => 0.00,
                'giacuoicung' => $tonggiatridonhang,
                'thongbao' => 'Mã khuyến mãi chưa đến ngày áp dụng.'
            ];
        }
        
        if ($now > $ngayketthuc) {
            return [
                'tonggiatrigiam' => 0.00,
                'giacuoicung' => $tonggiatridonhang,
                'thongbao' => 'Mã khuyến mãi đã hết hạn.'
            ];
        }
        
        if ($tonggiatridonhang < $dieukientoithieu) {
            return [
                'tonggiatrigiam' => 0.00,
                'giacuoicung' => $tonggiatridonhang,
                'thongbao' => 'Đơn hàng chưa đủ điều kiện tối thiểu là ' . number_format($dieukientoithieu, 0) . ' VND.'
            ];
        }

        // 3. Tính giá trị giảm
        $tonggiatrigiam = 0.00;
        $giatri_km = (float)$khuyenmai['giatri'];

        if ($khuyenmai['loai'] === 'PHAN_TRAM') {
            // Giảm theo phần trăm
            $phantram = min($giatri_km, 100);
            $tonggiatrigiam = $tonggiatridonhang * ($phantram / 100);
        } elseif ($khuyenmai['loai'] === 'TIEN_MAT') {
            // Giảm theo số tiền (tối đa bằng giá trị đơn hàng)
            $tonggiatrigiam = min($giatri_km, $tonggiatridonhang);
        }

        // 4. Tính giá cuối cùng
        $giacuoicung = $tonggiatridonhang - $tonggiatrigiam;
        
        // Thông báo áp dụng thành công (chỉ khi mọi thứ hợp lệ)
        $thongbao = "Áp dụng mã **" . htmlspecialchars($makhuyenmai) . "** thành công! Bạn được giảm " . number_format($tonggiatrigiam, 0) . " VND.";

        return [
            'tonggiatrigiam' => round($tonggiatrigiam, 0),
            'giacuoicung' => round($giacuoicung, 0),
            'thongbao' => $thongbao
        ];

    } catch (\PDOException $e) {
        // Xử lý lỗi SQL nếu cần
        // Trong môi trường phát triển: error_log($e->getMessage());
        return [
            'tonggiatrigiam' => 0.00,
            'giacuoicung' => $tonggiatridonhang,
            'thongbao' => 'Lỗi xử lý cơ sở dữ liệu khi áp dụng khuyến mãi.'
        ];
    }
}
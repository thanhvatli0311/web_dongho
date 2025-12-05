<?php
// admin/bao_cao.php

/**
 * Hàm lấy dữ liệu biểu đồ (Giữ nguyên cho Dashboard cũ)
 */
function getRevenueChartData(PDO $pdo, string $startDate, string $endDate): array {
    $start = date('Y-m-d', strtotime($startDate));
    $end = date('Y-m-d', strtotime($endDate));

    $sql = "SELECT DATE(ngaymua) AS order_date, SUM(tongtiendonhang) AS daily_revenue
            FROM tbdonhang
            WHERE tinhtrang = 'Đã giao' 
            AND DATE(ngaymua) BETWEEN :start AND :end
            GROUP BY order_date
            ORDER BY order_date ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':start' => $start, ':end' => $end]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $map = array_column($rows, 'daily_revenue', 'order_date');
    $labels = []; $data = []; $total = 0.0;
    
    $current = new DateTime($start);
    $endDt = new DateTime($end);
    $endDt->modify('+1 day');

    while ($current < $endDt) {
        $dateStr = $current->format('Y-m-d');
        $labels[] = $current->format('d/m');
        $val = (float)($map[$dateStr] ?? 0);
        $data[] = $val;
        $total += $val;
        $current->modify('+1 day');
    }
    return ['labels' => $labels, 'data' => $data, 'total' => $total];
}

/**
 * Hàm lấy thống kê trạng thái (Giữ nguyên)
 */
function getOrderStatusStats(PDO $pdo): array {
    $stmt = $pdo->query("SELECT tinhtrang, COUNT(*) AS total_count FROM tbdonhang GROUP BY tinhtrang");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Hàm lấy Top sản phẩm (Cập nhật theo schema mới)
 */
function getTopProducts(PDO $pdo, int $limit = 3, ?string $startDate = null, ?string $endDate = null): array {
    $whereDate = "";
    $params = [':limit' => $limit];
    if ($startDate && $endDate) {
        $whereDate = " AND DATE(dh.ngaymua) BETWEEN :start AND :end ";
        $params[':start'] = $startDate;
        $params[':end'] = $endDate;
    }

    $sql = "SELECT sp.mahang, sp.tenhang AS tensanpham, sp.hinhanh,
                   SUM(ct.soluong) AS total_qty,
                   SUM(ct.soluong * ct.dongia) AS total_revenue
            FROM tbchitietdonhang ct
            JOIN tbdonhang dh ON ct.madonhang = dh.madonhang
            JOIN tbmathang sp ON ct.mahang = sp.mahang
            WHERE dh.tinhtrang = 'Đã giao' $whereDate
            GROUP BY sp.mahang, sp.tenhang, sp.hinhanh
            ORDER BY total_qty DESC LIMIT :limit";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * [MỚI] Hàm báo cáo chi tiết theo ngày
 * Kết hợp bảng tbdonhang và tbchitietdonhang để lấy số liệu chính xác
 */
function getDetailedDailyReport(PDO $pdo, string $startDate, string $endDate): array {
    $start = date('Y-m-d', strtotime($startDate));
    $end = date('Y-m-d', strtotime($endDate));

    $sql = "SELECT 
                DATE(dh.ngaymua) as ngay_bao_cao,
                COUNT(dh.madonhang) as so_don_hang,
                SUM(dh.tongtiendonhang) as doanh_thu_thuc,
                SUM(dh.giatrigiam) as tong_giam_gia,
                (
                    SELECT SUM(ct.soluong)
                    FROM tbchitietdonhang ct
                    JOIN tbdonhang dh2 ON ct.madonhang = dh2.madonhang
                    WHERE DATE(dh2.ngaymua) = DATE(dh.ngaymua) 
                    AND dh2.tinhtrang = 'Đã giao'
                ) as tong_san_pham
            FROM tbdonhang dh
            WHERE dh.tinhtrang = 'Đã giao'
            AND DATE(dh.ngaymua) BETWEEN :start AND :end
            GROUP BY DATE(dh.ngaymua)
            ORDER BY ngay_bao_cao DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':start' => $start, ':end' => $end]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getProductRevenueReport(PDO $pdo, string $startDate, string $endDate): array {
    $start = date('Y-m-d', strtotime($startDate));
    $end = date('Y-m-d', strtotime($endDate));

    $sql = "SELECT 
                DATE(dh.ngaymua) as ngay_ban,
                sp.mahang,
                sp.tenhang,
                SUM(ct.soluong) as so_luong_ban,
                ct.dongia,
                SUM(ct.soluong * ct.dongia) as thanh_tien
            FROM tbdonhang dh
            JOIN tbchitietdonhang ct ON dh.madonhang = ct.madonhang
            JOIN tbmathang sp ON ct.mahang = sp.mahang
            WHERE dh.tinhtrang = 'Đã giao'
              AND DATE(dh.ngaymua) BETWEEN :start AND :end
            GROUP BY DATE(dh.ngaymua), sp.mahang, sp.tenhang, ct.dongia
            ORDER BY ngay_ban DESC, sp.tenhang ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':start' => $start, ':end' => $end]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
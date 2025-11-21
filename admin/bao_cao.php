<?php
/**
 * bao_cao.php
 * Các hàm truy vấn dữ liệu cho Dashboard - Đã tối ưu hóa.
 */

/**
 * Trả về dữ liệu biểu đồ doanh thu theo ngày trong khoảng $startDate..$endDate
 * - Trả về ['labels'=>[], 'data'=>[], 'total'=> float]
 */
function getRevenueChartData(PDO $pdo, string $startDate, string $endDate): array {
    $start = date('Y-m-d', strtotime($startDate));
    $end = date('Y-m-d', strtotime($endDate));

    $sql = "
        SELECT DATE(dh.ngaymua) AS order_date,
               COALESCE(SUM(ct.soluong * ct.dongia), 0) AS daily_revenue
        FROM tbdonhang dh
        INNER JOIN tbchitietdonhang ct ON dh.madonhang = ct.madonhang
        WHERE DATE(dh.ngaymua) BETWEEN :start AND :end
          AND dh.tinhtrang = 'Đã giao'
        GROUP BY order_date
        ORDER BY order_date ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':start' => $start, ':end' => $end]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Map ngày -> doanh thu
    $map = array_column($rows, 'daily_revenue', 'order_date');

    $labels = [];
    $data = [];
    $total = 0.0;
    
    // Lấp đầy các ngày bị thiếu (rút gọn logic lặp)
    $current = new DateTime($start);
    $endDt = new DateTime($end);
    $endDt->modify('+1 day');

    while ($current < $endDt) {
        $dateStr = $current->format('Y-m-d');
        $labels[] = $current->format('d/m');
        
        $value = (float)($map[$dateStr] ?? 0.0);
        $data[] = $value;
        $total += $value;
        
        $current->modify('+1 day');
    }

    return [
        'labels' => $labels,
        'data' => $data,
        'total' => $total
    ];
}

/**
 * Trạng thái đơn hàng (doughnut chart)
 */
function getOrderStatusStats(PDO $pdo): array {
    $sql = "
        SELECT tinhtrang, COUNT(*) AS total_count
        FROM tbdonhang
        GROUP BY tinhtrang
        ORDER BY total_count DESC
    ";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Top bán chạy theo số lượng (limit)
 * - Đã thay đổi limit mặc định từ 5 xuống 3
 */
function getTopProducts(PDO $pdo, int $limit = 3, ?string $startDate = null, ?string $endDate = null): array {
    $params = [];
    $whereDate = "";
    
    if ($startDate !== null && $endDate !== null) {
        $whereDate = " AND DATE(dh.ngaymua) BETWEEN :startDate AND :endDate ";
        $params[':startDate'] = date('Y-m-d', strtotime($startDate));
        $params[':endDate'] = date('Y-m-d', strtotime($endDate));
    }

    $sql = "
        SELECT 
            sp.mahang,
            sp.tenhang AS tensanpham,
            sp.hinhanh,
            COALESCE(SUM(ct.soluong),0) AS total_qty,
            COALESCE(SUM(ct.soluong * ct.dongia),0) AS total_revenue
        FROM tbchitietdonhang ct
        INNER JOIN tbdonhang dh ON ct.madonhang = dh.madonhang
        INNER JOIN tbmathang sp ON ct.mahang = sp.mahang
        WHERE dh.tinhtrang = 'Đã giao'
        {$whereDate}
        GROUP BY sp.mahang, sp.tenhang, sp.hinhanh
        ORDER BY total_qty DESC, total_revenue DESC
        LIMIT :limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);

    if (!empty($params)) {
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
    }

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cast fields
    foreach ($rows as &$r) {
        $r['total_qty'] = (int)($r['total_qty'] ?? 0);
        $r['total_revenue'] = (float)($r['total_revenue'] ?? 0.0);
    }

    return $rows;
}
?>
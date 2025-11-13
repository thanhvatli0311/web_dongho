<?php
// Đảm bảo file db.php đã được include trong file gọi (ví dụ: admin.php)

/**
 * Lấy số lượng và tổng giá trị đơn hàng theo trạng thái (PDO)
 * @param PDO $pdo Đối tượng kết nối PDO
 * @return array Mảng chứa dữ liệu thống kê trạng thái
 */
function getOrderStatusStats(PDO $pdo) { 
    // 1. Chuẩn bị truy vấn: Lấy tình trạng và số lượng
    $sql = "SELECT tinhtrang, COUNT(*) AS total_count 
            FROM tbdonhang 
            GROUP BY tinhtrang";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        $stats = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { 
            // Tách dữ liệu thành mảng để dễ dàng sử dụng cho Chart.js
            $stats[] = [
                'status' => $row['tinhtrang'],
                'count'  => (int)$row['total_count']
            ];
        }
        return $stats;
    } catch (PDOException $e) {
        // Xử lý lỗi PDO
        error_log("Lỗi truy vấn trạng thái đơn hàng: " . $e->getMessage());
        return [];
    }
}

/**
 * Lấy dữ liệu doanh thu hàng ngày trong 7 ngày gần nhất (Chỉ tính đơn 'Đã giao') (PDO)
 * @param PDO $pdo Đối tượng kết nối PDO
 * @return array Mảng chứa doanh thu theo ngày
 */
function getWeeklyRevenue(PDO $pdo) {
    // Truy vấn tổng hợp
    $sql = "SELECT 
                DATE(dh.ngaymua) AS order_date, 
                SUM(ct.soluong * ct.dongia) AS daily_revenue 
            FROM 
                tbdonhang AS dh 
            INNER JOIN 
                tbchitietdonhang AS ct ON dh.madonhang = ct.madonhang 
            WHERE 
                dh.tinhtrang = 'Đã giao' 
                AND dh.ngaymua >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
            GROUP BY 
                order_date 
            ORDER BY 
                order_date ASC";
    
    $revenue_data = [];
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { 
            $revenue_data[$row['order_date']] = (float)$row['daily_revenue'];
        }
    } catch (PDOException $e) {
        error_log("Lỗi truy vấn doanh thu hàng tuần: " . $e->getMessage());
    }

    // Đảm bảo đủ 7 ngày, gán 0 cho ngày không có doanh thu
    $final_data = [];
    for ($i = 6; $i >= 0; $i--) {
        // Tạo chuỗi ngày (YYYY-MM-DD) của 7 ngày gần nhất
        $date = date('Y-m-d', strtotime("-$i day"));
        // Gán doanh thu (0 nếu không có trong CSDL)
        $final_data[$date] = $revenue_data[$date] ?? 0;
    }
    
    return $final_data;
}
?>
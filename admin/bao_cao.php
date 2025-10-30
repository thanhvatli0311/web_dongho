<?php
// Đảm bảo file db.php đã được include trong file gọi (ví dụ: dashboard.php)

/**
 * Lấy số lượng và tổng giá trị đơn hàng theo trạng thái
 * @param mysqli $conn Đối tượng kết nối MySQLi
 * @return array Mảng chứa dữ liệu thống kê trạng thái
 */
function getOrderStatusStats($conn) {
    // 1. Chuẩn bị truy vấn: Lấy tình trạng và số lượng
    $sql = "SELECT tinhtrang, COUNT(*) AS total_count 
            FROM tbdonhang 
            GROUP BY tinhtrang";
    
    $result = $conn->query($sql);
    
    $stats = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Tách dữ liệu thành mảng để dễ dàng sử dụng cho Chart.js
            $stats[] = [
                'status' => $row['tinhtrang'],
                'count'  => (int)$row['total_count']
            ];
        }
    }
    
    return $stats;
}

/**
 * Lấy dữ liệu doanh thu hàng ngày trong 7 ngày gần nhất (Chỉ tính đơn 'Đã giao')
 * @param mysqli $conn Đối tượng kết nối MySQLi
 * @return array Mảng chứa doanh thu theo ngày
 */
function getWeeklyRevenue($conn) {
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
    
    $result = $conn->query($sql);
    
$revenue_data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $revenue_data[$row['order_date']] = (float)$row['daily_revenue'];
        }
    }
    
// Đảm bảo đủ 7 ngày, gán 0 cho ngày không có doanh thu
    $final_data = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i day"));
        // Lấy dữ liệu đã có hoặc gán 0 nếu ngày đó không có đơn hàng
        $final_data[$date] = $revenue_data[$date] ?? 0; 
    }
    
    return $final_data;
}

// Thêm các hàm khác (Top Sellers, New Customers, ...) vào file này
?>
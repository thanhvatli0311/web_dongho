<?php
session_start();
require __DIR__ . '/../includes/db.php';

// Kiểm tra quyền
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    die("Access Denied");
}

// Lấy ngày từ GET
$start = $_GET['start'] ?? date('Y-m-d', strtotime('-7 days'));
$end = $_GET['end'] ?? date('Y-m-d');

// Tên file tải về
$filename = "Bao_cao_doanh_thu_" . date('dmY') . ".csv";

// Thiết lập header để trình duyệt hiểu đây là file tải về
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Tạo file CSV
$output = fopen('php://output', 'w');

// === QUAN TRỌNG: Thêm BOM để Excel đọc được tiếng Việt UTF-8 ===
fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

// 1. Viết tiêu đề cột
fputcsv($output, ['STT', 'Mã Đơn Hàng', 'Ngày Mua', 'Khách Hàng', 'Sản Phẩm', 'Tổng Tiền (VNĐ)']);

// 2. Truy vấn chi tiết để xuất báo cáo
$sql = "SELECT 
            dh.madonhang, 
            dh.ngaymua, 
            kh.tenkhachhang, 
            sp.tensanpham,
            (ct.soluong * ct.dongia) as thanh_tien
        FROM tbdonhang dh
        JOIN tbchitietdonhang ct ON dh.madonhang = ct.madonhang
        JOIN tbkhachhang kh ON dh.makhachhang = kh.makhachhang
        JOIN tbsanpham sp ON ct.masanpham = sp.masanpham
        WHERE dh.tinhtrang = 'Đã giao' 
        AND DATE(dh.ngaymua) BETWEEN :start AND :end
        ORDER BY dh.ngaymua DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([':start' => $start, ':end' => $end]);

$stt = 1;
$totalRevenue = 0;

// 3. Ghi dữ liệu từng dòng
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $stt++,
        $row['madonhang'],
        date('d/m/Y H:i', strtotime($row['ngaymua'])),
        $row['tenkhachhang'],
        $row['tensanpham'],
        number_format($row['thanh_tien'], 0, ',', '.') // Format tiền
    ]);
    $totalRevenue += $row['thanh_tien'];
}

// 4. Ghi dòng tổng cộng cuối cùng
fputcsv($output, ['', '', '', '', 'TỔNG DOANH THU:', number_format($totalRevenue, 0, ',', '.')]);

fclose($output);
exit;
?>
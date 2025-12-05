<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/bao_cao.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    die("Access Denied");
}

$start = $_GET['start'] ?? date('Y-m-d');
$end = $_GET['end'] ?? date('Y-m-d');

// Dùng hàm mới lấy chi tiết sản phẩm
$data = getProductRevenueReport($pdo, $start, $end);

$filename = "BaoCao_ChiTiet_" . date('dmY', strtotime($start)) . "_" . date('dmY', strtotime($end)) . ".xls";

// Headers
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo '<meta charset="UTF-8">';
echo '<table border="1">';
// Header xanh đậm chữ trắng giống ảnh
echo '<thead>
        <tr style="background-color: #0d47a1; color: #ffffff;">
            <th>TT</th>
            <th>Ngày bán</th>
            <th>Mã sản phẩm</th>
            <th>Tên sản phẩm</th>
            <th>Số lượng bán</th>
            <th>Đơn giá (VNĐ)</th>
            <th>Thành tiền (VNĐ)</th>
        </tr>
      </thead>';
echo '<tbody>';

$sumQty = 0;
$sumTotal = 0;
$i = 1;

foreach ($data as $row) {
    echo '<tr>';
    echo '<td style="text-align:center;">' . $i++ . '</td>';
    echo '<td>' . date('d/m/Y', strtotime($row['ngay_ban'])) . '</td>';
    echo '<td>' . $row['mahang'] . '</td>';
    echo '<td>' . $row['tenhang'] . '</td>';
    echo '<td style="text-align:center;">' . $row['so_luong_ban'] . '</td>';
    echo '<td style="text-align:right;">' . $row['dongia'] . '</td>';
    echo '<td style="text-align:right;">' . $row['thanh_tien'] . '</td>';
    echo '</tr>';

    $sumQty += $row['so_luong_ban'];
    $sumTotal += $row['thanh_tien'];
}

// Dòng tổng
echo '<tr style="font-weight:bold; background-color: #fff9c4;">';
echo '<td colspan="4" style="text-align:center;">TỔNG CỘNG</td>';
echo '<td style="text-align:center;">' . $sumQty . '</td>';
echo '<td></td>';
echo '<td style="text-align:right;">' . $sumTotal . '</td>';
echo '</tr>';

echo '</tbody></table>';
exit;
?>
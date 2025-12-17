<?php
// ghtk_webhook.php - File nhận dữ liệu Webhook từ GHTK (Sử dụng $pdo từ db.php)

// THAY ĐỔI ĐƯỜNG DẪN NÀY NẾU FILE db.php CỦA BẠN KHÔNG CÙNG THƯ MỤC
include 'db.php'; 

/**
 * Ánh xạ trạng thái GHTK (ID số) sang trạng thái chung của website (chuỗi).
 * @param int $ghtk_status_id
 * @return string
 */
function mapStatusIdToWebStatus($ghtk_status_id) {
    switch ((int)$ghtk_status_id) {
        case 1: case 2: case 3: 
            return 'Đang xử lý';
        case 4: case 6: 
            return 'Đang giao hàng';
        case 5: case 20: 
            return 'Đã giao'; 
        case 7: case 8: case 9: case 10: 
            return 'Đã hủy'; 
        default:
            return 'Đang xử lý';
    }
}

// ----------------------------------------------------
// BƯỚC 1: NHẬN DỮ LIỆU JSON TỪ GHTK
// ----------------------------------------------------
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Kiểm tra dữ liệu và kết nối
if (empty($data) || !isset($data['order']) || !isset($pdo)) {
    http_response_code(400);
    exit;
}

$order_info = $data['order'];
$mavandon_ghtk = $order_info['label_id'] ?? null;
$ghtk_status_id = $order_info['status_id'] ?? null;
$status_name = $order_info['status_name'] ?? 'Không rõ';
$time = $order_info['modified'] ?? date('Y-m-d H:i:s'); 
$location = $order_info['location'] ?? null;
$reason = $order_info['reason'] ?? null;

if (!$mavandon_ghtk || !$ghtk_status_id) {
    http_response_code(400); 
    exit;
}

$tinhtrang_web = mapStatusIdToWebStatus($ghtk_status_id);

// Bắt đầu Transaction
$pdo->beginTransaction();

try {
    // 1. TÌM madonhang CỦA BẠN QUA MÃ VẬN ĐƠN (mavandon)
    $sql_find_order = "SELECT madonhang FROM tbdonhang WHERE mavandon = ?";
    $stmt_find = $pdo->prepare($sql_find_order);
    $stmt_find->execute([$mavandon_ghtk]);
    $madonhang = $stmt_find->fetchColumn();

    if (!$madonhang) {
        http_response_code(200); 
        $pdo = null;
        exit;
    }
    
    // 2. KIỂM TRA LỊCH SỬ VÀ CHÈN (Ngăn chặn trùng lặp)
    $sql_check = "SELECT id FROM tbtheodoi WHERE madonhang = ? AND ghtk_status_id = ? AND thoigian = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$madonhang, $ghtk_status_id, $time]);

    if ($stmt_check->rowCount() == 0) {
        $sql_insert = "INSERT INTO tbtheodoi (madonhang, mavandon, ghtk_status_id, trangthai_ghtk, mo_ta, tracking_location, thoigian) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute([
            $madonhang, 
            $mavandon_ghtk, 
            $ghtk_status_id, 
            $status_name, 
            $reason, 
            $location, 
            $time
        ]);
    }
    
    // 3. CẬP NHẬT BẢNG tbdonhang
    $sql_update_donhang = "UPDATE tbdonhang 
                           SET tinhtrang = ?, current_status_id = ?, thoigian_capnhat = ?
                           WHERE madonhang = ?";
    $stmt_update = $pdo->prepare($sql_update_donhang);
    $stmt_update->execute([$tinhtrang_web, $ghtk_status_id, $time, $madonhang]);

    $pdo->commit();
    http_response_code(200); 
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Lỗi Webhook PDO: ' . $e->getMessage() . ' Dữ liệu: ' . $json_data);
    http_response_code(500);
}

$pdo = null;
?>
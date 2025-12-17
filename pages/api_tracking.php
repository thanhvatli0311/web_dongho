<?php
// api_tracking.php - Xử lý AJAX tra cứu từ frontend (Sử dụng $pdo từ db.php)
header('Content-Type: application/json; charset=utf-8');

// THAY ĐỔI ĐƯỜNG DẪN NÀY NẾU FILE db.php CỦA BẠN KHÔNG CÙNG THƯ MỤC
include 'db.php'; 

// Cấu hình GHTK (Đã tích hợp Token bạn cung cấp)
define('GHTK_API_TOKEN', '34UV7PnBnFeyFN7UPfXG1LWxVAzNR8EIAFrhSVq'); 
define('CACHE_DURATION_SECONDS', 30 * 60); // 30 phút
define('GHTK_API_URL', 'https://services.giaohangtietkiem.vn/services/tracking?');

/**
 * Ánh xạ trạng thái GHTK (ID số) sang trạng thái chung của website (chuỗi).
 * @param int $ghtk_status_id
 * @return string
 */
function mapStatusIdToWebStatus($ghtk_status_id) {
    switch ((int)$ghtk_status_id) {
        case 1: case 2: case 3: return 'Đang xử lý';
        case 4: case 6: return 'Đang giao hàng';
        case 5: case 20: return 'Đã giao';
        case 7: case 8: case 9: case 10: return 'Đã hủy';
        default: return 'Đang xử lý';
    }
}

$madonhang = $_REQUEST['madonhang'] ?? null;
if (empty($madonhang)) {
    echo json_encode(['error' => 'Vui lòng cung cấp mã đơn hàng.']);
    exit;
}

// Kiểm tra kết nối PDO
if (!isset($pdo)) {
    echo json_encode(['error' => 'Lỗi: Không thể kết nối CSDL (Kiểm tra db.php).']);
    exit;
}


try {
    // 1. KIỂM TRA MÃ VẬN ĐƠN VÀ TRẠNG THÁI HIỆN TẠI (tbdonhang)
    $sql_order = "SELECT mavandon, current_status_id, thoigian_capnhat, tinhtrang FROM tbdonhang WHERE madonhang = ?";
    $stmt_order = $pdo->prepare($sql_order);
    $stmt_order->execute([$madonhang]);
    $order = $stmt_order->fetch();

    if (!$order) {
        echo json_encode(['error' => 'Không tìm thấy đơn hàng trong hệ thống.']);
        exit;
    }

    $mavandon = $order['mavandon'];
    $current_status_id = $order['current_status_id'];
    $current_tinhtrang = $order['tinhtrang'];
    $thoigian_capnhat = $order['thoigian_capnhat'];
    $warning = null;

    if (empty($mavandon)) {
        echo json_encode(['error' => 'Đơn hàng chưa có mã vận đơn GHTK. Vui lòng thử lại sau.']);
        exit;
    }

    // Logic cập nhật: Gọi API nếu cache hết hạn hoặc trạng thái chưa cuối cùng và đã quá 5 phút
    $need_update = false;
    $time_since_last_update = time() - strtotime($thoigian_capnhat);
    $is_final_status = in_array($current_status_id, [5, 7, 8, 9, 10, 20]); 

    if (!$is_final_status && $time_since_last_update > 300) { // 5 phút nếu chưa hoàn thành
        $need_update = true;
    } elseif ($time_since_last_update > CACHE_DURATION_SECONDS) { // 30 phút
        $need_update = true;
    }
    
    // 2. GỌI API GHTK NẾU CẦN
    if ($need_update) {
        $api_url = GHTK_API_URL . "label=" . urlencode($mavandon);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Token: ' . GHTK_API_TOKEN]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $ghtk_data = json_decode($response, true);

        if ($http_code == 200 && isset($ghtk_data['success']) && $ghtk_data['success'] && isset($ghtk_data['order'])) {
            
            $pdo->beginTransaction();
            try {
                $tracking_history = $ghtk_data['order']['tracking'] ?? [];
                
                // Cập nhật tbtheodoi
                foreach ($tracking_history as $item) {
                    $item_id = $item['status_id'] ?? 0;
                    $item_time = date('Y-m-d H:i:s', strtotime($item['time']));
                    
                    $sql_check = "SELECT id FROM tbtheodoi WHERE madonhang = ? AND ghtk_status_id = ? AND thoigian = ?";
                    $stmt_check = $pdo->prepare($sql_check);
                    $stmt_check->execute([$madonhang, $item_id, $item_time]);
                    
                    if ($stmt_check->rowCount() == 0) {
                        $sql_insert = "INSERT INTO tbtheodoi (madonhang, mavandon, ghtk_status_id, trangthai_ghtk, mo_ta, tracking_location, thoigian) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $stmt_insert = $pdo->prepare($sql_insert);
                        $stmt_insert->execute([
                            $madonhang, 
                            $mavandon, 
                            $item_id, 
                            $item['status_name'] ?? '', 
                            $item['status_description'] ?? '', 
                            $item['location'] ?? null, 
                            $item_time
                        ]);
                    }
                }

                // Cập nhật tbdonhang
                $latest_tracking = end($tracking_history);
                $new_ghtk_status_id = $latest_tracking['status_id'] ?? $current_status_id;
                $new_time = date('Y-m-d H:i:s', strtotime($latest_tracking['time'] ?? date('Y-m-d H:i:s')));
                $new_tinhtrang_web = mapStatusIdToWebStatus($new_ghtk_status_id);

                $sql_update_donhang = "UPDATE tbdonhang 
                                       SET tinhtrang = ?, current_status_id = ?, thoigian_capnhat = ?
                                       WHERE madonhang = ?";
                $stmt_update = $pdo->prepare($sql_update_donhang);
                $stmt_update->execute([$new_tinhtrang_web, $new_ghtk_status_id, $new_time, $madonhang]);

                $pdo->commit();
                $current_tinhtrang = $new_tinhtrang_web;
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log('Lỗi cập nhật CSDL từ API Pull: ' . $e->getMessage());
                $warning = 'Cập nhật CSDL thất bại. Hiển thị dữ liệu cũ.';
            }
        } else {
            // Lỗi GHTK API
            $warning = 'GHTK API báo lỗi. Hiển thị dữ liệu cũ.';
        }
    }

    // 3. LẤY LỊCH SỬ TỪ CSDL (Luôn dùng DB để trả về)
    $sql_history = "SELECT thoigian, trangthai_ghtk, mo_ta, tracking_location 
                    FROM tbtheodoi 
                    WHERE madonhang = ?
                    ORDER BY thoigian DESC";
    $stmt_history = $pdo->prepare($sql_history);
    $stmt_history->execute([$madonhang]);
    $history = $stmt_history->fetchAll();

    $response_data = [
        'current_status' => $current_tinhtrang,
        'history' => $history,
        'warning' => $warning,
    ];
    
    echo json_encode($response_data);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Lỗi truy vấn CSDL: ' . $e->getMessage()]);
}

$pdo = null;
?>
<?php
header('Content-Type: application/json; charset=utf-8');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dbdongho');
define('GHTK_TOKEN', '34UV7PnBnFeyFN7UPfXG1LWxVAzNR8EIAFrhSVq');

/**
 * Kết nối đến cơ sở dữ liệu MySQL.
 * @return mysqli
 */
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Lỗi kết nối CSDL: ' . $conn->connect_error]));
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

/**
 * Gọi API của Giao Hàng Tiết Kiệm để lấy trạng thái mới nhất của đơn hàng.
 * @param string $mavandon Mã vận đơn của GHTK.
 * @return array
 */
function callGHTKApi($mavandon) {
    $url = "https://services.giaohangtietkiem.vn/services/shipment/tracking?order_id=" . urlencode($mavandon);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Token: ' . GHTK_TOKEN]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // curl_close($ch);

    if ($http_code != 200) {
        return ['error' => 'Lỗi kết nối API GHTK. HTTP Code: ' . $http_code];
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['success']) && $data['success'] === true) {
        return ['success' => true, 'data' => $data];
    } else {
        // GHTK có thể trả về success=false kèm theo message lỗi
        $message = isset($data['message']) ? $data['message'] : 'Không thể tra cứu vận đơn này.';
        return ['error' => 'Lỗi từ GHTK: ' . $message];
    }
}

/**
 * Ánh xạ trạng thái chi tiết của GHTK sang trạng thái tổng quát của website.
 * @param string $ghtk_label Nhãn trạng thái từ GHTK.
 * @return string
 */
function mapStatus($ghtk_label) {
    $map = [
        'Đã tiếp nhận' => 'Đang xử lý',
        'Đang lấy hàng' => 'Đang xử lý',
        'Đã lấy hàng' => 'Đang xử lý',
        'Đang vận chuyển' => 'Đang giao hàng',
        'Đang giao hàng' => 'Đang giao hàng',
        'Đã giao hàng' => 'Đã giao',
        'Hoàn thành' => 'Đã giao',
        'Không giao được' => 'Đang giao hàng',
        'Hủy' => 'Đã hủy',
    ];
    
    foreach ($map as $key => $value) {
        if (stripos($ghtk_label, $key) !== false) {
            return $value;
        }
    }

    return 'Đang xử lý';
}

/**
 * Cập nhật lịch sử theo dõi và trạng thái đơn hàng trong CSDL.
 * @param mysqli $conn
 * @param string $madonhang
 * @param array $ghtk_data
 * @return bool|array
 */
function updateDatabase($conn, $madonhang, $ghtk_data) {
    $conn->begin_transaction();
    $current_time = date('Y-m-d H:i:s');
    $last_status = 'Đang xử lý';

    try {
        // Cập nhật lịch sử chi tiết (tbtheodoi)
        if (isset($ghtk_data['data']['tracking'])) {
            // Xóa lịch sử cũ để đồng bộ lại toàn bộ từ GHTK.
            // Tối ưu hơn có thể kiểm tra và chỉ insert bản ghi mới.
            $conn->query("DELETE FROM tbtheodoi WHERE madonhang = '{$madonhang}'");

            foreach ($ghtk_data['data']['tracking'] as $item) {
                $status_name = $conn->real_escape_string($item['status_name'] ?? 'Không rõ');
                $status_description = $conn->real_escape_string($item['status_description'] ?? '');
                $time = date('Y-m-d H:i:s', strtotime($item['time']));

                $sql_insert = "INSERT INTO tbtheodoi (madonhang, thoigian, trangthai_ghtk, mo_ta) 
                               VALUES ('{$madonhang}', '{$time}', '{$status_name}', '{$status_description}')";
                $conn->query($sql_insert);

                $last_status = mapStatus($status_name);
            }
        }

        // Cập nhật trạng thái tổng quát và thời gian của đơn hàng (tbdonhang)
        $sql_update_donhang = "UPDATE tbdonhang 
                               SET tinhtrang = '{$last_status}', thoigian_capnhat = '{$current_time}'
                               WHERE madonhang = '{$madonhang}'";
        $conn->query($sql_update_donhang);

        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['error' => 'Lỗi cập nhật CSDL: ' . $e->getMessage()];
    }
}

/**
 * Lấy lịch sử theo dõi của đơn hàng trực tiếp từ CSDL.
 * @param mysqli $conn
 * @param string $madonhang
 * @param string $current_tinhtrang
 * @return array
 */
function getTrackingHistoryFromDB($conn, $madonhang, $current_tinhtrang) {
    $history = [];
    $sql = "SELECT thoigian, trangthai_ghtk, mo_ta 
            FROM tbtheodoi 
            WHERE madonhang = '{$madonhang}' 
            ORDER BY thoigian DESC";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
    }

    return [
        'current_status' => $current_tinhtrang,
        'history' => $history
    ];
}



// XỬ LÝ CHÍNH


$madonhang = $_REQUEST['madonhang'] ?? null;
if (!$madonhang) {
    echo json_encode(['error' => 'Vui lòng cung cấp mã đơn hàng.']);
    exit;
}

$conn = connectDB();

// Lấy thông tin cơ bản của đơn hàng
$sql_info = "SELECT mavandon, thoigian_capnhat, tinhtrang FROM tbdonhang WHERE madonhang = '{$madonhang}'";
$result_info = $conn->query($sql_info);

if (!$result_info || $result_info->num_rows == 0) {
    echo json_encode(['error' => 'Không tìm thấy đơn hàng với mã: ' . htmlspecialchars($madonhang)]);
    $conn->close();
    exit;
}

$order_info = $result_info->fetch_assoc();
$mavandon = $order_info['mavandon'];
$thoigian_capnhat = $order_info['thoigian_capnhat'];
$current_tinhtrang = $order_info['tinhtrang'];

if (!$mavandon) {
    echo json_encode(['error' => 'Đơn hàng này chưa có mã vận đơn GHTK.']);
    $conn->close();
    exit;
}

// Chỉ gọi API GHTK nếu lần cập nhật cuối cùng đã quá 30 phút
$need_update = false;
if (empty($thoigian_capnhat) || (time() - strtotime($thoigian_capnhat)) > (30 * 60)) {
    $need_update = true;
}

if ($need_update) {
    $api_result = callGHTKApi($mavandon);
    
    if (isset($api_result['error'])) {
        // Nếu API lỗi, trả về dữ liệu cũ từ DB kèm theo cảnh báo
        $response_data = getTrackingHistoryFromDB($conn, $madonhang, $current_tinhtrang);
        $response_data['warning'] = 'Lỗi cập nhật trạng thái mới nhất từ GHTK: ' . $api_result['error'];
        echo json_encode($response_data);
        $conn->close();
        exit;
    }

    updateDatabase($conn, $madonhang, $api_result);
    
    // Lấy lại tình trạng mới nhất từ DB sau khi cập nhật
    $new_info = $conn->query($sql_info)->fetch_assoc();
    $current_tinhtrang = $new_info['tinhtrang'];
}

// Trả về dữ liệu cuối cùng (dữ liệu mới hoặc dữ liệu cũ trong cache 30 phút)
$response_data = getTrackingHistoryFromDB($conn, $madonhang, $current_tinhtrang);
echo json_encode($response_data);

$conn->close();
?>
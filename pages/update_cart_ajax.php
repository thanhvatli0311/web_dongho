<?php
session_start();
// Đảm bảo file db.php (kết nối $pdo) và functions.php (chứa hàm KM) được nhúng
require_once '../includes/db.php'; 
require_once '../includes/functions.php'; 

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Yêu cầu AJAX không hợp lệ.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $itemId = $_POST['item_id'] ?? null;
    // Sử dụng max(1, ...) để đảm bảo số lượng luôn là số nguyên dương hợp lệ
    $quantity = max(1, (int)($_POST['quantity'] ?? 1)); 
    $checked = filter_var($_POST['checked'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    $couponCode = trim($_POST['coupon_code'] ?? '');
    $action = $_POST['action'];

    // 1. CẬP NHẬT SESSION cho sản phẩm
    if ($action === 'update_item' && $itemId) {
        if (isset($_SESSION['cart'][$itemId])) {
            $_SESSION['cart'][$itemId]['quantity'] = $quantity; 
            $_SESSION['cart'][$itemId]['checked'] = $checked;
        }
    }
    
    // 2. CẬP NHẬT MÃ KHUYẾN MÃI vào session (chỉ khi có yêu cầu cập nhật coupon)
    if (isset($_POST['update_coupon']) && (int)$_POST['update_coupon'] === 1) {
        // Nếu mã rỗng, loại bỏ mã khỏi session
        $_SESSION['coupon_code'] = !empty($couponCode) ? $couponCode : null;
    } else {
        // Lấy mã khuyến mãi hiện tại trong session (cho các action khác)
        $couponCode = $_SESSION['coupon_code'] ?? null;
    }
    
    // 3. TÍNH TOÁN LẠI TỔNG GIÁ TRỊ GỐC (chưa giảm)
    $raw_total_price = 0.00;
    $item_prices = [];

    if (!empty($_SESSION['cart'])) {
        $item_ids = array_keys($_SESSION['cart']);
        
        if (!empty($item_ids)) {
             $placeholders = implode(',', array_fill(0, count($item_ids), '?'));
            
            try {
                // Lấy đơn giá của tất cả sản phẩm trong giỏ hàng
                $stmt = $pdo->prepare("SELECT mahang, dongia FROM tbmathang WHERE mahang IN ($placeholders)");
                $stmt->execute($item_ids);
                $products = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            } catch (\PDOException $e) {
                 echo json_encode(['status' => 'error', 'message' => 'Lỗi CSDL khi truy vấn giá.']);
                 exit;
            }
            
            foreach ($_SESSION['cart'] as $itemIdKey => $item) {
                // Nếu sản phẩm không tồn tại trong DB, bỏ qua
                if (!isset($products[$itemIdKey])) continue;

                $is_checked = (bool)($item['checked'] ?? true);
                $unitPrice = (float)($products[$itemIdKey] ?? 0);
                $quantity = (int)($item['quantity'] ?? 0);
                $subtotal = $unitPrice * $quantity;
                
                // Chỉ cộng vào tổng gốc nếu sản phẩm được check
                if ($is_checked) {
                    $raw_total_price += $subtotal;
                }
                $item_prices[$itemIdKey] = $subtotal; // Lưu tổng phụ để cập nhật lại bên client
            }
        }
    }
    
    // 4. TÍNH TOÁN KHUYẾN MÃI
    $discount_result = tinhGiaSauKhuyenMai($pdo, $couponCode, $raw_total_price);
    
    // 5. TRẢ VỀ KẾT QUẢ JSON
    $response = [
        'status' => 'success',
        'raw_total_price' => round($raw_total_price, 0),
        'discount_amount' => $discount_result['tonggiatrigiam'],
        'final_total_price' => $discount_result['giacuoicung'],
        'item_prices' => $item_prices,
        'coupon_message' => $discount_result['thongbao']
    ];

} 

echo json_encode($response);
?>
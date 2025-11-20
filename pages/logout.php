<?php
session_start();
ob_start();

// Đảm bảo file db.php của bạn đã tạo biến $pdo
require_once '../includes/db.php'; 

// Chỉ thực hiện lưu giỏ hàng nếu người dùng đã đăng nhập và có giỏ hàng
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    
    try {
        // 1. Lấy mã khách hàng (makhach)
        $stmt_khach = $pdo->prepare("SELECT makhach FROM tbkhachhang WHERE username = ?");
        $stmt_khach->execute([$username]);
        $customer = $stmt_khach->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            $makhach = $customer['makhach'];
            
            // 2. Xóa giỏ hàng cũ đã lưu (luôn làm sạch trước khi lưu mới)
            $pdo->prepare("DELETE FROM tbgiohang_luu WHERE makhach = ?")->execute([$makhach]);
            
            // 3. Lưu giỏ hàng hiện tại trong Session vào CSDL
            if (!empty($_SESSION['cart'])) {
                $sql_save = "INSERT INTO tbgiohang_luu (makhach, mahang, soluong, checked) VALUES (?, ?, ?, ?)";
                $stmt_save = $pdo->prepare($sql_save);
                
                foreach ($_SESSION['cart'] as $item_id => $item) {
                    $quantity = (int)($item['quantity'] ?? 1);
                    // Lấy trạng thái checked, mặc định là 1 nếu không có
                    $checked = (int)($item['checked'] ?? 1); 
                    
                    if ($quantity > 0) {
                        $stmt_save->execute([$makhach, $item_id, $quantity, $checked]);
                    }
                }
            }
        }
    } catch (PDOException $e) {
        // Nếu có lỗi CSDL, ghi log nhưng vẫn tiếp tục quá trình đăng xuất
        error_log("Lỗi lưu giỏ hàng khi đăng xuất: " . $e->getMessage()); 
    }
}

// 4. Hủy session và chuyển hướng (Đăng xuất chính thức)
session_unset();
session_destroy();

// Xóa cookie session nếu có
$params = session_get_cookie_params();
setcookie(session_name(), '', time() - 42000,
    $params["path"], $params["domain"],
    $params["secure"], $params["httponly"]
);

// Chuyển hướng về trang chủ hoặc trang đăng nhập
header("Location: index.php"); 
exit;
?>
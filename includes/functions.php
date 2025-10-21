<?php
// Hàm để chuyển hướng (redirect) người dùng
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Hàm mã hóa mật khẩu
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Hàm xác minh mật khẩu
function verifyPassword($password, $hashedPassword) {
    return password_verify($password, $hashedPassword);
}

// Hàm kiểm tra người dùng đã đăng nhập chưa
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Hàm lấy thông tin người dùng đang đăng nhập
function getCurrentUser() {
    if (isLoggedIn() && isset($_SESSION['user_id'])) {
        global $db; // Sử dụng đối tượng CSDL đã khởi tạo
        $db->query("SELECT id, username, email, fullname, address, phone_number, role FROM users WHERE id = :id");
        $db->bind(':id', $_SESSION['user_id']);
        return $db->single();
    }
    return null;
}

// Hàm kiểm tra quyền admin
function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

// Hàm hiển thị thông báo flash (ví dụ: thông báo thành công/lỗi)
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}
// Hàm để thoát các ký tự đặc biệt trong HTML (chống XSS)
function escapeHTML($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}


?>
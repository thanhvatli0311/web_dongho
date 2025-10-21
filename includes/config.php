<?php
// Thông tin kết nối cơ sở dữ liệu
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Mặc định của XAMPP
define('DB_PASSWORD', '');     // Mặc định của XAMPP, có thể bạn đã thay đổi
define('DB_NAME', 'web_dongho'); // Tên cơ sở dữ liệu bạn đã tạo

// Đường dẫn gốc của dự án (có thể tùy chỉnh nếu cần)
define('BASE_URL', 'http://localhost/project-dongho/');

// Các cấu hình khác (nếu có)
// define('SITE_NAME', 'Shop Đồng Hồ ABC');

// Bật hiển thị lỗi PHP trong quá trình phát triển (nên tắt khi deploy thực tế)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Bắt đầu session (rất quan trọng cho việc quản lý người dùng, giỏ hàng)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
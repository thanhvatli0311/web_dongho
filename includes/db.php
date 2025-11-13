<?php
// === THÔNG TIN KẾT NỐI CƠ SỞ DỮ LIỆU ===
// --> Hãy kiểm tra kỹ lại các thông tin này!
$host = 'localhost';
$dbname = 'dbdongho'; // Tên database của bạn có đúng là 'da_dongho' không?
$user = 'root';      // User mặc định của XAMPP là 'root'
$pass = '';          // Mật khẩu mặc định của XAMPP là RỖNG ('')
$charset = 'utf8mb4';

// Chuỗi DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// Các tùy chọn cho PDO để xử lý lỗi tốt hơn
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Báo lỗi khi có sự cố
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Trả về dạng mảng kết hợp
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Tắt chế độ mô phỏng prepare statement
];

try {
    // Thử tạo một đối tượng PDO mới để kết nối
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Nếu kết nối thất bại, bắt lỗi và hiển thị thông báo chi tiết
    // Thông báo này sẽ giúp bạn biết chính xác lỗi là gì.
    die("LỖI KẾT NỐI CƠ SỞ DỮ LIỆU: " . $e->getMessage());
}
?>
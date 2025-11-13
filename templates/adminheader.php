<?php
// Session check của bạn vẫn được giữ nguyên
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị - Đồng hồ cao cấp</title>

    <!-- TÍCH HỢP BOOTSTRAP 5 (QUAN TRỌNG NHẤT) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome cho các icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- CSS tùy chỉnh của bạn -->
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f4f9; }
        .admin-container { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #2c3e50; color: white; position: fixed; height: 100%; padding-top: 20px; z-index: 100; }
        .sidebar h3 { text-align: center; margin-bottom: 20px; }
        .sidebar a { display: block; color: white; padding: 15px 20px; text-decoration: none; font-size: 16px; }
        .sidebar a:hover { background: #34495e; }
        .sidebar a.active { background: #3498db; }
        .main-content { margin-left: 250px; padding: 20px; width: calc(100% - 250px); }
        
        /* Ghi đè một vài style mặc định của Bootstrap để giữ lại phong cách của bạn */
        .main-content h2 { color: #2c3e50; margin-bottom: 20px; text-align: center; }

        /* Các style cho table, form... cũ của bạn có thể giữ lại hoặc xóa đi nếu muốn dùng hoàn toàn của Bootstrap */
        /* Tôi sẽ tạm thời giữ lại để không phá vỡ các trang cũ chưa dùng Bootstrap */
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #3498db; color: white; }
        tr:hover { background: #f5f5f5; }
        
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; width: 100%; }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
    <h3>Quản trị viên</h3>
    <a href="admin.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Trang chủ</a>
    <a href="manage_customers.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_customers.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> Quản lý khách hàng</a>
    <a href="manage_role.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_role.php' ? 'active' : '' ?>"><i class="fas fa-user-shield"></i> Quản lý quyền</a>
    <a href="manage_products.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_products.php' ? 'active' : '' ?>"><i class="fas fa-box"></i> Quản lý sản phẩm</a>
    <a href="manage_orders.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_orders.php' ? 'active' : '' ?>"><i class="fas fa-shopping-cart"></i> Quản lý đơn hàng</a>
    <a href="manage_reviews.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_reviews.php' ? 'active' : '' ?>"><i class="fas fa-star"></i> Quản lý Đánh giá</a>
    <a href="chatbot_manager.php"class="<?= basename($_SERVER['PHP_SELF']) == 'chatbot_manager.php' ? 'active' : '' ?>"><i class="fas fa-robot"></i> Quản lý Chatbot</a>
    
    <!-- DÒNG MỚI ĐƯỢC THÊM VÀO ĐÂY -->
    <a href="live_chat.php" class="<?= basename($_SERVER['PHP_SELF']) == 'live_chat.php' ? 'active' : '' ?>"><i class="fas fa-headset"></i> Live Chat</a>
    
    <a href="../pages/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
</div>
        <div class="main-content">
        <!-- Nội dung của các trang con sẽ được hiển thị ở đây -->

<!-- TÍCH HỢP BOOTSTRAP JS (Cần cho các thành phần tương tác như dropdown, modal...) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
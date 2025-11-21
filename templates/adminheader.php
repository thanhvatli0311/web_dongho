<?php
// Bắt đầu phiên (hoặc kiểm tra phiên đã bắt đầu)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Bảo vệ truy cập
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../pages/login.php");
    exit;
}

// Xác định trang hiện tại để đánh dấu active trong menu
$current_page = basename($_SERVER['PHP_SELF']);

// Hàm xử lý tên trang cho Title và Header
function formatPageTitle($filename) {
    // Loại bỏ '.php' và thay thế '_' bằng khoảng trắng
    $name = str_replace(['_', '.php'], [' ', ''], $filename);
    // Viết hoa chữ cái đầu của mỗi từ
    return ucwords($name);
}

$page_title_display = formatPageTitle($current_page);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Trị - <?= $page_title_display ?></title> 
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* === PHONG CÁCH CHÍNH THỨC: CLEAN MINIMALISM & CARD-BASED DESIGN === */
        :root {
            --primary-color: #3B82F6;
            --accent-color: #10B981; /* Thêm màu Accent */
            --danger-color: #EF4444; /* Thêm màu Danger */
            --bg-body: #F3F4F6;
            --bg-card: #FFFFFF;
            --text-main: #1F2937;
            --text-muted: #6B7280;
            --border-color: #E5E7EB;
            --sidebar-width: 260px; /* Chiều rộng chuẩn */
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            margin: 0;
            overflow-x: hidden;
        }
        
        /* --- SIDEBAR --- */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--bg-card);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            padding: 24px 16px;
            z-index: 1000;
        }

        .sidebar-brand {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 12px;
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex-grow: 1;
        }

        .sidebar a {
            text-decoration: none;
            color: var(--text-muted);
            padding: 12px 16px; /* Padding chuẩn */
            border-radius: 8px;
            font-size: 14px; /* Font size chuẩn */
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
        }

        .sidebar a:hover {
            background-color: #F9FAFB;
            color: var(--text-main);
        }

        .sidebar a.active {
            background-color: #EFF6FF;
            color: var(--primary-color);
            font-weight: 600; /* Nhấn mạnh trang đang active */
        }

        .sidebar a i {
            width: 20px; /* Cố định icon width */
            text-align: center;
        }
        
        /* Nút Đăng xuất ở cuối cùng */
        .logout-btn {
            margin-top: auto;
            color: var(--danger-color) !important;
            font-weight: 500;
        }

        .logout-btn:hover {
            background-color: #FEF2F2 !important;
        }
        
        /* --- MAIN CONTENT & HEADER --- */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .top-header {
            height: 70px; /* Chiều cao chuẩn */
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .page-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-main);
            margin: 0;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #DBEAFE;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            text-transform: uppercase;
        }

        .main-content {
            padding: 32px;
            width: 100%;
        }

        /* --- CARD STYLE (Cho các trang con) --- */
        .card-clean {
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            padding: 24px;
            margin-bottom: 24px;
            transition: box-shadow 0.2s;
        }

        .card-clean:hover {
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.02);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-wrapper { margin-left: 0; }
        }
    </style>
</head>
<body>

<nav class="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-clock"></i> ADMIN PAGE
    </div>
    
    <div class="sidebar-menu">
        <a href="admin.php" class="<?= $current_page == 'admin.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i> Tổng quan
        </a>
        <a href="manage_customers.php" class="<?= $current_page == 'manage_customers.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Khách hàng
        </a>
        <a href="manage_role.php" class="<?= $current_page == 'manage_role.php' ? 'active' : '' ?>">
            <i class="fas fa-user-shield"></i> Phân quyền
        </a>
        <a href="manage_products.php" class="<?= $current_page == 'manage_products.php' ? 'active' : '' ?>">
            <i class="fas fa-box"></i> Sản phẩm
        </a>
        <a href="manage_orders.php" class="<?= $current_page == 'manage_orders.php' ? 'active' : '' ?>">
            <i class="fas fa-shopping-cart"></i> Đơn hàng
        </a>
        <a href="manage_reviews.php" class="<?= $current_page == 'manage_reviews.php' ? 'active' : '' ?>">
            <i class="fas fa-star"></i> Đánh giá
        </a>
        <a href="manage_coupons.php" class="<?= $current_page == 'manage_coupons.php' ? 'active' : '' ?>">
            <i class="fas fa-tags"></i> Khuyến mãi
        </a>
        <a href="chatbot_manager.php" class="<?= $current_page == 'chatbot_manager.php' ? 'active' : '' ?>">
            <i class="fas fa-robot"></i> Chatbot
        </a>
        <a href="live_chat.php" class="<?= $current_page == 'live_chat.php' ? 'active' : '' ?>">
            <i class="fas fa-headset"></i> Live Chat
        </a>

        <a href="../pages/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Đăng xuất
        </a>
    </div>
</nav>

<div class="main-wrapper">
    <header class="top-header">
        <h2 class="page-title"><?= $page_title_display ?></h2>
        <div class="user-profile">
            <span>Xin chào, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong></span>
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?>
            </div>
        </div>
    </header>

<div class="main-content">
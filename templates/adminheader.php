<?php
// templates/adminheader.php

// 1. Kiểm tra session (đảm bảo phiên làm việc đã bắt đầu)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Bảo vệ: Chỉ cho phép Admin truy cập
// Lưu ý: Đường dẫn header location tính từ file gọi (trong thư mục admin/)
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../pages/login.php");
    exit;
}

// 3. Xác định trang hiện tại để active menu
$current_page = basename($_SERVER['PHP_SELF']);

// 4. Hàm tạo tiêu đề trang tự động
function formatPageTitle($filename) {
    $titles = [
        'admin.php'             => 'Tổng Quan Dashboard',
        'revenue_report.php'    => 'Báo Cáo Doanh Thu',
        'manage_customers.php'  => 'Quản Lý Khách Hàng',
        'manage_products.php'   => 'Quản Lý Sản Phẩm',
        'manage_orders.php'     => 'Quản Lý Đơn Hàng',
        'manage_reviews.php'    => 'Quản Lý Đánh Giá',
        'manage_coupons.php'    => 'Quản Lý Khuyến Mãi',
        'manage_role.php'       => 'Phân Quyền Hệ Thống',
        'chatbot_manager.php'   => 'Huấn Luyện Chatbot',
        'live_chat.php'         => 'Hỗ Trợ Trực Tuyến',
        'manage_leads.php'      => 'Khách Hàng Tiềm Năng',
        'edit_product.php'      => 'Chỉnh Sửa Sản Phẩm',
        'add_product.php'       => 'Thêm Sản Phẩm Mới'
    ];
    return $titles[$filename] ?? 'Trang Quản Trị';
}

$page_title_display = formatPageTitle($current_page);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title_display ?> - CleanAdmin</title> 
    
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* === CLEAN ADMIN STYLE === */
        :root {
            --primary-color: #3B82F6;
            --accent-color: #10B981; 
            --danger-color: #EF4444; 
            --bg-body: #F3F4F6;
            --bg-card: #FFFFFF;
            --text-main: #1F2937;
            --text-muted: #6B7280;
            --border-color: #E5E7EB;
            --sidebar-width: 260px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            margin: 0;
            overflow-x: hidden;
        }
        
        /* SIDEBAR STYLE */
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
            transition: transform 0.3s ease;
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
            overflow-y: auto;
        }

        .sidebar a {
            text-decoration: none;
            color: var(--text-muted);
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
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
            font-weight: 600;
        }

        .sidebar a i { width: 20px; text-align: center; }
        
        .logout-btn {
            margin-top: auto;
            color: var(--danger-color) !important;
            font-weight: 500;
        }
        .logout-btn:hover { background-color: #FEF2F2 !important; }
        
        /* MAIN CONTENT STYLE */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease;
        }

        .top-header {
            height: 70px;
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

        .page-title { font-size: 18px; font-weight: 600; color: var(--text-main); margin: 0; }

        .user-profile { display: flex; align-items: center; gap: 12px; font-size: 14px; }
        .user-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: #DBEAFE; color: var(--primary-color);
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; text-transform: uppercase;
        }

        .main-content { padding: 32px; width: 100%; flex-grow: 1; }

        /* COMMON UI ELEMENTS */
        .card-clean {
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            padding: 24px;
            margin-bottom: 24px;
            height: 100%;
        }
        
        .table-responsive { overflow-x: auto; }
        .table-custom { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .table-custom th { text-align: left; padding: 12px; border-bottom: 2px solid var(--border-color); color: var(--text-muted); font-weight: 600; font-size: 13px; text-transform: uppercase; background: #F9FAFB; }
        .table-custom td { padding: 14px 12px; border-bottom: 1px solid var(--border-color); vertical-align: middle; font-size: 14px; }
        .table-custom tr:hover { background-color: #F9FAFB; }
        .row-total td { background-color: #EFF6FF !important; color: var(--primary-color); font-weight: 700; }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-wrapper { margin-left: 0; }
            .top-header { padding: 0 16px; }
            .main-content { padding: 16px; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR NAVIGATION -->
<nav class="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-clock"></i> ADMIN PAGE
    </div>
    
    <div class="sidebar-menu">
        <a href="admin.php" class="<?= $current_page == 'admin.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i> Tổng quan
        </a>
        
        <!-- MỚI THÊM: Link báo cáo doanh thu -->
        <a href="revenue_report.php" class="<?= $current_page == 'revenue_report.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i> Báo cáo doanh thu
        </a>

        <a href="manage_customers.php" class="<?= $current_page == 'manage_customers.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Khách hàng
        </a>
        <a href="manage_role.php" class="<?= $current_page == 'manage_role.php' ? 'active' : '' ?>">
            <i class="fas fa-user-shield"></i> Phân quyền
        </a>
        <a href="manage_products.php" class="<?= in_array($current_page, ['manage_products.php', 'add_product.php', 'edit_product.php']) ? 'active' : '' ?>">
            <i class="fas fa-box"></i> Sản phẩm
        </a>
        <a href="manage_orders.php" class="<?= in_array($current_page, ['manage_orders.php', 'order_detail.php']) ? 'active' : '' ?>">
            <i class="fas fa-shopping-cart"></i> Đơn hàng
        </a>
        <a href="manage_reviews.php" class="<?= $current_page == 'manage_reviews.php' ? 'active' : '' ?>">
            <i class="fas fa-star"></i> Đánh giá
        </a>
        <a href="manage_coupons.php" class="<?= $current_page == 'manage_coupons.php' ? 'active' : '' ?>">
            <i class="fas fa-tags"></i> Khuyến mãi
        </a>
        
        <!-- AI SECTION -->
        <div style="margin: 10px 16px; border-top: 1px solid var(--border-color);"></div>
        <small style="padding: 0 16px; color: var(--text-muted); font-size: 11px; font-weight: 600; text-transform: uppercase; margin-bottom: 5px; display:block;">Hệ thống AI</small>
        
        <a href="chatbot_manager.php" class="<?= $current_page == 'chatbot_manager.php' ? 'active' : '' ?>">
            <i class="fas fa-robot"></i> Chatbot
        </a>
        <a href="live_chat.php" class="<?= $current_page == 'live_chat.php' ? 'active' : '' ?>">
            <i class="fas fa-headset"></i> Live Chat
        </a>
        <a href="manage_leads.php" class="<?= $current_page == 'manage_leads.php' ? 'active' : '' ?>">
            <i class="fas fa-address-book"></i> Khách tiềm năng
        </a>

        <!-- LOGOUT (Trỏ ra ngoài thư mục admin -> pages) -->
        <a href="../pages/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Đăng xuất
        </a>
    </div>
</nav>

<!-- MAIN WRAPPER -->
<div class="main-wrapper">
    <!-- TOP HEADER -->
    <header class="top-header">
        <h2 class="page-title"><?= $page_title_display ?></h2>
        <div class="user-profile">
            <span>Xin chào, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong></span>
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?>
            </div>
        </div>
    </header>

    <!-- CONTENT START -->
    <div class="main-content">
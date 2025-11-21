<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/bao_cao.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../pages/login.php");
    exit;
}

$defaultStart = date('Y-m-d', strtotime('-6 days'));
$defaultEnd = date('Y-m-d');

$startDate = $_GET['start'] ?? $defaultStart;
$endDate = $_GET['end'] ?? $defaultEnd;

if (strtotime($startDate) > strtotime($endDate)) {
    [$startDate, $endDate] = [$endDate, $startDate];
}

$chartData = getRevenueChartData($pdo, $startDate, $endDate);
$topProducts = getTopProducts($pdo, 3, $startDate, $endDate) ?? [];

$total_orders = (int)($pdo->query("SELECT COUNT(*) FROM tbdonhang")->fetchColumn() ?? 0);
$total_customers = (int)($pdo->query("SELECT COUNT(*) FROM tbkhachhang")->fetchColumn() ?? 0);

$statusData = getOrderStatusStats($pdo);
$statusLabels = array_column($statusData, 'tinhtrang');
$statusCounts = array_column($statusData, 'total_count');

$jsonRevenue = json_encode([
    'labels' => $chartData['labels'],
    'data' => $chartData['data']
]);
$jsonStatusLabels = json_encode($statusLabels);
$jsonStatusCounts = json_encode($statusCounts);

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - CleanAdmin Pro</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
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
    padding: 0 12px;
    display: flex;
    align-items: center;
    gap: 10px;
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

.logout-btn {
    margin-top: auto;
    color: var(--danger-color) !important;
}

.logout-btn:hover {
    background-color: #FEF2F2 !important;
    color: var(--danger-color) !important;
}

.main-wrapper {
    margin-left: var(--sidebar-width);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
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

.page-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 36px;
    height: 36px;
    background: #DBEAFE;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
    font-weight: 600;
    text-transform: uppercase;
}

.main-content {
    padding: 32px;
    width: 100%;
}

.card-clean {
    background: var(--bg-card);
    border-radius: 12px;
    border: 1px solid var(--border-color);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    padding: 24px;
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: box-shadow 0.2s;
    min-height: 0;
}

.card-clean:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.02);
}

.stat-card {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
}

.stat-content h6 {
    color: var(--text-muted);
    font-size: 14px;
    margin-bottom: 8px;
    font-weight: 500;
}

.stat-content h3 {
    font-size: 26px;
    font-weight: 700;
    margin: 0;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}

.bg-blue-light {
    background: #DBEAFE;
    color: var(--primary-color);
}
.bg-yellow-light {
    background: #FFFBEB;
    color: #F59E0B;
}
.bg-green-light {
    background: #D1FAE5;
    color: var(--accent-color);
}

.product-table th {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-muted);
    padding-top: 0;
    padding-bottom: 12px !important;
    border-bottom: 2px solid var(--border-color) !important;
}
.product-table td {
    padding: 12px 0;
    vertical-align: middle;
    border-bottom: 1px solid #F3F4F6;
    font-size: 14px;
}
.product-table tr:last-child td {
    border-bottom: none;
}
.product-img-small {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    object-fit: cover;
    background: #F3F4F6;
    border: 1px solid #E5E7EB;
}
.table-responsive {
    flex-grow: 1;
    overflow-y: auto;
}
.product-table {
    margin-bottom: 0;
}

.filter-bar {
    display: flex;
    gap: 12px;
    align-items: center;
}

@media (min-width: 992px) {
    .row.g-4 {
        align-items: stretch;
    }
    .col-lg-4 .card-clean {
        height: 100%;
    }
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    .main-wrapper {
        margin-left: 0;
    }
    .top-header {
        padding: 0 16px;
    }
    .main-content {
        padding: 16px;
    }
    .filter-bar {
        flex-direction: column;
        align-items: flex-start;
        width: 100%;
    }
    .filter-bar > div {
        width: 100%;
        display: flex;
        justify-content: space-between;
    }
}
</style>
</head>

<body>
<nav class="sidebar">
    <div class="sidebar-brand"><i class="fas fa-clock"></i> ADMIN PAGE</div>
    <div class="sidebar-menu">
        <a href="admin.php" class="<?= $current_page == 'admin.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Tổng quan</a>
        <a href="manage_customers.php" class="<?= $current_page == 'manage_customers.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> Khách hàng</a>
        <a href="manage_role.php" class="<?= $current_page == 'manage_role.php' ? 'active' : '' ?>"><i class="fas fa-user-shield"></i> Phân quyền</a>
        <a href="manage_products.php" class="<?= $current_page == 'manage_products.php' ? 'active' : '' ?>"><i class="fas fa-box"></i> Sản phẩm</a>
        <a href="manage_orders.php" class="<?= $current_page == 'manage_orders.php' ? 'active' : '' ?>"><i class="fas fa-shopping-cart"></i> Đơn hàng</a>
        <a href="manage_reviews.php" class="<?= $current_page == 'manage_reviews.php' ? 'active' : '' ?>"><i class="fas fa-star"></i> Đánh giá</a>
        <a href="manage_coupons.php" class="<?= $current_page == 'manage_coupons.php' ? 'active' : '' ?>"><i class="fas fa-tags"></i> Khuyến mãi</a>
        <a href="chatbot_manager.php" class="<?= $current_page == 'chatbot_manager.php' ? 'active' : '' ?>"><i class="fas fa-robot"></i> Chatbot</a>
        <a href="live_chat.php" class="<?= $current_page == 'live_chat.php' ? 'active' : '' ?>"><i class="fas fa-headset"></i> Live Chat</a>
        <a href="../pages/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
    </div>
</nav>

<div class="main-wrapper">
<header class="top-header">
    <h2 class="page-title">Dashboard</h2>
    <div class="user-profile">
        <span>Xin chào, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong></span>
        <div class="user-avatar">
            <?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?>
        </div>
    </div>
</header>
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h3 class="m-0">Thống kê kinh doanh</h3>
        <form method="GET" action="" class="filter-bar">
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted fw-bold">Từ:</label>
                <input type="date" name="start" class="form-control form-control-sm" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted fw-bold">Đến:</label>
                <input type="date" name="end" class="form-control form-control-sm" value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Lọc</button>
            <a href="../pages/export_excel.php?start=<?= $startDate ?>&end=<?= $endDate ?>" class="btn btn-success btn-sm text-white"><i class="fas fa-file-excel"></i> Xuất Excel</a>
        </form>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card-clean stat-card">
                <div class="stat-content">
                    <h6>Doanh thu (<?= date('d/m', strtotime($startDate)) ?> - <?= date('d/m', strtotime($endDate)) ?>)</h6>
                    <h3><?= number_format($chartData['total'] ?? 0, 0, ',', '.') ?> đ</h3>
                </div>
                <div class="stat-icon bg-blue-light"><i class="fas fa-dollar-sign"></i></div>
            </div>
        </div>
        <div class="col-md-4">
            <a href="manage_orders.php" style="text-decoration:none;">
                <div class="card-clean stat-card">
                    <div class="stat-content">
                        <h6>Tổng đơn hàng</h6>
                        <h3><?= $total_orders ?></h3>
                    </div>
                    <div class="stat-icon bg-green-light"><i class="fas fa-shopping-cart"></i></div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="manage_customers.php" style="text-decoration:none;">
                <div class="card-clean stat-card">
                    <div class="stat-content">
                        <h6>Tổng khách hàng</h6>
                        <h3><?= $total_customers ?></h3>
                    </div>
                    <div class="stat-icon bg-yellow-light"><i class="fas fa-users"></i></div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8 col-md-12">
            <div class="card-clean">
                <h5 class="mb-3">Biểu đồ doanh thu theo thời gian</h5>
                <div style="height:350px;"><canvas id="weeklyRevenueChart"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card-clean h-100">
                <h5>Trạng thái đơn hàng</h5>
                <div style="height: 350px; display:flex; justify-content:center; align-items:center;">
                    <canvas id="orderStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-12">
            <div class="card-clean">
                <h5>Top 3 Bán chạy</h5>
                <div class="table-responsive">
                    <table class="table table-borderless product-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;">Mã</th>
                                <th style="width: 5%;">Ảnh</th>
                                <th style="width: 30%;">Sản phẩm</th>
                                <th class="text-end" style="width: 15%;">SL bán</th>
                                <th class="text-end" style="width: 15%;">Doanh thu</th>
                                <th style="width: 30%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProducts as $p): ?>
                            <tr>
                                <td class="small text-muted"><?= htmlspecialchars($p['mahang'] ?? 'N/A') ?></td>
                                <td>
                                    <img src="../assets/images/<?= htmlspecialchars($p['hinhanh'] ?? 'default.png') ?>" onerror="this.src='https://via.placeholder.com/32'" class="product-img-small">
                                </td>
                                <td><?= htmlspecialchars($p['tensanpham'] ?? 'Sản phẩm lỗi') ?></td>
                                <td class="text-end fw-bold"><?= $p['total_qty'] ?? 0 ?></td>
                                <td class="text-end text-primary fw-bold"><?= number_format($p['total_revenue'] ?? 0, 0, ',', '.') ?>đ</td>
                                <td></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($topProducts)): ?>
                            <tr>
                                <td colspan="6" class="text-muted text-center py-4">Chưa có dữ liệu</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const revenue = <?= $jsonRevenue ?>;
const statusLabels = <?= $jsonStatusLabels ?>;
const statusCounts = <?= $jsonStatusCounts ?>;

Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = "#6B7280";

new Chart(document.getElementById("weeklyRevenueChart"), {
    type: "line",
    data: {
        labels: revenue.labels,
        datasets: [{
            label: "Doanh thu",
            data: revenue.data,
            borderColor: "#3B82F6",
            backgroundColor: "rgba(59,130,246,0.1)",
            borderWidth: 2,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: "#FFFFFF",
            pointBorderColor: "#3B82F6",
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    borderDash: [5, 5],
                    color: "#E5E7EB"
                },
                ticks: {
                    callback: (value) => {
                        if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                        if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
                        return value;
                    }
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        },
        tooltips: {
            callbacks: {
                label: function(tooltipItem, data) {
                    let value = tooltipItem.raw;
                    return 'Doanh thu: ' + value.toLocaleString('vi-VN') + ' đ';
                }
            }
        }
    }
});

new Chart(document.getElementById("orderStatusChart"), {
    type: "doughnut",
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusCounts,
            backgroundColor: [
                "#3B82F6",
                "#F59E0B",
                "#10B981",
                "#EF4444",
                "#8B5CF6"
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: "bottom",
                labels: {
                    usePointStyle: true,
                    boxWidth: 8
                }
            }
        }
    }
});
</script>

</body>
</html>
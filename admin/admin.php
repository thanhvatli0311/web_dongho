<?php
session_start();
// 1. Kết nối CSDL và thư viện
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/bao_cao.php';

// 2. Kiểm tra quyền Admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../pages/login.php");
    exit;
}

// 3. Xử lý Logic lấy dữ liệu (Giữ nguyên code của bạn)
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

// 4. GỌI GIAO DIỆN HEADER (Chứa Menu, Sidebar, CSS, DOCTYPE)
// File này sẽ tự động mở thẻ <html>, <body>, <nav sidebar>, <header> và <div class="main-content">
require __DIR__ . '/../templates/adminheader.php';
?>

<!-- BẮT ĐẦU NỘI DUNG DASHBOARD -->
<!-- Không cần mở <div class="main-content"> vì header đã mở rồi -->

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <h3 class="m-0">Thống kê kinh doanh</h3>
    <form method="GET" action="" class="d-flex gap-2 align-items-center">
        <div class="input-group input-group-sm">
            <span class="input-group-text">Từ</span>
            <input type="date" name="start" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
        </div>
        <div class="input-group input-group-sm">
            <span class="input-group-text">Đến</span>
            <input type="date" name="end" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Lọc</button>
        <a href="export_excel.php?start=<?= $startDate ?>&end=<?= $endDate ?>" class="btn btn-success btn-sm text-white"><i class="fas fa-file-excel"></i> Xuất Excel</a>
    </form>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card-clean h-100 d-flex flex-row align-items-center justify-content-between">
            <div>
                <h6 class="text-muted mb-2">Doanh thu (<?= date('d/m', strtotime($startDate)) ?> - <?= date('d/m', strtotime($endDate)) ?>)</h6>
                <h3 class="mb-0 fw-bold"><?= number_format($chartData['total'] ?? 0, 0, ',', '.') ?> đ</h3>
            </div>
            <div style="width: 50px; height: 50px; background: #DBEAFE; color: #3B82F6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <a href="manage_orders.php" style="text-decoration:none; color: inherit;">
            <div class="card-clean h-100 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted mb-2">Tổng đơn hàng</h6>
                    <h3 class="mb-0 fw-bold"><?= $total_orders ?></h3>
                </div>
                <div style="width: 50px; height: 50px; background: #D1FAE5; color: #10B981; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="manage_customers.php" style="text-decoration:none; color: inherit;">
            <div class="card-clean h-100 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted mb-2">Tổng khách hàng</h6>
                    <h3 class="mb-0 fw-bold"><?= $total_customers ?></h3>
                </div>
                <div style="width: 50px; height: 50px; background: #FFFBEB; color: #F59E0B; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    <i class="fas fa-users"></i>
                </div>
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
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th style="width: 10%;">Mã</th>
                            <th style="width: 10%;">Ảnh</th>
                            <th style="width: 35%;">Sản phẩm</th>
                            <th class="text-end" style="width: 15%;">SL bán</th>
                            <th class="text-end" style="width: 20%;">Doanh thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topProducts as $p): ?>
                        <tr>
                            <td class="small text-muted"><?= htmlspecialchars($p['mahang'] ?? 'N/A') ?></td>
                            <td>
                                <img src="../assets/images/<?= htmlspecialchars($p['hinhanh'] ?? 'default.png') ?>" 
                                     onerror="this.src='https://via.placeholder.com/32'" 
                                     style="width: 32px; height: 32px; border-radius: 6px; object-fit: cover; border: 1px solid #E5E7EB;">
                            </td>
                            <td><?= htmlspecialchars($p['tensanpham'] ?? 'Sản phẩm lỗi') ?></td>
                            <td class="text-end fw-bold"><?= $p['total_qty'] ?? 0 ?></td>
                            <td class="text-end text-primary fw-bold"><?= number_format($p['total_revenue'] ?? 0, 0, ',', '.') ?>đ</td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($topProducts)): ?>
                        <tr>
                            <td colspan="5" class="text-muted text-center py-4">Chưa có dữ liệu</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ĐÓNG CÁC THẺ DIV MÀ HEADER ĐÃ MỞ -->
</div> <!-- End main-content -->
</div> <!-- End main-wrapper -->

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Dữ liệu từ PHP
const revenue = <?= $jsonRevenue ?>;
const statusLabels = <?= $jsonStatusLabels ?>;
const statusCounts = <?= $jsonStatusCounts ?>;

// Cấu hình chung Chart
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = "#6B7280";

// 1. Biểu đồ Line Doanh thu
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
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                grid: { borderDash: [5, 5], color: "#E5E7EB" },
                ticks: {
                    callback: (value) => {
                        if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                        if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
                        return value;
                    }
                }
            },
            x: { grid: { display: false } }
        },
        tooltips: {
            callbacks: {
                label: function(tooltipItem) {
                    return 'Doanh thu: ' + tooltipItem.raw.toLocaleString('vi-VN') + ' đ';
                }
            }
        }
    }
});

// 2. Biểu đồ Doughnut Trạng thái
new Chart(document.getElementById("orderStatusChart"), {
    type: "doughnut",
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusCounts,
            backgroundColor: [ "#3B82F6", "#F59E0B", "#10B981", "#EF4444", "#8B5CF6" ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: "bottom", labels: { usePointStyle: true, boxWidth: 8 } }
        }
    }
});
</script>

</body>
</html>
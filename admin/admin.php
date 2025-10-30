<?php 
session_start();
include '../includes/db.php';
include '../templates/adminheader.php';
// Đảm bảo file logic được include sau khi db được include
include 'bao_cao.php';

// Kiểm tra nếu chưa đăng nhập hoặc không phải admin thì chuyển hướng
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

// Truy vấn tổng số đơn hàng
$sql_orders = "SELECT COUNT(*) as total_orders FROM tbdonhang";
$result_orders = $conn->query($sql_orders);
$row_orders = $result_orders->fetch_assoc();
$total_orders = $row_orders['total_orders'];

// Truy vấn tổng số khách hàng đã đăng ký tài khoản
$sql_customers = "SELECT COUNT(*) as total_customers FROM tbkhachhang";
$result_customers = $conn->query($sql_customers);
$row_customers = $result_customers->fetch_assoc();
$total_customers = $row_customers['total_customers'];

// Truy vấn tổng doanh thu từ những đơn hàng có trạng thái 'Đã giao'
$sql_revenue = "SELECT SUM(ct.soluong * ct.dongia) as total_revenue 
                 FROM tbdonhang AS dh 
                 INNER JOIN tbchitietdonhang AS ct ON dh.madonhang = ct.madonhang 
                 WHERE dh.tinhtrang = 'Đã giao'";
$result_revenue = $conn->query($sql_revenue);
$row_revenue = $result_revenue->fetch_assoc();
$total_revenue = $row_revenue['total_revenue'];
if (!$total_revenue) {
    $total_revenue = 0;
}
$status_data = getOrderStatusStats($conn);
$revenue_data = getWeeklyRevenue($conn);

// Chuyển đổi dữ liệu PHP sang định dạng JSON để JS có thể sử dụng
$status_json = json_encode($status_data);
$revenue_json = json_encode(array_values($revenue_data)); // Chỉ lấy giá trị doanh thu
$revenue_labels_json = json_encode(array_keys($revenue_data)); // Lấy nhãn ngày
?>

<head>
    <meta charset="UTF-8">
    <title>Tổng Quan Admin</title>
    <!-- Thêm CDN Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Thêm CDN Font Awesome cho các icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<!-- CSS tùy chỉnh -->
<style>
    h2{
        margin-bottom: 20px;
        color: #2c3e50;
        font-weight: 600;
        border-bottom: 2px solid #e1e8ed;
        padding-bottom: 10px;
    }
    .custom-card {
        border: none;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        border-radius: 10px;
        transition: transform 0.2s ease;
        margin-bottom: 30px;
    }
    .custom-card:hover {
        transform: translateY(-5px);
    }
    .icon-style {
        font-size: 2rem;
        color: #007bff;
        margin-right: 10px;
    }
    .card-title {
        font-weight: bold;
    }
    /* Loại bỏ gạch chân của thẻ a và cho hiển thị block */
    .card-link {
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .display-4 {
        color: #34495e; /* Màu chữ đậm hơn cho số liệu */
        font-weight: 700;
    }
</style>
<h2 class="text-center my-4">Tổng quan</h2>

<div class="container mt-5">
    <!-- Hàng hiển thị Tổng số đơn hàng và Tổng số khách hàng -->
    <div class="row">
        <!-- Tổng số đơn hàng -->
        <div class="col-md-6">
            <a href="manage_orders.php" class="card-link">
                <div class="card text-center custom-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-shopping-cart icon-style" style="color: #28a745;"></i> Tổng số đơn hàng
                        </h5>
                        <p class="card-text display-4"><?php echo $total_orders; ?></p>
                    </div>
                </div>
            </a>
        </div>
        <!-- Tổng số khách hàng -->
        <div class="col-md-6">
            <a href="manage_customers.php" class="card-link">
                <div class="card text-center custom-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-users icon-style" style="color: #ffc107;"></i> Tổng số khách hàng
                        </h5>
                        <p class="card-text display-4"><?php echo $total_customers; ?></p>
                    </div>
                </div>
            </a>
        </div>
    </div>
    
    <div class="container mt-4">
        <h2>Tổng quan Báo cáo Kinh doanh</h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Tỷ lệ Trạng thái Đơn hàng</div>
                    <div class="card-body">
                        <canvas id="orderStatusChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Doanh thu 7 ngày gần nhất</div>
                    <div class="card-body">
                        <canvas id="weeklyRevenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- HÀNG MỚI CHỨA TỔNG DOANH THU (ĐƯỢC DI CHUYỂN XUỐNG DƯỚI CÙNG) -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card text-center custom-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-dollar-sign icon-style" style="color: #007bff;"></i> Tổng doanh thu
                    </h5>
                    <p class="card-text display-4">
                        <?php echo number_format($total_revenue, 0, ',', '.'); ?> VNĐ
                    </p>
                </div>
            </div>
        </div>
    </div>
    
</div>
<script>
    // Lấy dữ liệu từ PHP (đã được encode thành JSON)
    const statusData = <?= $status_json ?>;
    const revenueValues = <?= $revenue_json ?>;
    const revenueLabels = <?= $revenue_labels_json ?>;

    // 1. VẼ BIỂU ĐỒ TRẠNG THÁI ĐƠN HÀNG (Doughnut Chart)
    const statusLabels = statusData.map(item => item.status);
    const statusCounts = statusData.map(item => item.count);
    
    new Chart(document.getElementById('orderStatusChart'), {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusCounts,
                backgroundColor: ['#007bff', '#ffc107', '#28a745', '#dc3545'] // Blue, Yellow, Green, Red (Bootstrap colors)
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                title: { display: false }
            }
        }
    });

    // 2. VẼ BIỂU ĐỒ DOANH THU HÀNG TUẦN (Line Chart)
    new Chart(document.getElementById('weeklyRevenueChart'), {
        type: 'line',
        data: {
            labels: revenueLabels,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: revenueValues,
                borderColor: '#007bff', // Màu xanh Primary
                tension: 0.3, // Đường cong mềm mại hơn
                fill: false,
                borderWidth: 2,
                pointBackgroundColor: '#007bff'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { 
                    beginAtZero: true,
                    ticks: {
                        // Định dạng tiền tệ cho trục Y
                        callback: function(value, index, values) {
                            // Sử dụng hàm toLocaleString để định dạng số lớn
                            return value.toLocaleString('vi-VN');
                        }
                    }
                },
                x: {
                    // Cải thiện hiển thị ngày tháng trên trục X
                    ticks: {
                        callback: function(value, index, values) {
                            // Chuyển đổi từ YYYY-MM-DD sang DD/MM
                            let dateStr = revenueLabels[index];
                            if (dateStr) {
                                let dateParts = dateStr.split('-');
                                return dateParts[2] + '/' + dateParts[1];
                            }
                            return '';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                // Định dạng tiền tệ trong tooltip
                                label += context.parsed.y.toLocaleString('vi-VN') + ' VNĐ';
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
</script>
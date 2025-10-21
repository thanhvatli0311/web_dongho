<?php
session_start();

// Kiểm tra quyền truy cập (middleware - ví dụ đơn giản)
// Bạn có thể có một file `auth_middleware.php` trong `includes/`
// để thực hiện kiểm tra đăng nhập và vai trò người dùng phức tạp hơn.
// Hiện tại, chúng ta giả định người dùng đã đăng nhập và là admin.
// if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
//     header("Location: login.php"); // Chuyển hướng về trang đăng nhập admin
//     exit();
// }

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php'; // Đảm bảo file này tồn tại và có các hàm cần thiết

$db = new Database();

// === BẮT ĐẦU LOGIC XỬ LÝ CHO DASHBOARD ===

// Hàm để lấy một giá trị duy nhất từ CSDL (nếu chưa có trong class Database)
// (Bạn nên thêm hàm này vào class `Database` trong `includes/database.php`)
/*
class Database {
    // ... các thuộc tính và phương thức khác ...

    // Thêm phương thức này vào class Database của bạn
    public function singleColumn() {
        if ($this->stmt) {
            return $this->stmt->fetchColumn();
        }
        return null;
    }
}
*/

// Lấy số liệu thống kê
try {
    // Tổng sản phẩm
    $db->query("SELECT COUNT(*) FROM products");
    $totalProducts = $db->singleColumn();
    $totalProducts = $totalProducts ?? 0; // Đảm bảo giá trị là 0 nếu không có sản phẩm nào

    // Tổng người dùng
    $db->query("SELECT COUNT(*) FROM users");
    $totalUsers = $db->singleColumn();
    $totalUsers = $totalUsers ?? 0;

    // Tổng đơn hàng
    $db->query("SELECT COUNT(*) FROM orders");
    $totalOrders = $db->singleColumn();
    $totalOrders = $totalOrders ?? 0;

    // Tổng doanh thu (ví dụ: chỉ tính các đơn hàng đã hoàn thành)
    // Giả định có cột `order_status` trong bảng `orders`
    $db->query("SELECT SUM(total_amount) FROM orders WHERE order_status = 'completed'");
    $totalRevenue = $db->singleColumn();
    $totalRevenue = $totalRevenue ?? 0;

} catch (Exception $e) {
    error_log("Lỗi lấy dữ liệu dashboard: " . $e->getMessage());
    $totalProducts = $totalUsers = $totalOrders = $totalRevenue = "Lỗi";
    // Có thể hiển thị thông báo lỗi thân thiện hơn cho người dùng
}

// === KẾT THÚC LOGIC XỬ LÝ CHO DASHBOARD ===


// ===========================================
// Bắt đầu include các phần của giao diện
// ===========================================
include 'partials/header.php'; // Bao gồm phần header của admin
include 'partials/sidebar.php'; // Bao gồm thanh sidebar của admin
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Dashboard</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Trang Chủ</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- Small boxes (Stat box) -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <!-- small box -->
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?php echo $totalProducts; ?></h3>
                            <p>Tổng sản phẩm</p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-bag"></i> <!-- Icon ví dụ, cần thư viện icon như FontAwesome -->
                        </div>
                        <a href="products.php" class="small-box-footer">Xem thêm <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <!-- ./col -->
                <div class="col-lg-3 col-6">
                    <!-- small box -->
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?php echo $totalUsers; ?></h3>
                            <p>Tổng người dùng</p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-stats-bars"></i> <!-- Icon ví dụ -->
                        </div>
                        <a href="users.php" class="small-box-footer">Xem thêm <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <!-- ./col -->
                <div class="col-lg-3 col-6">
                    <!-- small box -->
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?php echo $totalOrders; ?></h3>
                            <p>Tổng đơn hàng</p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-person-add"></i> <!-- Icon ví dụ -->
                        </div>
                        <a href="orders.php" class="small-box-footer">Xem thêm <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <!-- ./col -->
                <div class="col-lg-3 col-6">
                    <!-- small box -->
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?php
                                if (is_numeric($totalRevenue)) { // Kiểm tra nếu $totalRevenue là một số
                                    echo number_format($totalRevenue);
                                } else {
                                    echo $totalRevenue; // Nếu không phải số (ví dụ: "Lỗi"), hiển thị nguyên chuỗi
                                }
                            ?> VNĐ</h3>
                            <p>Tổng doanh thu (đã giao)</p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-pie-graph"></i> <!-- Icon ví dụ -->
                        </div>
                        <a href="orders.php" class="small-box-footer">Xem thêm <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <!-- ./col -->
            </div>
            <!-- /.row -->

            <!-- Thêm các widget hoặc biểu đồ khác tại đây -->

        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php
include 'partials/footer.php'; // Bao gồm phần footer của admin
?>
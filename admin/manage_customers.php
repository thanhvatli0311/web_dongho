<?php
session_start();
require __DIR__ . '/../includes/db.php';
// Gọi Header chuẩn (đã bao gồm CSS, Sidebar)
require __DIR__ . '/../templates/adminheader.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../pages/login.php");
    exit;
}

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Cấu hình phân trang
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Xây dựng câu truy vấn động
$where = 'WHERE 1=1';
$params = [];

if (!empty($search)) {
    // Sửa lại tên cột thành 'sodienthoai' cho khớp với DB của bạn
    $where .= " AND (tenkhach LIKE ? OR sodienthoai LIKE ? OR diachi LIKE ? OR makhach LIKE ?)";
    $likeSearch = "%" . $search . "%";
    // Thêm 4 tham số cho 4 dấu ? ở trên
    $params = [$likeSearch, $likeSearch, $likeSearch, $likeSearch];
}

// 1. Đếm tổng số bản ghi
$total_customers = 0;
try {
    $sql_count = "SELECT COUNT(*) FROM tbkhachhang {$where}";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params);
    $total_customers = $stmt_count->fetchColumn();
} catch (PDOException $e) {
    echo '<div class="alert alert-danger m-3">Lỗi đếm số lượng: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

$total_pages = ceil($total_customers / $limit);

// 2. Lấy dữ liệu hiển thị
$customers = [];
// Lưu ý: Nếu DB của bạn không có cột 'ngaysinh' hay 'gioitinh', hãy xóa chúng khỏi dòng SELECT dưới đây
$sql_data = "SELECT makhach, tenkhach, ngaysinh, sodienthoai, diachi, gioitinh, username 
             FROM tbkhachhang 
             {$where}
             ORDER BY makhach DESC
             LIMIT ? OFFSET ?"; // Dùng dấu ? cho Limit/Offset để tránh lỗi mixed parameters

try {
    $stmt = $pdo->prepare($sql_data);

    // Bind các tham số tìm kiếm (index bắt đầu từ 1)
    $paramIndex = 1;
    foreach ($params as $paramValue) {
        $stmt->bindValue($paramIndex++, $paramValue, PDO::PARAM_STR);
    }

    // Bind Limit và Offset vào các dấu ? cuối cùng
    $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);

    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Hiển thị lỗi rõ ràng nếu sai tên cột khác
    echo '<div class="alert alert-danger m-3">Lỗi truy vấn dữ liệu: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Chuyển hướng nếu trang hiện tại lớn hơn tổng số trang
if ($page > $total_pages && $total_pages > 0) {
    // header("Location: manage_customers.php?page={$total_pages}&search=" . urlencode($search));
    // exit;
}
?>

<!-- NỘI DUNG CHÍNH (Nằm trong .main-content do header mở sẵn) -->
<div class="card-clean">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="m-0 fw-bold text-primary"><i class="fas fa-users me-2"></i>Quản lý khách hàng</h5>
    </div>

    <!-- Form tìm kiếm -->
    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control form-control-clean"
                placeholder="Tìm tên, SĐT, địa chỉ, mã khách..."
                value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Tìm</button>
            <?php if(!empty($search)): ?>
                <a href="manage_customers.php" class="btn btn-light border"><i class="fas fa-sync-alt"></i> Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Thông báo Session -->
    <?php if (isset($_SESSION['message'])) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars(is_array($_SESSION['message']) ? $_SESSION['message']['content'] : $_SESSION['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- Bảng dữ liệu -->
    <div class="table-responsive">
        <table class="table table-custom table-hover">
            <thead>
                <tr>
                    <th>Mã KH</th>
                    <th>Tên khách hàng</th>
                    <th>Ngày sinh</th>
                    <th>Số điện thoại</th>
                    <th>Địa chỉ</th>
                    <th>Giới tính</th>
                    <th class="text-center">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($customers)) : ?>
                    <?php foreach ($customers as $row) : ?>
                        <tr>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['makhach']) ?></span></td>
                            <td class="fw-medium"><?= htmlspecialchars($row['tenkhach']) ?></td>
                            <td>
                                <?php 
                                    // Kiểm tra null hoặc ngày mặc định
                                    if (!empty($row['ngaysinh']) && $row['ngaysinh'] !== '0000-00-00') {
                                        echo date('d/m/Y', strtotime($row['ngaysinh']));
                                    } else {
                                        echo '<span class="text-muted small">-</span>';
                                    }
                                ?>
                            </td>
                            <td><?= htmlspecialchars($row['sodienthoai']) ?></td>
                            <td><span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?= htmlspecialchars($row['diachi']) ?>"><?= htmlspecialchars($row['diachi']) ?></span></td>
                            <td><?= htmlspecialchars($row['gioitinh']) ?></td>
                            <td class="text-center">
                                <a href="edit_customer.php?id=<?= urlencode($row['makhach']) ?>"
                                   class="btn btn-sm btn-warning text-dark fw-bold">
                                   <i class="fas fa-edit"></i> Sửa
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-user-slash fa-2x mb-3"></i><br>
                            Không tìm thấy khách hàng nào phù hợp.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center mt-4">
            <!-- Nút Previous -->
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>

            <!-- Hiển thị số trang -->
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);

            if ($start_page > 1) { echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; }

            for ($i = $start_page; $i <= $end_page; $i++) : ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                </li>
            <?php endfor;

            if ($end_page < $total_pages) { echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; }
            ?>

            <!-- Nút Next -->
            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- Đóng thẻ div do header mở -->
    </div> <!-- End .main-content -->
</div> <!-- End .main-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
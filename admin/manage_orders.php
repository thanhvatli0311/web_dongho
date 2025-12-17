<?php
session_start();
// Lưu ý: Đường dẫn này dựa trên cấu trúc admin/ -> includes/db.php
require __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    // Điều hướng Login phải luôn ở trên cùng
    header("Location: ../pages/login.php");
    exit;
}
if (!isset($pdo)) {
    die("Lỗi: Không thể kết nối CSDL.");
}

// 1. XỬ LÝ ĐIỀU HƯỚNG/HEADER PHẢI Ở ĐẦU FILE
// Xử lý xóa đơn hàng
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $madonhang_to_delete = $_GET['id'];
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM tbChiTietDonHang WHERE madonhang = ?")->execute([$madonhang_to_delete]);
        $pdo->prepare("DELETE FROM tbDonHang WHERE madonhang = ?")->execute([$madonhang_to_delete]);
        $pdo->commit();
        $_SESSION['message'] = ['type' => 'success', 'content' => 'Đã xóa đơn hàng thành công.'];
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['message'] = ['type' => 'danger', 'content' => 'Lỗi khi xóa đơn hàng: ' . $e->getMessage()];
    }
    header("Location: manage_orders.php");
    exit;
}

// Xử lý cập nhật trạng thái đơn hàng
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['update_status'])) {
    $madonhang = $_POST['madonhang'];
    $tinhtrang = $_POST['tinhtrang'];
    try {
        $update_query = $pdo->prepare("UPDATE tbDonHang SET tinhtrang = ? WHERE madonhang = ?");
        $update_query->execute([$tinhtrang, $madonhang]);
        $_SESSION['message'] = ['type' => 'success', 'content' => 'Cập nhật trạng thái thành công.'];
    } catch (PDOException $e) {
        $_SESSION['message'] = ['type' => 'danger', 'content' => 'Lỗi cập nhật: ' . $e->getMessage()];
    }
    header("Location: manage_orders.php");
    exit;
}

// 2. INCLUDE HEADER SAU KHI ĐÃ XỬ LÝ XONG CÁC HÀM ĐIỀU HƯỚNG (header())
// Đường dẫn này dựa trên cấu trúc admin/ -> templates/adminheader.php
require __DIR__ . '/../templates/adminheader.php'; 

// Lấy các tham số tìm kiếm và lọc
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Xây dựng câu lệnh WHERE dựa trên các tham số
$where_clauses = [];
$params = [];
if ($search !== '') {
    $where_clauses[] = "(tbDonHang.madonhang LIKE ? OR tbkhachhang.tenkhach LIKE ? OR tbkhachhang.sodienthoai LIKE ?)";
    $search_param = "%{$search}%";
    array_push($params, $search_param, $search_param, $search_param);
}
if ($filter !== '') {
    if ($filter == 'today') $where_clauses[] = "DATE(tbDonHang.ngaymua) = CURDATE()";
    elseif ($filter == 'yesterday') $where_clauses[] = "DATE(tbDonHang.ngaymua) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    elseif ($filter == 'this_week') $where_clauses[] = "YEARWEEK(tbDonHang.ngaymua, 1) = YEARWEEK(CURDATE(), 1)";
    elseif ($filter == 'this_month') $where_clauses[] = "MONTH(tbDonHang.ngaymua) = MONTH(CURDATE()) AND YEAR(tbDonHang.ngaymua) = YEAR(CURDATE())";
}
if ($date_from !== '' && $date_to !== '') {
    $where_clauses[] = "DATE(tbDonHang.ngaymua) BETWEEN ? AND ?";
    array_push($params, $date_from, $date_to);
}
$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(' AND ', $where_clauses) : "";

// Phân trang
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số đơn hàng
$count_sql = "SELECT COUNT(*) FROM tbDonHang JOIN tbkhachhang ON tbDonHang.makhach = tbkhachhang.makhach {$where_sql}";
$stmt_count = $pdo->prepare($count_sql);
$stmt_count->execute($params);
$total = $stmt_count->fetchColumn();
$total_pages = ceil($total / $limit);

// Truy vấn lấy danh sách đơn hàng
$sql = "SELECT 
            tbDonHang.madonhang, tbDonHang.ngaymua, tbDonHang.tinhtrang, tbDonHang.phuongthuctt, 
            tbDonHang.tongtiendonhang, tbDonHang.makhuyenmai, 
            tbkhachhang.tenkhach, tbkhachhang.sodienthoai,
            tbkhuyenmai.tenkhuyenmai, tbkhuyenmai.loai, tbkhuyenmai.giatri
        FROM tbDonHang 
        JOIN tbkhachhang ON tbDonHang.makhach = tbkhachhang.makhach 
        LEFT JOIN tbkhuyenmai ON tbDonHang.makhuyenmai = tbkhuyenmai.makhuyenmai 
        {$where_sql} 
        ORDER BY tbDonHang.ngaymua DESC 
        LIMIT {$limit} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message']['type']; ?> alert-dismissible fade show card-clean p-3 mb-4" role="alert">
            <?php echo htmlspecialchars($_SESSION['message']['content']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <div class="card-clean mb-4">
        <h5 style="font-weight: 600; color: var(--text-main); margin-bottom: 15px;">🔍 Bộ lọc & tìm kiếm</h5>
        <form method="GET" class="row g-3">
            <div class="col-md-12"><input type="text" name="search" class="form-control" placeholder="Tìm Mã ĐH, Tên KH, SĐT..." value="<?php echo htmlspecialchars($search); ?>"></div>
            <div class="col-md-12">
                <select name="filter" class="form-select">
                    <option value="">Lọc nhanh...</option>
                    <option value="today" <?php if($filter == 'today') echo 'selected'; ?>>Hôm nay</option>
                    <option value="yesterday" <?php if($filter == 'yesterday') echo 'selected'; ?>>Hôm qua</option>
                    <option value="this_week" <?php if($filter == 'this_week') echo 'selected'; ?>>Tuần này</option>
                    <option value="this_month" <?php if($filter == 'this_month') echo 'selected'; ?>>Tháng này</option>
                </select>
            </div>
            <div class="col-md-6"><input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>"></div>
            <div class="col-md-6"><input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>"></div>
            <div class="col-md-12 d-flex">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> Lọc</button>
                <a href="manage_orders.php" class="btn btn-secondary ms-2" title="Đặt lại"><i class="fas fa-sync-alt"></i></a>
            </div>
        </form>
    </div>

    <div class="card-clean p-0"> 
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th style="width: 12%;">Mã ĐH</th>
                        <th style="width: 14%;">Khách hàng</th>
                        <th style="width: 10%;">Ngày mua</th>
                        <th style="width: 10%;">Tổng tiền</th>
                        <th style="width: 12%;">Khuyến mãi</th>
                        <th style="width: 10%;">P.thức TT</th>
                        <th style="width: 10%;">Tình trạng</th>
                        <th style="width: 22%;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $row): ?>
                            <tr>
                                <td class="order-id" style="font-weight: 600; color: var(--primary-color);"><?php echo htmlspecialchars($row['madonhang']); ?></td>
                                <td class="customer-info">
                                    <strong><?php echo htmlspecialchars($row['tenkhach']); ?></strong><br>
                                    <small class="text-muted"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($row['sodienthoai']); ?></small>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($row['ngaymua'])); ?></td>
                                <td class="order-price" style="font-weight: 700; color: var(--danger-color);"><?php echo number_format($row['tongtiendonhang'] ?? 0, 0, ',', '.'); ?> VNĐ</td>
                                <td>
                                    <?php if (!empty($row['makhuyenmai'])): ?>
                                        <span class="badge rounded-pill bg-success text-white" style="font-size: 0.75rem;"><?php echo htmlspecialchars($row['makhuyenmai']); ?></span>
                                        <br>
                                        <small class="text-muted" style="font-size: 0.8em;">
                                            <?php 
                                            // ... (Logic hiển thị chi tiết KM giữ nguyên) ...
                                            $discount_info = '';
                                            if (!empty($row['tenkhuyenmai'])) {
                                                $discount_info = htmlspecialchars($row['tenkhuyenmai']);
                                            } elseif (isset($row['giatri']) && $row['giatri'] > 0) {
                                                if (isset($row['loai']) && $row['loai'] === 'PHAN_TRAM') {
                                                    $discount_info = 'Giảm ' . htmlspecialchars(number_format($row['giatri'], 0, ',', '.')) . '%';
                                                } else { 
                                                    $discount_info = 'Giảm ' . htmlspecialchars(number_format($row['giatri'], 0, ',', '.')) . ' VNĐ';
                                                }
                                            } else {
                                                $discount_info = 'Mã KM không hợp lệ';
                                            }
                                            echo $discount_info;
                                            ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted"><i>Không có</i></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $payment_method = htmlspecialchars($row['phuongthuctt']);
                                    if ($payment_method == 'COD') {
                                        echo '<span class="badge rounded-pill bg-info text-dark">Tiền mặt</span>';
                                    } elseif ($payment_method == 'BANK_TRANSFER') {
                                        echo '<span class="badge rounded-pill bg-warning text-dark">Chuyển khoản</span>';
                                    } elseif ($payment_method == 'ONLINE_CARD') {
                                        echo '<span class="badge rounded-pill bg-primary">Online</span>';
                                    } else {
                                        echo htmlspecialchars($payment_method);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                        $status = htmlspecialchars($row['tinhtrang']);
                                        $badge_class = 'bg-secondary';
                                        if ($status == 'Đã giao') $badge_class = 'bg-success';
                                        elseif ($status == 'Đang xử lý' || $status == 'Đang giao hàng') $badge_class = 'bg-primary';
                                        elseif ($status == 'Đã hủy') $badge_class = 'bg-danger';
                                        echo "<span class='badge {$badge_class}'>{$status}</span>";
                                    ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-column" style="gap: 5px; font-size: 14px;">
                                        <form method="POST" class="d-flex" style="gap: 5px;">
                                            <input type="hidden" name="madonhang" value="<?php echo htmlspecialchars($row['madonhang']); ?>">
                                            <select name="tinhtrang" class="form-select form-select-sm" style="flex-grow: 1;">
                                                <option value="Đang xử lý" <?php if($row['tinhtrang'] == 'Đang xử lý') echo 'selected'; ?>>Đang xử lý</option>
                                                <option value="Đang giao hàng" <?php if($row['tinhtrang'] == 'Đang giao hàng') echo 'selected'; ?>>Đang giao hàng</option>
                                                <option value="Đã giao" <?php if($row['tinhtrang'] == 'Đã giao') echo 'selected'; ?>>Đã giao</option>
                                                <option value="Đã hủy" <?php if($row['tinhtrang'] == 'Đã hủy') echo 'selected'; ?>>Đã hủy</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-primary btn-sm" title="Cập nhật"><i class="fas fa-check"></i></button>
                                        </form>
                                        <div class="d-flex justify-content-around align-items-center pt-1" style="border-top: 1px solid var(--border-color);">
                                            <a href="order_detail.php?id=<?php echo htmlspecialchars($row['madonhang']); ?>" title="Xem chi tiết" class="text-primary"><i class="fas fa-eye"></i> Chi tiết</a>
                                            <span class="text-muted">|</span>
                                            <a href="manage_orders.php?action=delete&id=<?php echo htmlspecialchars($row['madonhang']); ?>" title="Xóa đơn hàng" class="text-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn đơn hàng này?');">
                                               <i class="fas fa-trash-alt"></i> Xóa
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">
                                <div class="text-center p-5 text-muted">Không tìm thấy đơn hàng nào.</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <nav class="p-3 border-top">
                <ul class="pagination justify-content-center mb-0">
                    <?php 
                    $query_params = $_GET;
                    unset($query_params['page']);
                    $base_query = http_build_query($query_params);
                    ?>
                    <li class="page-item <?php if($page <= 1) echo 'disabled'; ?>">
                        <a class="page-link" href="?<?php echo $base_query . '&page=' . ($page - 1); ?>">Trước</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php if($i == $page) echo 'active'; ?>">
                            <a class="page-link" href="?<?php echo $base_query . '&page=' . $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php if($page >= $total_pages) echo 'disabled'; ?>">
                        <a class="page-link" href="?<?php echo $base_query . '&page=' . ($page + 1); ?>">Sau</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
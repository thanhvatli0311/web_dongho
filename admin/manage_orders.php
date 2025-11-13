<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../templates/adminheader.php';

// --- PHẦN LOGIC PHP (GIỮ NGUYÊN, KHÔNG THAY ĐỔI) ---

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') { header("Location: ../login.php"); exit; }
if (!isset($pdo)) { die("Lỗi: Không thể kết nối CSDL."); }

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

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

$where_clauses = []; $params = [];
if ($search !== '') {
    $where_clauses[] = "(tbDonHang.madonhang LIKE ? OR tbkhachhang.tenkhach LIKE ? OR tbkhachhang.sodienthoai LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param; $params[] = $search_param; $params[] = $search_param;
}
if ($filter !== '') {
    if ($filter == 'today') $where_clauses[] = "DATE(tbDonHang.ngaymua) = CURDATE()";
    elseif ($filter == 'yesterday') $where_clauses[] = "DATE(tbDonHang.ngaymua) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    elseif ($filter == 'this_week') $where_clauses[] = "YEARWEEK(tbDonHang.ngaymua, 1) = YEARWEEK(CURDATE(), 1)";
    elseif ($filter == 'this_month') $where_clauses[] = "MONTH(tbDonHang.ngaymua) = MONTH(CURDATE()) AND YEAR(tbDonHang.ngaymua) = YEAR(CURDATE())";
}
if ($date_from !== '' && $date_to !== '') {
    $where_clauses[] = "DATE(tbDonHang.ngaymua) BETWEEN ? AND ?";
    $params[] = $date_from; $params[] = $date_to;
}
$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(' AND ', $where_clauses) : "";

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$count_sql = "SELECT COUNT(*) FROM tbDonHang JOIN tbkhachhang ON tbDonHang.makhach = tbkhachhang.makhach {$where_sql}";
$stmt_count = $pdo->prepare($count_sql);
$stmt_count->execute($params);
$total = $stmt_count->fetchColumn();
$total_pages = ceil($total / $limit);

$sql = "SELECT tbDonHang.*, tbkhachhang.tenkhach, tbkhachhang.sodienthoai,
        (SELECT SUM(soluong * dongia) FROM tbChiTietDonHang WHERE madonhang = tbDonHang.madonhang) AS total_value
        FROM tbDonHang 
        JOIN tbkhachhang ON tbDonHang.makhach = tbkhachhang.makhach 
        {$where_sql} 
        ORDER BY tbDonHang.ngaymua DESC 
        LIMIT {$limit} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Đơn hàng</title>
    
    <style>
        body {
            background-color: #f4f6f9;
        }
        .container-fluid {
            max-width: 1600px;
        }
        .filter-box {
            background-color: #fff;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.1);
            margin-bottom: 2rem;
        }
        .orders-table {
            border-collapse: separate;
            border-spacing: 0 15px;
            width: 100%;
        }
        .orders-table thead th {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 15px 20px;
            font-weight: 600;
            text-align: left;
        }
        .orders-table thead th:first-child { border-radius: 8px 0 0 8px; }
        .orders-table thead th:last-child { border-radius: 0 8px 8px 0; }

        .orders-table tbody tr {
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,.1);
            border-radius: 8px;
        }
        .orders-table tbody td {
            padding: 20px;
            vertical-align: middle;
            border: none;
        }
        .orders-table tbody td:first-child { border-radius: 8px 0 0 8px; }
        .orders-table tbody td:last-child { border-radius: 0 8px 8px 0; }

        .customer-info strong { font-size: 1.05rem; }
        .customer-info small { color: #555; }
        .order-id { font-weight: 600; }
        .order-price { font-weight: 600; color: #dc3545; }
        
        .action-box {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            background-color: #fff;
        }
        .action-box .btn-save { width: 100%; margin-top: 10px; }
        .action-icons { margin-top: 10px; text-align: center; }
        .action-icons a { color: #6c757d; margin: 0 8px; font-size: 1.1rem; }

        /* === CSS PHÂN TRANG (ĐÃ SỬA LẠI THEO HÌNH ẢNH) === */
        .pagination {
            padding-bottom: 2rem;
        }
        /* Bỏ các style mặc định của Bootstrap */
        .pagination .page-item {
            margin: 0 !important;
        }
        .pagination .page-link {
            color: #343a40; /* Màu chữ mặc định */
            background-color: #fff;
            border: 1px solid #dee2e6;
            margin-left: -1px; /* Ghép các nút lại với nhau */
            transition: all 0.2s ease;
            box-shadow: none !important;
            border-radius: 0; /* Bỏ bo góc riêng lẻ */
        }
        /* Bo góc cho nút đầu và nút cuối của cả cụm */
        .pagination .page-item:first-child .page-link {
            border-top-left-radius: 0.25rem;
            border-bottom-left-radius: 0.25rem;
        }
        .pagination .page-item:last-child .page-link {
            border-top-right-radius: 0.25rem;
            border-bottom-right-radius: 0.25rem;
        }

        .pagination .page-link:hover {
            background-color: #e9ecef;
            border-color: #dee2e6;
        }
        .pagination .page-item.active .page-link {
            z-index: 1;
            color: #fff;
            background-color: #2c3e50; /* Màu nền tối cho trang hiện tại */
            border-color: #2c3e50;
        }
        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            background-color: #fff;
            border-color: #dee2e6;
        }
    </style>
</head>
<body>
    <h1>Quản lý đơn hàng</h1>
<div class="container-fluid pt-4">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message']['type']; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($_SESSION['message']['content']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <div class="filter-box">
        <form method="GET" class="row g-3">
            <div class="col-md-12"><input type="text" name="search" class="form-control" placeholder="Tìm Mã ĐH, Tên KH, SĐT..." value="<?php echo htmlspecialchars($search); ?>"></div>
            <div class="col-md-12"><select name="filter" class="form-select">
                    <option value="">Lọc nhanh...</option>
                    <option value="today" <?php if($filter == 'today') echo 'selected'; ?>>Hôm nay</option>
                    <option value="yesterday" <?php if($filter == 'yesterday') echo 'selected'; ?>>Hôm qua</option>
                    <option value="this_week" <?php if($filter == 'this_week') echo 'selected'; ?>>Tuần này</option>
                    <option value="this_month" <?php if($filter == 'this_month') echo 'selected'; ?>>Tháng này</option>
            </select></div>
            <div class="col-md-6"><input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>"></div>
            <div class="col-md-6"><input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>"></div>
            <div class="col-md-12 d-flex">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> Lọc</button>
                <a href="manage_orders.php" class="btn btn-light ms-2" title="Đặt lại"><i class="fas fa-sync-alt"></i></a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="orders-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Mã ĐH</th>
                    <th style="width: 15%;">Khách hàng</th>
                    <th style="width: 12%;">Ngày mua</th>
                    <th style="width: 13%;">Tổng tiền</th>
                    <th style="width: 12%;">Tình trạng</th>
                    <th style="width: 33%;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $row): ?>
                        <tr>
                            <td class="order-id"><?php echo htmlspecialchars($row['madonhang']); ?></td>
                            <td class="customer-info">
                                <strong><?php echo htmlspecialchars($row['tenkhach']); ?></strong><br>
                                <small><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($row['sodienthoai']); ?></small>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['ngaymua'])); ?></td>
                            <td class="order-price"><?php echo number_format($row['total_value'] ?? 0, 0, ',', '.'); ?> VNĐ</td>
                            <td><?php echo htmlspecialchars($row['tinhtrang']); ?></td>
                            <td>
                                <div class="action-box">
                                    <form method="POST" class="d-flex flex-column">
                                        <input type="hidden" name="madonhang" value="<?php echo htmlspecialchars($row['madonhang']); ?>">
                                        <select name="tinhtrang" class="form-select">
                                            <option value="Chờ xử lý" <?php if($row['tinhtrang'] == 'Chờ xử lý') echo 'selected'; ?>>Chờ xử lý</option>
                                            <option value="Đang giao hàng" <?php if($row['tinhtrang'] == 'Đang giao hàng') echo 'selected'; ?>>Đang giao hàng</option>
                                            <option value="Đã giao hàng" <?php if($row['tinhtrang'] == 'Đã giao hàng') echo 'selected'; ?>>Đã giao hàng</option>
                                            <option value="Đã hủy" <?php if($row['tinhtrang'] == 'Đã hủy') echo 'selected'; ?>>Đã hủy</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn-primary btn-save"><i class="fas fa-check"></i></button>
                                    </form>
                                    <div class="action-icons">
                                        <a href="order_detail.php?id=<?php echo htmlspecialchars($row['madonhang']); ?>" title="Xem chi tiết"><i class="fas fa-eye"></i></a>
                                        <a href="manage_orders.php?action=delete&id=<?php echo htmlspecialchars($row['madonhang']); ?>" title="Xóa đơn hàng" onclick="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn đơn hàng này?');">
                                           <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                       <td colspan="6">
                           <div class="text-center p-5 bg-white rounded-3">Không tìm thấy đơn hàng nào.</div>
                       </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Phân trang đã được áp dụng CSS mới -->
    <?php if ($total_pages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center mt-4">
                <?php 
                $query_params = $_GET; unset($query_params['page']);
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
</body>
</html>
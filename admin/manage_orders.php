<?php
session_start();
include '../includes/db.php';

// Kiểm tra nếu chưa đăng nhập hoặc không phải Admin thì chuyển hướng
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

include '../templates/adminheader.php';

// Xử lý cập nhật tình trạng đơn hàng
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['update_status'])) {
    $madonhang = $_POST['madonhang'];
    $tinhtrang = $_POST['tinhtrang'];

    $update_query = $conn->prepare("UPDATE tbDonHang SET tinhtrang = ? WHERE madonhang = ?");
    $update_query->bind_param("ss", $tinhtrang, $madonhang);
    $update_query->execute();
    header("Location: manage_orders.php");
    exit;
}

// Lấy các biến lọc từ URL (GET)
$search    = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to   = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$filter    = isset($_GET['filter']) ? $_GET['filter'] : '';

// Xây dựng mệnh đề WHERE cho SQL
$where = "WHERE 1=1";
$params = [];
$types  = "";

// Tìm kiếm theo mã đơn, tên khách, SĐT, hoặc địa chỉ
if ($search !== '') {
    $where .= " AND (tbDonHang.madonhang LIKE ? OR tbkhachhang.tenkhach LIKE ? OR tbkhachhang.sodienthoai LIKE ? OR tbkhachhang.diachi LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= "ssss";
}

// Lọc nhanh theo dropdown
if ($filter !== '') {
    if ($filter == 'today') {
        $where .= " AND DATE(tbDonHang.ngaymua) = CURDATE()";
    } elseif ($filter == 'yesterday') {
        $where .= " AND DATE(tbDonHang.ngaymua) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    } elseif ($filter == 'this_week') {
        $where .= " AND YEARWEEK(tbDonHang.ngaymua, 1) = YEARWEEK(CURDATE(), 1)";
    } elseif ($filter == 'this_month') {
        $where .= " AND MONTH(tbDonHang.ngaymua) = MONTH(CURDATE()) AND YEAR(tbDonHang.ngaymua) = YEAR(CURDATE())";
    }
}

// Lọc theo khoảng thời gian (nếu cả hai ngày được chọn)
if ($date_from !== '' && $date_to !== '') {
    $where .= " AND DATE(tbDonHang.ngaymua) BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
    $types .= "ss";
}

// Phân trang
$limit  = 10;
$page   = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số đơn hàng
$count_sql = "SELECT COUNT(*) as total FROM tbDonHang 
              JOIN tbkhachhang ON tbDonHang.makhach = tbkhachhang.makhach 
              $where";
$stmt = $conn->prepare($count_sql);
if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result_count = $stmt->get_result();
$total_row = $result_count->fetch_assoc();
$total = $total_row['total'];
$total_pages = ceil($total / $limit);

// Lấy dữ liệu đơn hàng cùng thông tin khách hàng và tổng giá trị
$sql = "SELECT tbDonHang.*, tbkhachhang.tenkhach, tbkhachhang.sodienthoai, tbkhachhang.diachi, 
        (SELECT SUM(soluong * dongia) FROM tbChiTietDonHang WHERE madonhang = tbDonHang.madonhang) AS total_value
        FROM tbDonHang 
        JOIN tbkhachhang ON tbDonHang.makhach = tbkhachhang.makhach 
        $where 
        ORDER BY tbDonHang.ngaymua DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($types !== "") {
    $types_with_limit = $types . "ii";
    $params_with_limit = $params;
    $params_with_limit[] = $limit;
    $params_with_limit[] = $offset;
    $stmt->bind_param($types_with_limit, ...$params_with_limit);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý đơn hàng</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f7f9fc;
            color: #333;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 2px solid #e1e8ed;
            padding-bottom: 10px;
        }

        .container-custom {
            max-width: 1200px;
            margin: 30px auto;
            background: #fff;
            padding: 20px 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        /* Tùy chỉnh phần tìm kiếm & lọc: sử dụng flex để sắp xếp các thành phần */
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            /* Căn giữa theo chiều ngang */
            gap: 15px;
            margin-bottom: 20px;
        }

        .filter-form .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 0;
            /* Loại bỏ khoảng cách dưới để các phần tử thẳng hàng */
        }

        .filter-form .form-group label {
            margin-right: 5px;
        }

        .filter-form .form-group input,
        .filter-form .form-group select {
            /* Giữ chiều cao đồng nhất cho các input */
            height: calc(1.5em + .75rem + 2px);
        }

        /* Căn chỉnh nút Tìm kiếm và Reset */
        .filter-form button.btn-primary,
        .filter-form a.btn-secondary {
            width: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            height: calc(1.5em + .75rem + 2px);
           
            padding: 0;
            line-height: 1;
           
            vertical-align: middle;
           
            margin: 0;
        }

        .filter-form .form-group a.btn-secondary {
            margin-left: 10px;
        }

     
        .table th:nth-child(1) {
            width: 100px;
        }

        .table th:nth-child(2) {
            width: 18%;
        }

        .table th:nth-child(3) {
            width: 12%;
        }

        .table th:nth-child(4) {
            width: 20%;
        }

        .table th:nth-child(5) {
            width: 15%;
        }

        .table th:nth-child(6) {
            width: 13%;
        }

        .table th:nth-child(7) {
            width: 10%;
        }

        /* Căn giữa cho văn bản trong các cột */
        .table th,
        .table td {
            text-align: center;
            vertical-align: middle;
        }

        /* Đổi màu cho tên cột */
        .table thead th {
            background-color: #2c3e50 !important;
            color: #fff;
            font-weight: bold;
        }

        .pagination .page-link {
            color: #2c3e50;
        }

        .pagination .page-item.active .page-link {
            background-color: #2c3e50;
            border-color: #2c3e50;
        }

        /* Nút cập nhật đổi màu sang #ffc107 */
        .btn.btn-sm.btn-primary {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000;
        }

        .btn.btn-sm.btn-primary:hover {
            background-color: #e0a800;
            border-color: #d39e00;
            color: #000;
        }
    </style>
</head>

<body>
    <div class="container-custom">
        <h2>Quản lý đơn hàng</h2>

        <!-- Phần tìm kiếm và lọc -->
        <form method="GET" class="filter-form">
            <div class="form-group">
                <input type="text" name="search" class="form-control" placeholder="Tìm kiếm: Mã đơn, khách, SĐT, địa chỉ" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="form-group">
                <select name="filter" class="form-control">
                    <option value="">-- Lọc nhanh --</option>
                    <option value="today" <?= ($filter == 'today') ? 'selected' : '' ?>>Hôm nay</option>
                    <option value="yesterday" <?= ($filter == 'yesterday') ? 'selected' : '' ?>>Hôm qua</option>
                    <option value="this_week" <?= ($filter == 'this_week') ? 'selected' : '' ?>>Tuần này</option>
                    <option value="this_month" <?= ($filter == 'this_month') ? 'selected' : '' ?>>Tháng này</option>
                </select>
            </div>
            <div class="form-group">
                <label for="date_from">Từ:</label>
                <input type="date" name="date_from" id="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
            </div>
            <div class="form-group">
                <label for="date_to">Đến:</label>
                <input type="date" name="date_to" id="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                <a href="manage_orders.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>

        <!-- Bảng đơn hàng -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>SĐT</th>
                    <th>Địa chỉ</th>
                    <th>Ngày mua</th>
                    <th>Tổng giá trị</th>
                    <th>Tình trạng</th>
                    <th>Chi tiết</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['madonhang']) ?></td>
                            <td><?= htmlspecialchars($row['tenkhach']) ?></td>
                            <td><?= htmlspecialchars($row['sodienthoai']) ?></td>
                            <td><?= htmlspecialchars($row['diachi']) ?></td>
                            <td><?= htmlspecialchars($row['ngaymua']) ?></td>
                            <td><?= number_format($row['total_value'], 0) ?> VND</td>
                            <td>
                                <form method="post" style="margin: 0;">
                                    <input type="hidden" name="madonhang" value="<?= htmlspecialchars($row['madonhang']) ?>">
                                    <select name="tinhtrang" class="form-control form-control-sm d-inline-block" style="width: auto;">
                                        <option value="Đang xử lý" <?= ($row['tinhtrang'] == "Đang xử lý") ? 'selected' : '' ?>>Đang xử lý</option>
                                        <option value="Đã giao" <?= ($row['tinhtrang'] == "Đã giao") ? 'selected' : '' ?>>Đã giao</option>
                                        <option value="Đang giao hàng" <?= ($row['tinhtrang'] == "Đang giao hàng") ? 'selected' : '' ?>>Đang giao hàng</option>
                                        <option value="Đã hủy" <?= ($row['tinhtrang'] == "Đã hủy") ? 'selected' : '' ?>>Đã hủy</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary">Cập nhật</button>
                            <td>
                                <a href="order_detail.php?madonhang=<?= urlencode($row['madonhang']) ?>" class="btn btn-sm btn-info">Xem</a>
                            </td>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">Không có dữ liệu đơn hàng.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Phân trang -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&filter=<?= urlencode($filter) ?>">Trước</a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&filter=<?= urlencode($filter) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&filter=<?= urlencode($filter) ?>">Sau</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</body>

</html>
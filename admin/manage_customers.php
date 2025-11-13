<?php
session_start();
// Đồng bộ hóa đường dẫn include/require theo các file khác
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../templates/adminheader.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

// Kiểm tra kết nối PDO
if (!isset($pdo)) {
    die("Lỗi: Không thể kết nối CSDL (PDO). Vui lòng kiểm tra file includes/db.php.");
}

// Khởi tạo biến $search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Phân trang
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Xây dựng mệnh đề WHERE và danh sách tham số (PDO)
$where = 'WHERE 1=1';
$params = [];

if (!empty($search)) {
    // Thêm các điều kiện tìm kiếm và sử dụng placeholder '?'
    $where .= " AND (tenkhach LIKE ? OR sodienthoai LIKE ? OR diachi LIKE ? OR makhach LIKE ?)";
    $likeSearch = "%" . $search . "%";
    
    // Thêm tham số tìm kiếm (cần lặp lại 4 lần cho 4 điều kiện LIKE)
    $params = [$likeSearch, $likeSearch, $likeSearch, $likeSearch];
}

// 1. Truy vấn tổng số khách hàng (PDO)
$total_customers = 0;
try {
    $sql_count = "SELECT COUNT(*) AS total FROM tbkhachhang {$where}";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params); // Truyền các tham số tìm kiếm
    $total_customers = $stmt_count->fetchColumn(); // Lấy trực tiếp cột COUNT
} catch (PDOException $e) {
    die("Lỗi truy vấn tổng số: " . $e->getMessage());
}

$total_pages = ceil($total_customers / $limit);

// 2. Truy vấn dữ liệu khách hàng (PDO)
$customers = [];
$sql_data = "SELECT makhach, tenkhach, ngaysinh, sodienthoai, diachi, gioitinh, username 
             FROM tbkhachhang 
             {$where}
             LIMIT :limit OFFSET :offset";
             
try {
    $stmt = $pdo->prepare($sql_data);

    // Bind các tham số tìm kiếm (vị trí)
    $paramIndex = 1;
    foreach ($params as $paramValue) {
        $stmt->bindValue($paramIndex++, $paramValue, PDO::PARAM_STR);
    }
    
    // Bind các tham số phân trang (đặt tên)
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Lỗi truy vấn dữ liệu: " . $e->getMessage());
}

// Xử lý trường hợp truy cập trang vượt giới hạn
if ($page > $total_pages && $total_pages > 0) {
    header("Location: manage_customers.php?page={$total_pages}&search=" . urlencode($search));
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý khách hàng</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* Reset một số margin và padding mặc định */
        body,
        h2,
        table {
            margin: 0;
            padding: 0;
        }

        /* Định dạng cho body */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f7f9fc;
            color: #333;
            line-height: 1.6;
        }

        /* Container chính */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            background: #fff;
            padding: 20px 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        /* Tiêu đề */
        h2 {
            margin-bottom: 20px;
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 2px solid #e1e8ed;
            padding-bottom: 10px;
        }

        /* Đảm bảo phần .input-group-append hiển thị theo flex để các nút có cùng chiều cao */
        .input-group .input-group-append {
            display: flex;
        }

        /* Giữ lại border-radius như cũ */
        .input-group input.form-control {
            border-radius: 30px 0 0 30px;
            border-right: none;
        }

        .input-group .input-group-append button,
        .input-group .input-group-append a.btn {
            border-radius: 0 30px 30px 0;
            height: calc(2.25rem + 2px);
            margin: 0;
        }

        /* Thông báo */
        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
            padding: 12px 20px;
        }

        /* Bảng dữ liệu */
        table.table {
            margin-bottom: 20px;
            border-collapse: separate;
            border-spacing: 0;
        }

        table.table th,
        table.table td {
            vertical-align: middle;
            text-align: center;
        }

        table.table thead th {
            background-color: #2c3e50;
            color: #fff;
            font-weight: 500;
            padding: 12px;
            border: none;
        }

        table.table tbody td {
            padding: 12px;
            border-top: 1px solid #dee2e6;
        }

        table.table tbody tr:hover {
            background-color: #f1f5f9;
        }

        /* Nút hành động */
        .btn {
            min-width: 70px;
        }

        /* Phân trang */
        .pagination {
            justify-content: center;
            margin: 0;
        }

        .pagination .page-item.active .page-link {
            background-color: #2c3e50;
            border-color: #2c3e50;
        }

        .pagination .page-link {
            color: #2c3e50;
            border-radius: 5px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 15px 20px;
            }

            table.table th,
            table.table td {
                padding: 8px;
            }
        }

        /* Cột 6: Giới tính */
        table.table thead th:nth-child(6),
        table.table tbody td:nth-child(6) {
            width: 100px;
        }
    
        table.table thead th:nth-child(7),
        table.table tbody td:nth-child(7) {
            width: 15%;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Quản lý khách hàng</h2>

        <form method="GET" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control"
                    placeholder="Tìm kiếm theo tên, số điện thoại, địa chỉ..."
                    value="<?= htmlspecialchars($search) ?>">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">Tìm kiếm</button>
                    <a href="manage_customers.php" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>

        <?php if (isset($_SESSION['message'])) : ?>
            <?php 
            $msg = $_SESSION['message'];
            
            // Nếu message là array (theo cấu trúc ['type' => 'success', 'content' => '...'])
            if (is_array($msg) && isset($msg['content'])) {
                $type = $msg['type'] ?? 'success'; // Mặc định là 'success'
                $content = $msg['content'];
            } else {
                // Nếu message là chuỗi đơn giản (tương thích ngược) hoặc không phải string
                $type = 'success';
                $content = is_string($msg) ? $msg : 'Lỗi hiển thị thông báo: Dữ liệu không hợp lệ.';
            }
            ?>
            <div class="alert alert-<?= htmlspecialchars($type) ?>">
                <?= htmlspecialchars($content) ?>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Mã KH</th>
                    <th>Tên khách</th>
                    <th>Ngày sinh</th>
                    <th>SĐT</th>
                    <th>Địa chỉ</th>
                    <th>Giới tính</th>
                    <th>Thay đổi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($customers)) : // Sử dụng mảng $customers từ PDO ?>
                    <?php foreach ($customers as $row) : // Lặp qua mảng kết quả ?>
                        <tr>
                            <td><?= htmlspecialchars($row['makhach']) ?></td>
                            <td><?= htmlspecialchars($row['tenkhach']) ?></td>
                            <td>
                                <?= !empty($row['ngaysinh']) && $row['ngaysinh'] !== '0000-00-00'
                                    ? date('d/m/Y', strtotime($row['ngaysinh']))
                                    : 'N/A' ?>
                            </td>
                            <td><?= htmlspecialchars($row['sodienthoai']) ?></td>
                            <td><?= htmlspecialchars($row['diachi']) ?></td>
                            <td><?= htmlspecialchars($row['gioitinh']) ?></td>
                            <td>
                                <a href="edit_customer.php?id=<?= urlencode($row['makhach']) ?>" class="btn btn-warning btn-sm">Sửa</a>
                                </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7" class="text-center">Không có dữ liệu khách hàng.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <nav>
            <ul class="pagination">
                <?php if ($page > 1) : ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Trước</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages) : ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Sau</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</body>

</html>
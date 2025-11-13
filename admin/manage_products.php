<?php
session_start();
// Đồng bộ hóa đường dẫn include/require theo file chatbot_manager.php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../templates/adminheader.php';

// Kiểm tra nếu chưa đăng nhập hoặc không phải Admin thì chuyển hướng
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

// Kiểm tra biến $pdo (đối tượng kết nối PDO)
if (!isset($pdo)) {
    die("Lỗi: Không thể kết nối CSDL (PDO). Vui lòng kiểm tra file includes/db.php.");
}

// Xử lý xóa sản phẩm (Chuyển sang PDO)
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $sql = "DELETE FROM tbmathang WHERE mahang = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $_SESSION['message'] = ['type' => 'success', 'content' => 'Sản phẩm đã được xóa thành công.'];
    } catch (PDOException $e) {
        $_SESSION['message'] = ['type' => 'danger', 'content' => 'Lỗi xóa sản phẩm: ' . $e->getMessage()];
    }
    // Chuyển hướng để xóa tham số 'delete' trên URL và hiển thị thông báo (nếu có)
    header("Location: manage_products.php");
    exit;
}

// Lấy từ khóa tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Phân trang
$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$where = "";
$params = [];

// Nếu có tìm kiếm thì dùng prepared statement (PDO)
if ($search !== "") {
    $likeSearch = "%" . $search . "%";
    $where = " WHERE tenhang LIKE ? ";
    $params[] = $likeSearch;
}

// 1. Tính tổng số sản phẩm (PDO)
try {
    $stmtCount = $pdo->prepare("SELECT COUNT(*) as total FROM tbmathang {$where}");
    $stmtCount->execute($params);
    $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
} catch (PDOException $e) {
    die("Lỗi truy vấn tổng số: " . $e->getMessage());
}

$total_pages = ceil($total / $limit);

// 2. Truy vấn dữ liệu sản phẩm với phân trang (PDO)
$sql = "SELECT * FROM tbmathang {$where} LIMIT :limit OFFSET :offset";
try {
    $stmt = $pdo->prepare($sql);
    
    // Bind các tham số LIMIT và OFFSET (cần bindValue vì kiểu dữ liệu là INT)
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    // Bind tham số tìm kiếm
    $param_index = 1;
    foreach ($params as $value) {
        $stmt->bindValue($param_index++, $value, PDO::PARAM_STR);
    }

    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC); // Lấy tất cả dữ liệu vào mảng $products
    
} catch (PDOException $e) {
    die("Lỗi truy vấn dữ liệu: " . $e->getMessage());
}

// Xử lý trường hợp người dùng truy cập trang quá giới hạn (ví dụ: page=10 khi chỉ có 5 trang)
if ($page > $total_pages && $total_pages > 0) {
    header("Location: manage_products.php?page={$total_pages}&search=" . urlencode($search));
    exit;
}

// Biến $products sẽ chứa mảng kết quả, thay thế cho đối tượng $result của MySQLi
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý sản phẩm</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f7f9fc;
            color: #333;
        }

        h2 {
            margin-bottom: 20px;
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 2px solid #e1e8ed;
            padding-bottom: 10px;
        }

        /* Định dạng container chung */
        .container-custom {
            max-width: 1200px;
            margin: 30px auto;
            background: #fff;
            padding: 20px 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        /* Thanh tìm kiếm */
        .search-form .input-group {
            align-items: center;
        }
        .search-form .input-group input.form-control {
            border-radius: 30px 0 0 30px;
            border-right: none;
        }
        .search-form .input-group-append button,
        .search-form .input-group-append a.btn {
            border-radius: 0 30px 30px 0;
        }
        .input-group .input-group-append {
            display: flex;
        }
        .input-group .input-group-append button,
        .input-group .input-group-append a.btn {
            height: calc(2.25rem + 2px);
            margin: 0;
        }

        /* Bảng dữ liệu */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th,
        table td {
            padding: 12px;
            border: 1px solid #dee2e6;
            text-align: center;
        }
        table th {
            background-color: #2c3e50;
            color: #fff;
        }
        /* Tăng kích thước cột Mã hàng */
        table th:first-child, 
        table td:first-child {
            width: 15%;
        }


        /* Căn giữa nút Thêm sản phẩm */
        .add-product-container {
            text-align: center;
            margin-bottom: 20px;
        }

        /* Phân trang: đổi màu #2c3e50 */
        .pagination .page-link {
            color: #2c3e50;
        }
        .pagination .page-item.active .page-link {
            background-color: #2c3e50;
            border-color: #2c3e50;
        }
    </style>
</head>

<body>
    <div class="container-custom">
        <h2>Quản lý sản phẩm</h2>

        <div class="add-product-container">
            <a href="../admin/add_product.php" class="btn btn-success">Thêm sản phẩm</a>
        </div>

        <form class="search-form mb-4" action="" method="GET">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Tìm kiếm sản phẩm..."
                    value="<?= htmlspecialchars($search) ?>">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">Tìm kiếm</button>
                    <a href="manage_products.php" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Mã hàng</th>
                    <th>Tên hàng</th>
                    <th>Đơn giá</th>
                    <th>Thay đổi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)) : // Dùng !empty($products) để kiểm tra mảng kết quả ?>
                    <?php foreach ($products as $row) : // Lặp qua mảng kết quả $products ?>
                        <tr>
                            <td><?= htmlspecialchars($row['mahang']) ?></td>
                            <td><?= htmlspecialchars($row['tenhang']) ?></td>
                            <td><?= number_format($row['dongia'], 0, ',', '.') ?> VND</td>
                            <td>
                                <a href="../admin/edit_product.php?id=<?= urlencode($row['mahang']) ?>" class="btn btn-warning btn-sm">Sửa</a>
                                <a href="?delete=<?= urlencode($row['mahang']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xác nhận xóa?')">Xóa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4" class="text-center">Không có dữ liệu sản phẩm.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <nav>
            <ul class="pagination justify-content-center">
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
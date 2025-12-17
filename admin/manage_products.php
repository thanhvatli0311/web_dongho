<?php
session_start();
require __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../pages/login.php");
    exit;
}

if (!isset($pdo)) {
    die("Lỗi: Không thể kết nối CSDL (PDO). Vui lòng kiểm tra file includes/db.php.");
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // 1. Xóa các bản ghi phụ thuộc (tbhinhanhchitiet) trước
        $sql_delete_child = "DELETE FROM tbhinhanhchitiet WHERE mahang = ?";
        $stmt_child = $pdo->prepare($sql_delete_child);
        $stmt_child->execute([$id]);

        // 2. Sau đó, xóa bản ghi cha (tbmathang)
        $sql_delete_parent = "DELETE FROM tbmathang WHERE mahang = ?";
        $stmt_parent = $pdo->prepare($sql_delete_parent);
        $stmt_parent->execute([$id]);
        
        $_SESSION['message'] = ['type' => 'success', 'content' => 'Sản phẩm và dữ liệu liên quan đã được xóa thành công.'];
    } catch (PDOException $e) {
        $_SESSION['message'] = ['type' => 'danger', 'content' => 'Lỗi xóa sản phẩm: ' . $e->getMessage()];
    }
    header("Location: manage_products.php");
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : "";

$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$where = "";
$params = [];

if ($search !== "") {
    $likeSearch = "%" . $search . "%";
    $where = " WHERE tenhang LIKE ? ";
    $params[] = $likeSearch;
}

try {
    $stmtCount = $pdo->prepare("SELECT COUNT(*) as total FROM tbmathang {$where}");
    $stmtCount->execute($params);
    $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
} catch (PDOException $e) {
    die("Lỗi truy vấn tổng số: " . $e->getMessage());
}

$total_pages = ceil($total / $limit);

$sql = "SELECT * FROM tbmathang {$where} LIMIT :limit OFFSET :offset";
try {
    $stmt = $pdo->prepare($sql);
    
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $param_index = 1;
    foreach ($params as $value) {
        $stmt->bindValue($param_index++, $value, PDO::PARAM_STR);
    }

    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC); 
    
} catch (PDOException $e) {
    die("Lỗi truy vấn dữ liệu: " . $e->getMessage());
}

if ($page > $total_pages && $total_pages > 0) {
    header("Location: manage_products.php?page={$total_pages}&search=" . urlencode($search));
    exit;
}

require __DIR__ . '/../templates/adminheader.php';
?>

        <div class="card-clean">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 m-0">Danh sách sản phẩm</h2>
                <a href="add_product.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i> Thêm sản phẩm
                </a>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?= $_SESSION['message']['type'] ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['message']['content']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <form class="mb-4" action="" method="GET">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Tìm kiếm sản phẩm theo tên..."
                        value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-primary" type="submit">Tìm kiếm</button>
                    <a href="manage_products.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Mã hàng</th>
                            <th>Tên hàng</th>
                            <th>Đơn giá</th>
                            <th class="text-center">Tác vụ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($products)) : ?>
                            <?php foreach ($products as $row) : ?>
                                <tr>
                                    <td style="width: 15%;"><?= htmlspecialchars($row['mahang']) ?></td>
                                    <td><?= htmlspecialchars($row['tenhang']) ?></td>
                                    <td><?= number_format($row['dongia'], 0, ',', '.') ?> VND</td>
                                    <td class="text-center" style="width: 20%;">
                                        <a href="../admin/edit_product.php?id=<?= urlencode($row['mahang']) ?>" class="btn btn-sm btn-warning me-2">Sửa</a>
                                        <a href="?delete=<?= urlencode($row['mahang']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xác nhận xóa sản phẩm &quot;<?= htmlspecialchars($row['tenhang']) ?>&quot;?')">Xóa</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">Không tìm thấy sản phẩm nào.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <nav>
                <ul class="pagination justify-content-center mt-4">
                    <?php if ($total_pages > 1) : ?>
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Trước</a>
                        </li>

                        <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Sau</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="text-center text-muted small">
                    Hiển thị <?= min($limit, $total) ?> trên tổng số <?= $total ?> sản phẩm.
                </div>
            </nav>
        </div>

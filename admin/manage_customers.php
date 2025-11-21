<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../templates/adminheader.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../pages/login.php");
    exit;
}

if (!isset($pdo)) {
    die("Lỗi: Không thể kết nối CSDL (PDO). Vui lòng kiểm tra file includes/db.php.");
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$where = 'WHERE 1=1';
$params = [];

if (!empty($search)) {
    $where .= " AND (tenkhach LIKE ? OR sodienthoai LIKE ? OR diachi LIKE ? OR makhach LIKE ?)";
    $likeSearch = "%" . $search . "%";
    $params = [$likeSearch, $likeSearch, $likeSearch, $likeSearch];
}

$total_customers = 0;
try {
    $sql_count = "SELECT COUNT(*) AS total FROM tbkhachhang {$where}";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params);
    $total_customers = $stmt_count->fetchColumn();
} catch (PDOException $e) {
    die("Lỗi truy vấn tổng số: " . $e->getMessage());
}

$total_pages = ceil($total_customers / $limit);

$customers = [];
$sql_data = "SELECT makhach, tenkhach, ngaysinh, sodienthoai, diachi, gioitinh, username 
             FROM tbkhachhang 
             {$where}
             LIMIT :limit OFFSET :offset";

try {
    $stmt = $pdo->prepare($sql_data);

    $paramIndex = 1;
    foreach ($params as $paramValue) {
        $stmt->bindValue($paramIndex++, $paramValue, PDO::PARAM_STR);
    }

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Lỗi truy vấn dữ liệu: " . $e->getMessage());
}

if ($page > $total_pages && $total_pages > 0) {
    header("Location: manage_customers.php?page={$total_pages}&search=" . urlencode($search));
    exit;
}

$page_title = 'Khách hàng';
$current_page = basename(__FILE__);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý khách hàng - CleanAdmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3B82F6;
            --accent-color: #10B981;
            --danger-color: #EF4444;
            --bg-body: #F3F4F6;
            --bg-card: #FFFFFF;
            --text-main: #1F2937;
            --text-muted: #6B7280;
            --border-color: #E5E7EB;
            --sidebar-width: 260px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            margin: 0;
            overflow-x: hidden;
            min-height: 100vh;
        }

        .main-wrapper {
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            padding: 32px;
            width: 100%;
            flex-grow: 1;
        }

        .card-clean {
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            padding: 24px;
            transition: box-shadow 0.2s;
            flex-grow: 1;
        }

        .card-clean:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.02);
        }

        .search-container {
            margin-bottom: 24px;
            display: flex;
            gap: 8px;
        }

        .form-control-clean {
            border-radius: 8px !important;
            padding: 10px 16px;
            border: 1px solid var(--border-color);
            box-shadow: none;
            transition: border-color 0.2s;
        }

        .form-control-clean:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .input-group-append-clean {
            display: flex;
            gap: 8px;
        }

        .btn-clean-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .btn-clean-secondary {
            background-color: var(--bg-body);
            border-color: var(--border-color);
            color: var(--text-main);
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .btn-clean-primary:hover {
            background-color: #2563EB;
            border-color: #2563EB;
        }

        .btn-clean-secondary:hover {
            background-color: #E5E7EB;
        }

        .table-clean {
            width: 100%;
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-clean thead th {
            background-color: #F9FAFB;
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 600;
            padding: 12px 16px;
            text-align: left;
            border-bottom: 2px solid var(--border-color);
        }

        .table-clean tbody td {
            padding: 12px 16px;
            border-top: 1px solid var(--border-color);
            color: var(--text-main);
            font-size: 14px;
            vertical-align: middle;
            text-align: left;
        }

        .table-clean tbody tr:hover {
            background-color: #F9FAFB;
        }

        .table-clean thead th:last-child,
        .table-clean tbody td:last-child {
            text-align: center;
            width: 100px;
        }

        .table-clean tbody td:first-child {
            color: var(--text-muted);
            font-weight: 500;
        }

        .text-center-placeholder {
            padding: 40px 16px !important;
            color: var(--text-muted);
            font-style: italic;
        }

        .pagination-clean {
            display: flex;
            justify-content: center;
            margin-top: 24px;
            padding: 0;
        }

        .pagination-clean .page-item .page-link {
            border: 1px solid var(--border-color);
            background-color: var(--bg-card);
            color: var(--text-main);
            border-radius: 8px;
            margin: 0 4px;
            transition: background-color 0.2s, border-color 0.2s;
            font-size: 14px;
        }

        .pagination-clean .page-item .page-link:hover {
            background-color: #F3F4F6;
        }

        .pagination-clean .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .btn-action-warning {
            background-color: #FBBF24;
            border-color: #FBBF24;
            color: #1F2937;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .btn-action-warning:hover {
             background-color: #EAB308;
             border-color: #EAB308;
             color: #1F2937;
        }

        .table-responsive-clean {
            overflow-x: auto;
        }

        @media (max-width: 768px) {
             .main-content { padding: 16px; }
             .card-clean { padding: 16px; }
        }
    </style>
</head>
<body>
<div class="main-content">
    <h3 class="mb-4" style="font-weight:600;">Quản lý khách hàng</h3>
    <div class="card-clean">
        <form method="GET" class="search-container">
            <div class="input-group">
                <input type="text" name="search" class="form-control form-control-clean"
                    placeholder="Tìm kiếm theo tên, SĐT, địa chỉ..."
                    value="<?= htmlspecialchars($search) ?>">
                <div class="input-group-append-clean">
                    <button class="btn btn-clean-primary" type="submit"><i class="fas fa-search"></i> Tìm</button>
                    <a href="manage_customers.php" class="btn btn-clean-secondary"><i class="fas fa-sync-alt"></i> Reset</a>
                </div>
            </div>
        </form>

        <?php if (isset($_SESSION['message'])) : ?>
        <?php
        $msg = $_SESSION['message'];
        $type = is_array($msg) && isset($msg['type']) ? $msg['type'] : 'success';
        $content = is_array($msg) && isset($msg['content']) ? $msg['content'] : (is_string($msg) ? $msg : 'Thông báo.');
        ?>
        <div class="alert alert-<?= htmlspecialchars($type) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($content) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <div class="table-responsive-clean">
            <table class="table table-clean">
                <thead>
                    <tr>
                        <th>Mã KH</th>
                        <th>Tên khách</th>
                        <th>Ngày sinh</th>
                        <th>SĐT</th>
                        <th>Địa chỉ</th>
                        <th>Giới tính</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($customers)) : ?>
                        <?php foreach ($customers as $row) : ?>
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
                                    <a href="edit_customer.php?id=<?= urlencode($row['makhach']) ?>"
                                       class="btn btn-action-warning btn-sm">
                                       <i class="fas fa-edit"></i> Sửa
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7" class="text-center-placeholder">Không có dữ liệu khách hàng.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <nav>
            <ul class="pagination pagination-clean">
                <?php if ($page > 1) : ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>"><i class="fas fa-chevron-left small"></i></a>
                    </li>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                if ($start_page > 1) { echo '<li class="page-item"><span class="page-link">...</span></li>'; }

                for ($i = $start_page; $i <= $end_page; $i++) : ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor;

                if ($end_page < $total_pages) { echo '<li class="page-item"><span class="page-link">...</span></li>'; }
                ?>

                <?php if ($page < $total_pages) : ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>"><i class="fas fa-chevron-right small"></i></a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
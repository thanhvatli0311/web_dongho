<?php
session_start();
// Chú ý: File db.php phải cung cấp biến $pdo (PDO instance)
include '../includes/db.php';
include '../templates/adminheader.php';

// Kiểm tra nếu chưa đăng nhập hoặc không phải Admin thì chuyển hướng
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

// Xử lý cập nhật quyền nếu có yêu cầu (Sử dụng PDO Prepared Statements)
if (isset($_POST['update_role'])) {
    $username = trim($_POST['username']);
    $new_role = trim($_POST['role']);

    try {
        // Cập nhật quyền trong bảng tbuserinrole
        $role_query = $pdo->prepare("UPDATE tbuserinrole SET role = :role WHERE username = :username");
        $role_query->execute([
            ':role' => $new_role,
            ':username' => $username
        ]);
        
        $_SESSION['message'] = ['type' => 'success', 'content' => "Cập nhật quyền **$new_role** cho tài khoản **$username** thành công."];
    } catch (PDOException $e) {
        $_SESSION['message'] = ['type' => 'danger', 'content' => "Lỗi cập nhật quyền: " . $e->getMessage()];
    }

    header("Location: manage_role.php");
    exit;
}

// Thiết lập phân trang
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// 1. Truy vấn tổng số tài khoản (Sử dụng PDO)
$count_sql = "SELECT COUNT(*) as total 
              FROM tbUser u 
              JOIN tbuserinrole ur ON u.username = ur.username";
try {
    $stmt_count = $pdo->query($count_sql);
    $total_users = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    die("Lỗi truy vấn tổng số người dùng: " . $e->getMessage());
}
$total_pages = ceil($total_users / $limit);

// 2. Truy vấn lấy thông tin người dùng, quyền và tên khách (nếu có) có phân trang (Sử dụng PDO)
$sql = "SELECT u.username, ur.role AS role_name, kh.tenkhach 
        FROM tbUser u 
        JOIN tbuserinrole ur ON u.username = ur.username 
        LEFT JOIN tbkhachhang kh ON u.username = kh.username
        LIMIT :limit OFFSET :offset";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy vấn danh sách người dùng: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý tài khoản</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Container chung */
        .container-custom {
            max-width: 900px;
            margin: 30px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            margin-bottom: 20px;
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 2px solid #e1e8ed;
            padding-bottom: 10px;
        }

        /* Bảng dữ liệu */
        .table thead th {
            background-color: #2c3e50 !important;
            color: #fff;
        }

        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }
        
        /* Form trong bảng */
        form.inline-form {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }

        form.inline-form select {
            padding: 5px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            margin-right: 5px;
            min-width: 100px;
        }

        form.inline-form button {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            background-color: #ffc107;
            color: black;
            transition: background-color 0.3s ease;
            font-size: 0.9rem;
        }

        form.inline-form button:hover {
            background-color: #d39e00;
        }

        /* Phân trang */
        .pagination {
            justify-content: center;
        }

        .pagination .page-item.active .page-link {
            background-color: #2c3e50;
            border-color: #2c3e50;
        }

        .pagination .page-link {
            color: #2c3e50;
        }
    </style>
</head>

<body>
    <div class="container-custom">
        <h2>Quản lý tài khoản và Phân quyền</h2>
        
        <!-- Hiển thị thông báo (nếu có) -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message']['type'] ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['message']['content']) ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Tên Khách hàng</th>
                        <th>Quyền hiện tại</th>
                        <th>Cập nhật Quyền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $row) : ?>
                            <tr>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><?= htmlspecialchars($row['tenkhach'] ?? 'Chưa cập nhật') ?></td>
                                <td><?= htmlspecialchars($row['role_name']) ?></td>
                                <td>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="username" value="<?= htmlspecialchars($row['username']) ?>">
                                        <select name="role" class="form-control-sm">
                                            <option value="Member" <?= ($row['role_name'] == 'Member') ? 'selected' : '' ?>>Member</option>
                                            <option value="Admin" <?= ($row['role_name'] == 'Admin') ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                        <button type="submit" name="update_role" class="btn btn-warning btn-sm">
                                            <i class="fas fa-redo-alt"></i> Sửa
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">Không có tài khoản nào được tìm thấy.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Phân trang -->
        <nav>
            <ul class="pagination">
                <?php if ($page > 1) : ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>">Trước</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages) : ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>">Sau</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <!-- Scripts for Bootstrap features -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
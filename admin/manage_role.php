<?php
session_start();
include '../includes/db.php';

// Kiểm tra nếu chưa đăng nhập hoặc không phải Admin thì chuyển hướng
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

include '../templates/adminheader.php';

// Thiết lập phân trang
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Truy vấn tổng số tài khoản
$count_sql = "SELECT COUNT(*) as total 
              FROM tbUser u 
              JOIN tbuserinrole ur ON u.username = ur.username";
$count_result = $conn->query($count_sql);
$total_row = $count_result->fetch_assoc();
$total_users = $total_row['total'];
$total_pages = ceil($total_users / $limit);

// Truy vấn lấy thông tin người dùng, quyền và tên khách (nếu có) từ bảng tbkhachhang có phân trang
$sql = "SELECT u.username, ur.role AS role_name, kh.tenkhach 
        FROM tbUser u 
        JOIN tbuserinrole ur ON u.username = ur.username 
        LEFT JOIN tbkhachhang kh ON u.username = kh.username
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Xử lý cập nhật quyền nếu có yêu cầu
if (isset($_POST['update_role'])) {
    $username = $_POST['username'];
    $new_role = $_POST['role'];

    // Cập nhật quyền trong bảng tbuserinrole
    $role_query = $conn->prepare("UPDATE tbuserinrole SET role = ? WHERE username = ?");
    $role_query->bind_param("ss", $new_role, $username);
    $role_query->execute();

    header("Location: manage_role.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý tài khoản</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;

        }

        table thead {
            background-color: #2c3e50 !important;
            color: #fff;
        }

        table thead th {
            background-color: #2c3e50 !important;
            color: #fff !important;
        }

        table th,
        table td {
            padding: 12px 15px;
            text-align: center;
            border: 1px solid #dee2e6;
        }

        table tbody tr:nth-child(even) {
            background-color: #f7f9fc;
        }

        table tbody tr:hover {
            background-color: #eef1f5;
        }

        /* Form trong bảng */
        form.inline-form {
            margin: 0;
        }

        form.inline-form select {
            padding: 5px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            margin-right: 5px;
        }

        form.inline-form button {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            background-color: #ffc107;
            color: black;
            transition: background-color 0.3s ease;
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
            border-radius: 5px;
        }

        /* Responsive */
        @media (max-width: 768px) {

            table th,
            table td {
                padding: 8px 10px;
            }
        }
    </style>
</head>

<body>
    <div class="container-custom">
        <h2>Quản lý tài khoản</h2>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Tên</th>
                    <th>Quyền</th>
                    <th>Thay đổi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) : ?>
                    <tr>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['tenkhach'] ?? 'Chưa cập nhật') ?></td>
                        <td><?= htmlspecialchars($row['role_name']) ?></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="username" value="<?= htmlspecialchars($row['username']) ?>">
                                <select name="role">
                                    <option value="Member" <?= ($row['role_name'] == 'Member') ? 'selected' : '' ?>>Member</option>
                                    <option value="Admin" <?= ($row['role_name'] == 'Admin') ? 'selected' : '' ?>>Admin</option>
                                </select>
                                <button type="submit" name="update_role">Cập nhật</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

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
</body>

</html>
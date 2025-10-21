<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../public/login.html');
    exit();
}

global $db;

// Lấy danh sách người dùng
$db->query("SELECT id, username, email, fullname, address, phone_number, role, created_at FROM users ORDER BY created_at DESC");
$users = $db->resultSet();

// Hàm định dạng vai trò
function formatRole($role) {
    return $role === 'admin' ? 'Quản trị viên' : 'Khách hàng';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Người dùng - Admin Shop Đồng Hồ</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        /* admin_style.css */
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        .users-table th, .users-table td {
            border: 1px solid #eee;
            padding: 12px 15px;
            text-align: left;
            vertical-align: middle;
        }
        .users-table th {
            background-color: #f8f8f8;
            font-weight: bold;
            color: #333;
        }
        .users-table tr:hover {
            background-color: #f5f5f5;
        }
        .btn-edit-user {
            background-color: #007bff;
            color: #fff;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        .btn-edit-user:hover { background-color: #0056b3; }

        /* Modal for Edit User */
        .modal-body select {
            width: calc(100% - 22px);
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            margin-bottom: 15px;
        }
        .modal-body input[type="text"],
        .modal-body input[type="email"],
        .modal-body input[type="tel"] {
            width: calc(100% - 22px);
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <h2>Admin Panel</h2>
            <nav>
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="products.php">Quản lý Sản phẩm</a></li>
                    <li><a href="orders.php">Quản lý Đơn hàng</a></li>
                    <li><a href="users.php" class="active">Quản lý Người dùng</a></li>
                    <li><a href="#" id="admin-logout-btn" class="logout-btn-admin">Đăng Xuất</a></li>
                </ul>
            </nav>
        </aside>

        <main class="admin-main-content">
            <h1>Quản Lý Người Dùng</h1>

            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên đăng nhập</th>
                        <th>Email</th>
                        <th>Họ và tên</th>
                        <th>SĐT</th>
                        <th>Vai trò</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['fullname'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($user['phone_number'] ?? 'N/A'); ?></td>
                                <td><?php echo formatRole($user['role']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button class="btn-edit-user" data-user-id="<?php echo $user['id']; ?>"
                                        data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                        data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                        data-fullname="<?php echo htmlspecialchars($user['fullname']); ?>"
                                        data-phone="<?php echo htmlspecialchars($user['phone_number']); ?>"
                                        data-address="<?php echo htmlspecialchars($user['address']); ?>"
                                        data-role="<?php echo htmlspecialchars($user['role']); ?>">Sửa</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">Không có người dùng nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>

    <!-- Modal for Edit User -->
    <div id="editUserModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Sửa Thông Tin Người Dùng #<span id="edit-user-id"></span></h2>
            <div class="modal-body">
                <form id="edit-user-form">
                    <div class="form-group">
                        <label for="edit-username">Tên đăng nhập:</label>
                        <input type="text" id="edit-username" name="username" disabled>
                    </div>
                    <div class="form-group">
                        <label for="edit-email">Email:</label>
                        <input type="email" id="edit-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-fullname">Họ và tên:</label>
                        <input type="text" id="edit-fullname" name="fullname">
                    </div>
                    <div class="form-group">
                        <label for="edit-phone">Số điện thoại:</label>
                        <input type="tel" id="edit-phone" name="phone_number">
                    </div>
                    <div class="form-group">
                        <label for="edit-address">Địa chỉ:</label>
                        <textarea id="edit-address" name="address" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit-role">Vai trò:</label>
                        <select id="edit-role" name="role">
                            <option value="customer">Khách hàng</option>
                            <option value="admin">Quản trị viên</option>
                        </select>
                    </div>
                    <button type="submit" id="save-user-btn" class="btn-primary">Lưu Thay Đổi</button>
                </form>
            </div>
        </div>
    </div>

    <script src="../public/js/script.js"></script>
    <script>
        document.getElementById('admin-logout-btn').addEventListener('click', async (e) => {
            e.preventDefault();
            const response = await fetch('../api/auth.php?action=logout', { method: 'POST' });
            const result = await response.json();
            if (result.success) {
                localStorage.removeItem('user_id');
                localStorage.removeItem('username');
                localStorage.removeItem('user_role');
                window.location.href = '../public/login.html';
            } else {
                alert('Có lỗi khi đăng xuất: ' + result.message);
            }
        });

        const editUserModal = document.getElementById('editUserModal');
        const closeButtons = document.querySelectorAll('.close-button'); // Lấy tất cả nút đóng nếu có nhiều modal
        const editUserForm = document.getElementById('edit-user-form');
        let currentEditingUserId = null;

        // --- Hiển thị Modal sửa người dùng ---
        document.querySelectorAll('.btn-edit-user').forEach(button => {
            button.addEventListener('click', (e) => {
                currentEditingUserId = e.target.dataset.userId;
                document.getElementById('edit-user-id').textContent = currentEditingUserId;
                document.getElementById('edit-username').value = e.target.dataset.username;
                document.getElementById('edit-email').value = e.target.dataset.email;
                document.getElementById('edit-fullname').value = e.target.dataset.fullname;
                document.getElementById('edit-phone').value = e.target.dataset.phone;
                document.getElementById('edit-address').value = e.target.dataset.address;
                document.getElementById('edit-role').value = e.target.dataset.role;

                editUserModal.style.display = 'block';
            });
        });

        // --- Gắn sự kiện lưu thay đổi người dùng ---
        editUserForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!currentEditingUserId) return;

            const updatedData = {
                email: document.getElementById('edit-email').value,
                fullname: document.getElementById('edit-fullname').value,
                phone_number: document.getElementById('edit-phone').value,
                address: document.getElementById('edit-address').value,
                role: document.getElementById('edit-role').value
            };

            try {
                const response = await fetch(`../api/users.php?id=${currentEditingUserId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(updatedData)
                });
                const result = await response.json();
                showToast(result.message, result.success ? 'success' : 'error');
                if (result.success) {
                    editUserModal.style.display = 'none';
                    location.reload(); // Tải lại trang để thấy thay đổi
                }
            } catch (error) {
                console.error('Error updating user:', error);
                showToast('Đã xảy ra lỗi khi cập nhật người dùng.', 'error');
            }
        });

        // --- Đóng Modal chung ---
        closeButtons.forEach(button => {
            button.addEventListener('click', () => {
                editUserModal.style.display = 'none';
                // Đóng thêm các modal khác nếu có
            });
        });
        window.addEventListener('click', (event) => {
            if (event.target === editUserModal) {
                editUserModal.style.display = 'none';
            }
        });
    </script>
</body>
</html>
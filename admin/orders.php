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

// Lấy danh sách đơn hàng
$status_filter = $_GET['status'] ?? '';
$sql = "SELECT o.id, o.order_date, o.total_amount, o.status, u.fullname as customer_name, u.email as customer_email
        FROM orders o
        JOIN users u ON o.user_id = u.id";
$bindings = [];
if (!empty($status_filter)) {
    $sql .= " WHERE o.status = :status_filter";
    $bindings[':status_filter'] = $status_filter;
}
$sql .= " ORDER BY o.order_date DESC";

$db->query($sql);
foreach($bindings as $key => $value) {
    $db->bind($key, $value);
}
$orders = $db->resultSet();

// Hàm định dạng trạng thái
function formatOrderStatusAdmin($status) {
    $statusMap = [
        'pending' => 'Chờ xác nhận',
        'processing' => 'Đang xử lý',
        'shipped' => 'Đang giao hàng',
        'delivered' => 'Đã giao hàng',
        'cancelled' => 'Đã hủy'
    ];
    return $statusMap[$status] ?? $status;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn hàng - Admin Shop Đồng Hồ</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        /* admin_style.css */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .admin-header select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden; /* Để bo góc table */
        }
        .orders-table th, .orders-table td {
            border: 1px solid #eee;
            padding: 12px 15px;
            text-align: left;
            vertical-align: middle;
        }
        .orders-table th {
            background-color: #f8f8f8;
            font-weight: bold;
            color: #333;
        }
        .orders-table tr:hover {
            background-color: #f5f5f5;
        }
        .order-status.pending { color: orange; font-weight: bold; }
        .order-status.processing { color: #007bff; font-weight: bold; }
        .order-status.shipped { color: #28a745; font-weight: bold; }
        .order-status.delivered { color: green; font-weight: bold; }
        .order-status.cancelled { color: red; font-weight: bold; }

        .btn-view-order, .btn-update-status {
            background-color: #007bff;
            color: #fff;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            margin-right: 5px;
            transition: background-color 0.3s ease;
        }
        .btn-view-order:hover { background-color: #0056b3; }
        .btn-update-status { background-color: #28a745; }
        .btn-update-status:hover { background-color: #218838; }

        .modal-content select {
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
                    <li><a href="orders.php" class="active">Quản lý Đơn hàng</a></li>
                    <li><a href="users.php">Quản lý Người dùng</a></li>
                    <li><a href="#" id="admin-logout-btn" class="logout-btn-admin">Đăng Xuất</a></li>
                </ul>
            </nav>
        </aside>

        <main class="admin-main-content">
            <h1>Quản Lý Đơn Hàng</h1>

            <div class="admin-header">
                <form method="GET" action="orders.php">
                    <label for="status-filter">Lọc theo trạng thái:</label>
                    <select name="status" id="status-filter" onchange="this.form.submit()">
                        <option value="">Tất cả</option>
                        <option value="pending" <?php echo ($status_filter === 'pending' ? 'selected' : ''); ?>>Chờ xác nhận</option>
                        <option value="processing" <?php echo ($status_filter === 'processing' ? 'selected' : ''); ?>>Đang xử lý</option>
                        <option value="shipped" <?php echo ($status_filter === 'shipped' ? 'selected' : ''); ?>>Đang giao hàng</option>
                        <option value="delivered" <?php echo ($status_filter === 'delivered' ? 'selected' : ''); ?>>Đã giao hàng</option>
                        <option value="cancelled" <?php echo ($status_filter === 'cancelled' ? 'selected' : ''); ?>>Đã hủy</option>
                    </select>
                </form>
            </div>

            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Mã ĐH</th>
                        <th>Khách hàng</th>
                        <th>Email</th>
                        <th>Ngày đặt</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
                                <td><?php echo number_format($order['total_amount'], 0, ',', '.') . '₫'; ?></td>
                                <td><span class="order-status <?php echo htmlspecialchars($order['status']); ?>"><?php echo formatOrderStatusAdmin($order['status']); ?></span></td>
                                <td>
                                    <button class="btn-view-order" data-order-id="<?php echo $order['id']; ?>">Xem</button>
                                    <button class="btn-update-status" data-order-id="<?php echo $order['id']; ?>" data-current-status="<?php echo $order['status']; ?>">Cập nhật</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">Không có đơn hàng nào phù hợp.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>

    <!-- Modal for Order Detail -->
    <div id="orderDetailModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Chi Tiết Đơn Hàng <span id="modal-order-id"></span></h2>
            <div class="modal-body">
                <p><strong>Ngày đặt:</strong> <span id="modal-order-date"></span></p>
                <p><strong>Khách hàng:</strong> <span id="modal-customer-name"></span></p>
                <p><strong>Email:</strong> <span id="modal-customer-email"></span></p>
                <p><strong>Địa chỉ giao hàng:</strong> <span id="modal-shipping-address"></span></p>
                <p><strong>Số điện thoại:</strong> <span id="modal-phone-number"></span></p>
                <p><strong>Phương thức thanh toán:</strong> <span id="modal-payment-method"></span></p>
                <p><strong>Trạng thái:</strong> <span id="modal-order-status"></span></p>
                <p><strong>Ghi chú:</strong> <span id="modal-order-notes"></span></p>

                <h3>Sản phẩm</h3>
                <ul id="modal-order-items">
                    <!-- Order items will be loaded here -->
                </ul>
                <div class="modal-total">
                    Tổng cộng: <span id="modal-order-total-amount"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Update Status -->
    <div id="updateStatusModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Cập Nhật Trạng Thái Đơn Hàng #<span id="update-modal-order-id"></span></h2>
            <div class="modal-body">
                <label for="new-status">Chọn trạng thái mới:</label>
                <select id="new-status">
                    <option value="pending">Chờ xác nhận</option>
                    <option value="processing">Đang xử lý</option>
                    <option value="shipped">Đang giao hàng</option>
                    <option value="delivered">Đã giao hàng</option>
                    <option value="cancelled">Đã hủy</option>
                </select>
                <button id="save-status-btn" class="btn-primary">Lưu</button>
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

        const orderDetailModal = document.getElementById('orderDetailModal');
        const updateStatusModal = document.getElementById('updateStatusModal');
        const closeButtons = document.querySelectorAll('.close-button');
        const saveStatusBtn = document.getElementById('save-status-btn');
        let currentOrderIdToUpdate = null;

        // Hàm format trạng thái đơn hàng (được dùng lại từ profile.html)
        function formatOrderStatus(status) {
            const statusMap = {
                'pending': 'Chờ xác nhận',
                'processing': 'Đang xử lý',
                'shipped': 'Đang giao hàng',
                'delivered': 'Đã giao hàng',
                'cancelled': 'Đã hủy'
            };
            return statusMap[status] || status;
        }
         function formatPaymentMethod(method) {
            const methodMap = {
                'COD': 'Thanh toán khi nhận hàng',
                'Bank Transfer': 'Chuyển khoản ngân hàng',
                'MoMo': 'Ví điện tử MoMo',
                'ZaloPay': 'Ví điện tử ZaloPay',
                'Credit Card': 'Thẻ tín dụng'
            };
            return methodMap[method] || method;
        }

        // --- Hiển thị chi tiết đơn hàng (Modal) ---
        document.querySelectorAll('.btn-view-order').forEach(button => {
            button.addEventListener('click', async (e) => {
                const orderId = e.target.dataset.orderId;
                try {
                    const response = await fetch(`../api/orders.php?id=${orderId}`);
                    const result = await response.json();

                    if (result.success && result.data) {
                        const order = result.data;
                        document.getElementById('modal-order-id').textContent = `#${order.id}`;
                        document.getElementById('modal-order-date').textContent = new Date(order.order_date).toLocaleString('vi-VN');
                        document.getElementById('modal-customer-name').textContent = escapeHTML(order.customer_name || 'N/A');
                        document.getElementById('modal-customer-email').textContent = escapeHTML(order.customer_email || 'N/A');
                        document.getElementById('modal-shipping-address').textContent = escapeHTML(order.shipping_address);
                        document.getElementById('modal-phone-number').textContent = escapeHTML(order.phone_number);
                        document.getElementById('modal-payment-method').textContent = formatPaymentMethod(order.payment_method);
                        document.getElementById('modal-order-status').textContent = formatOrderStatus(order.status);
                        document.getElementById('modal-order-status').className = `order-status ${order.status}`;
                        document.getElementById('modal-order-notes').textContent = escapeHTML(order.notes || 'Không có');
                        document.getElementById('modal-order-total-amount').textContent = formatCurrency(order.total_amount);

                        const orderItemsList = document.getElementById('modal-order-items');
                        orderItemsList.innerHTML = '';
                        order.items.forEach(item => {
                            const listItem = document.createElement('li');
                            listItem.innerHTML = `
                                <img src="${item.image_url ? '../public/images/products/' + item.image_url : '../public/images/placeholder.jpg'}" alt="${escapeHTML(item.product_name)}">
                                <div class="item-info">
                                    ${escapeHTML(item.product_name)} (x${item.quantity})
                                </div>
                                <span class="item-price">${formatCurrency(item.price * item.quantity)}</span>
                            `;
                            orderItemsList.appendChild(listItem);
                        });
                        orderDetailModal.style.display = 'block';
                    } else {
                        showToast(result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error fetching order detail:', error);
                    showToast('Đã xảy ra lỗi khi tải chi tiết đơn hàng.', 'error');
                }
            });
        });

        // --- Cập nhật trạng thái đơn hàng (Modal) ---
        document.querySelectorAll('.btn-update-status').forEach(button => {
            button.addEventListener('click', (e) => {
                currentOrderIdToUpdate = e.target.dataset.orderId;
                const currentStatus = e.target.dataset.currentStatus;
                document.getElementById('update-modal-order-id').textContent = currentOrderIdToUpdate;
                document.getElementById('new-status').value = currentStatus; // Đặt giá trị mặc định là trạng thái hiện tại
                updateStatusModal.style.display = 'block';
            });
        });

        saveStatusBtn.addEventListener('click', async () => {
            if (!currentOrderIdToUpdate) return;

            const newStatus = document.getElementById('new-status').value;
            try {
                const response = await fetch(`../api/orders.php?id=${currentOrderIdToUpdate}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status: newStatus })
                });
                const result = await response.json();
                showToast(result.message, result.success ? 'success' : 'error');
                if (result.success) {
                    updateStatusModal.style.display = 'none';
                    location.reload(); // Tải lại trang để thấy thay đổi
                }
            } catch (error) {
                console.error('Error updating order status:', error);
                showToast('Đã xảy ra lỗi khi cập nhật trạng thái.', 'error');
            }
        });

        // --- Đóng Modal chung ---
        closeButtons.forEach(button => {
            button.addEventListener('click', () => {
                orderDetailModal.style.display = 'none';
                updateStatusModal.style.display = 'none';
            });
        });
        window.addEventListener('click', (event) => {
            if (event.target === orderDetailModal) {
                orderDetailModal.style.display = 'none';
            }
            if (event.target === updateStatusModal) {
                updateStatusModal.style.display = 'none';
            }
        });
    </script>
</body>
</html>
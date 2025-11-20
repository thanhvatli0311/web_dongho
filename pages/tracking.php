<?php
session_start();
// Đảm bảo file này khởi tạo biến $pdo (đối tượng PDO)
include '../includes/db.php'; 

// Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

// Kiểm tra $pdo đã được định nghĩa chưa sau khi include
if (!isset($pdo)) {
    // Lỗi nghiêm trọng nếu $pdo không tồn tại
    die("Lỗi: Biến \$pdo (kết nối CSDL) không được định nghĩa. Vui lòng kiểm tra file db.php.");
}

include '../templates/header.php'; // Đảm bảo tệp này có mở thẻ <body>

$username = $_SESSION['username'];

try {
    // 1. Lấy thông tin khách hàng từ bảng tbKhachHang dựa trên username
    $customer_sql = "SELECT makhach FROM tbKhachHang WHERE username = ?";
    $stmt = $pdo->prepare($customer_sql);
    // PDO: Dùng execute với mảng các tham số
    $stmt->execute([$username]); 
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        echo "Khách hàng không tồn tại trong hệ thống!";
        include '../templates/footer.php';
        exit;
    }

    $makhach = $customer['makhach']; 

    // 2. Lấy danh sách đơn hàng của khách hàng (sắp xếp theo ngày mua giảm dần)
    $order_sql = "SELECT madonhang, ngaymua, tinhtrang FROM tbDonHang WHERE makhach = ? ORDER BY ngaymua DESC";
    $stmt_order = $pdo->prepare($order_sql);
    $stmt_order->execute([$makhach]);
    $orders = $stmt_order->fetchAll(PDO::FETCH_ASSOC); // PDO: Lấy tất cả đơn hàng vào mảng

    // 3. Tính tổng giá trị cho từng đơn hàng
    $order_data = [];
    foreach ($orders as $order) {
        $madonhang = $order['madonhang'];
        
        $total_sql = "SELECT SUM(dongia) AS total FROM tbChiTietDonHang WHERE madonhang = ?";
        $stmt_total = $pdo->prepare($total_sql);
        $stmt_total->execute([$madonhang]);
        $total_row = $stmt_total->fetch(PDO::FETCH_ASSOC);
        $total_amount = $total_row['total'] ?? 0;
        
        $order['total_amount'] = $total_amount;
        $order_data[] = $order;
    }

} catch (\PDOException $e) {
    // Xử lý lỗi PDO
    echo "Lỗi truy vấn CSDL: " . $e->getMessage();
    include '../templates/footer.php';
    exit;
}

?>

<style>
/* ==========================================================
   CSS CHO GIAO DIỆN TRACKING.PHP (GIỮ NGUYÊN)
   ========================================================== */
.tracking-container { max-width: 800px; margin: 30px auto; padding: 20px; background-color: #fff; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
h2 { color: #007bff; text-align: center; margin-bottom: 25px; }

/* Phần nhập liệu tra cứu */
#tracking-form { display: flex; justify-content: center; gap: 10px; margin-bottom: 30px; }
#tracking-form input[type="text"] { padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 16px; width: 60%; max-width: 400px; }
#tracking-form button { padding: 12px 20px; background-color: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; transition: background-color 0.3s; }
#tracking-form button:hover { background-color: #1e7e34; }

/* Danh sách đơn hàng */
.order-list-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 40px; }
.order-item { border: 1px solid #ddd; padding: 15px; border-radius: 10px; background: #f9f9f9; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
.order-item strong { color: #333; }
.track-button { 
    margin-top: 10px; 
    padding: 8px 15px; 
    background-color: #007bff; 
    color: white; 
    border: none; 
    border-radius: 5px; 
    cursor: pointer; 
    transition: background-color 0.3s;
}
.track-button:hover { background-color: #0056b3; }

/* Kết quả và Loader */
#tracking-results { padding: 20px; border: 1px solid #eee; border-radius: 8px; min-height: 100px; position: relative; }
.current-status { background-color: #e6f7ff; color: #007bff; padding: 15px; border-radius: 5px; margin-bottom: 25px; font-size: 1.2em; font-weight: bold; text-align: center; }
.error-message { color: #dc3545; background-color: #f8d7da; padding: 10px; border-radius: 5px; text-align: center; }

/* Loader CSS */
.loader {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    animation: spin 1s linear infinite;
    margin: 20px auto;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.hidden { display: none; }


/* Timeline CSS (Lịch sử theo dõi) */
.timeline { border-left: 3px solid #007bff; padding-left: 20px; position: relative; margin-top: 20px; }
.timeline-item { margin-bottom: 25px; position: relative; padding-left: 15px; }
.timeline-item::before { 
    content: ''; 
    position: absolute; 
    left: -29px; 
    top: 5px; 
    width: 12px; 
    height: 12px; 
    background-color: #007bff; 
    border-radius: 50%; 
    border: 3px solid #fff; 
    box-shadow: 0 0 0 2px #007bff;
}
.timeline-item:first-child::before { /* Highlight trạng thái mới nhất */
    background-color: #28a745; 
    box-shadow: 0 0 0 2px #28a745;
}
.timeline-date { color: #6c757d; font-size: 0.9em; }
.timeline-detail { margin-top: 5px; font-style: italic; color: #555; }

</style>

<div class="tracking-container">
    <h2>🔎 Theo Dõi Đơn Hàng</h2>

    <form id="tracking-form" onsubmit="trackOrder(event)">
        <input type="text" id="madonhang-input" placeholder="Hoặc nhập Mã Đơn Hàng để tra cứu" required>
        <button type="submit">Kiểm tra đơn hàng</button>
    </form>

    <div id="tracking-results">
        <p style="text-align: center; color: #777;">Nhập mã đơn hàng hoặc nhấn nút "Theo Dõi" bên dưới.</p>
    </div>

    <hr style="margin: 30px 0;">

    <h3 style="text-align: center; color: #333;">Đơn hàng của bạn</h3>
    
    <?php if (!empty($order_data)): ?>
    <div class="order-list-container">
        <?php foreach ($order_data as $order): ?>
            <div class="order-item" id="order-<?php echo htmlspecialchars($order['madonhang']); ?>">
                <p><strong>Mã đơn hàng:</strong> <?php echo htmlspecialchars($order['madonhang']); ?></p>
                <p><strong>Ngày mua:</strong> <?php echo date('d/m/Y H:i:s', strtotime($order['ngaymua'])); ?></p>
                <p><strong>Tình trạng:</strong> <span id="status-<?php echo htmlspecialchars($order['madonhang']); ?>" style="font-weight: bold; color: #007bff;"><?php echo htmlspecialchars($order['tinhtrang']); ?></span></p>
                
                <p><strong>Tổng giá trị:</strong> <?php echo number_format($order['total_amount']) . " VND"; ?></p>
                
                <button class="track-button" onclick="trackOrder(event, '<?php echo htmlspecialchars($order['madonhang']); ?>')">
                    Theo Dõi Vận Chuyển
                </button>
            </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <p style='text-align: center; font-size: 18px; color: #777;'>Bạn chưa có đơn hàng nào.</p>
    <?php endif; ?>

</div> <script>
    // ==========================================================
    // JAVASCRIPT/AJAX XỬ LÝ THEO DÕI ĐƠN HÀNG (GIỮ NGUYÊN)
    // ==========================================================
    
    /**
     * Hàm gọi AJAX đến api_tracking.php
     */
    function trackOrder(event, madonhang = null) {
        if (event) event.preventDefault();
        
        const resultsDiv = document.getElementById('tracking-results');
        let orderId = madonhang;

        // Nếu gọi từ form input, lấy giá trị từ đó
        if (!orderId) {
            orderId = document.getElementById('madonhang-input').value.trim();
        }

        if (!orderId) {
            resultsDiv.innerHTML = '<p class="error-message">Vui lòng nhập mã đơn hàng.</p>';
            return;
        }

        // 1. Hiển thị trạng thái Loader
        resultsDiv.innerHTML = '<div class="loader"></div><p style="text-align: center; margin-top: 10px;">Đang tra cứu trạng thái đơn hàng...</p>';

        // 2. Gọi AJAX
        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'api_tracking.php?madonhang=' + orderId, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    renderTrackingResults(data, orderId);
                } catch (e) {
                    resultsDiv.innerHTML = '<p class="error-message">❌ Lỗi xử lý dữ liệu từ server.</p>';
                    console.error('Lỗi Parse JSON:', e);
                }
            } else {
                resultsDiv.innerHTML = '<p class="error-message">❌ Lỗi kết nối Server: HTTP Status ' + xhr.status + '</p>';
            }
        };

        xhr.onerror = function() {
            resultsDiv.innerHTML = '<p class="error-message">❌ Lỗi mạng: Không thể kết nối đến máy chủ.</p>';
        };
        
        xhr.send();
    }

    /**
     * Hàm hiển thị kết quả theo dõi lên giao diện
     */
    function renderTrackingResults(data, madonhang) {
        const resultsDiv = document.getElementById('tracking-results');
        
        // 3. Xử lý Lỗi từ API
        if (data.error) {
            resultsDiv.innerHTML = '<p class="error-message">❌ Lỗi: Mã đơn hàng ' + madonhang + ' ' + data.error + '</p>';
            return;
        }

        // Xử lý Cảnh báo
        let warningHTML = '';
        if (data.warning) {
             warningHTML = `<p class="error-message" style="background-color: #ffc107; color: #333;">⚠️ Cảnh báo: ${data.warning}</p>`;
        }
        
        // 4. Hiển thị Trạng thái Hiện tại
        let htmlContent = `
            ${warningHTML}
            <div class="current-status">Mã đơn hàng: ${madonhang} | Trạng thái: ${data.current_status || 'Không rõ'}</div>
            
            <h3>Lịch sử Vận chuyển</h3>
        `;

        // Cập nhật trạng thái tổng quát trong danh sách đơn hàng (nếu có)
        const statusSpan = document.getElementById('status-' + madonhang);
        if (statusSpan) {
            statusSpan.textContent = data.current_status;
            document.querySelector('.tracking-container').scrollIntoView({ behavior: 'smooth' });
        }

        // 5. Hiển thị Lịch sử (Timeline)
        if (data.history && data.history.length > 0) {
            htmlContent += '<div class="timeline">';
            data.history.forEach(item => {
                const date = new Date(item.thoigian).toLocaleString('vi-VN');
                const description = item.mo_ta ? item.mo_ta : 'Không có mô tả chi tiết.';
                htmlContent += `
                    <div class="timeline-item">
                        <div class="timeline-date">${date}</div>
                        <strong>${item.trangthai_ghtk}</strong>
                        <div class="timeline-detail">${description}</div>
                    </div>
                `;
            });
            htmlContent += '</div>';
        } else {
             htmlContent += '<p style="text-align: center;">Chưa có dữ liệu lịch sử vận chuyển chi tiết.</p>';
        }

        resultsDiv.innerHTML = htmlContent;
    }
</script>

<?php 
// Trong PDO, không cần gọi $pdo->close() mà kết nối tự động đóng khi script kết thúc
include '../templates/footer.php';
?>
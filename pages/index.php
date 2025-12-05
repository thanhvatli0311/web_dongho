<?php
// Bắt đầu session và include các file cần thiết
include '../session/session_start.php';
// GIẢ ĐỊNH: File db.php trả về biến kết nối PDO là $pdo
include '../includes/db.php';
include '../templates/header.php';

// Kiểm tra biến $pdo
if (!isset($pdo)) {
    die("Lỗi: Không thể kết nối CSDL (PDO). Vui lòng kiểm tra file includes/db.php.");
}

$cart_count = isset($_SESSION['cart']) && !empty($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

$search = trim($_GET['search'] ?? "");
$brand = trim($_GET['brand'] ?? "");
$price_range = trim($_GET['price_range'] ?? "");
$sort = trim(($_GET['sort'] ?? ""));
$seed = (int)($_GET['seed'] ?? mt_rand());
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cửa Hàng Đồng Hồ Cao Cấp</title>
    
    <!-- CSS Thư viện -->
    <link rel="stylesheet" href="../LIB/fontawesome-free-6.4.2-web/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.css" />
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.css" />
    
    <!-- CSS Chính -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- CSS CHATBOT (NHÚNG TRỰC TIẾP) -->
    <style>
        /* 1. Nút mở Chat */
        .chat-bubble {
            position: fixed; bottom: 30px; right: 30px; width: 60px; height: 60px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            border-radius: 50%; box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; z-index: 9999;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            animation: float 3s ease-in-out infinite;
        }
        .chat-bubble:hover { transform: scale(1.1) rotate(5deg); box-shadow: 0 6px 20px rgba(0, 123, 255, 0.6); }
        .chat-bubble i { color: white; font-size: 28px; }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0px); }
        }

        /* 2. Khung cửa sổ Chat */
        .chat-window {
            display: none; /* JS sẽ bật thành flex */
            position: fixed; bottom: 100px; right: 30px; width: 360px; height: 500px;
            max-height: 80vh; background-color: #ffffff; border-radius: 16px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.15);
            flex-direction: column; overflow: hidden; z-index: 9999;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            border: 1px solid #f0f0f0; animation: slideUp 0.3s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* 3. Header */
        .chat-header {
            background: linear-gradient(135deg, #007bff, #004494);
            padding: 15px 20px; color: white; display: flex;
            justify-content: space-between; align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .chat-header p { margin: 0; font-weight: 600; font-size: 16px; display: flex; align-items: center; gap: 8px; }
        .close-chat-btn {
            background: transparent; border: none; color: rgba(255, 255, 255, 0.8);
            font-size: 24px; cursor: pointer; line-height: 1; transition: 0.2s;
        }
        .close-chat-btn:hover { color: #fff; transform: rotate(90deg); }

        /* 4. Body */
        .chat-body {
            flex-grow: 1; padding: 15px; background-color: #f8f9fa;
            overflow-y: auto; display: flex; flex-direction: column; gap: 10px; scroll-behavior: smooth;
        }
        .chat-body::-webkit-scrollbar { width: 6px; }
        .chat-body::-webkit-scrollbar-thumb { background-color: #ccc; border-radius: 3px; }

        /* 5. Messages */
        .chat-message {
            padding: 10px 14px; border-radius: 18px; font-size: 14px; line-height: 1.5;
            max-width: 80%; word-wrap: break-word; position: relative; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .chat-message.bot {
            background-color: #ffffff; color: #333; align-self: flex-start;
            border-bottom-left-radius: 4px; border: 1px solid #e9ecef;
        }
        .chat-message.user {
            background-color: #007bff; color: white; align-self: flex-end;
            border-bottom-right-radius: 4px;
        }
        .typing-indicator {
            font-size: 12px; color: #888; margin-left: 15px; margin-bottom: 5px; font-style: italic; display: none;
        }

        /* 6. Footer */
        .chat-footer {
            padding: 12px 15px; background-color: #fff; border-top: 1px solid #eee;
            display: flex; align-items: center; gap: 10px;
        }
        #chat-input {
            flex-grow: 1; padding: 10px 15px; border: 1px solid #ddd;
            border-radius: 25px; outline: none; font-size: 14px; transition: border-color 0.3s;
        }
        #chat-input:focus { border-color: #007bff; }
        #send-btn {
            width: 40px; height: 40px; border: none; background-color: #007bff;
            color: white; border-radius: 50%; cursor: pointer; display: flex;
            align-items: center; justify-content: center; transition: background 0.3s, transform 0.2s;
        }
        #send-btn:hover { background-color: #0056b3; transform: scale(1.05); }
        #send-btn i { font-size: 16px; margin-left: -2px; }

        @media (max-width: 480px) {
            .chat-window { width: 90%; bottom: 80px; right: 5%; height: 60vh; }
            .chat-bubble { bottom: 20px; right: 20px; }
        }
    </style>
</head>

<body>

    <div class="hero">
        <div class="slideshow-container">
            <div class="mySlides fade">
                <div class="numbertext">1 / 4</div>
                <img src="../assets/images/rolex_banner2.jpg" alt="Banner 1">
            </div>
            <div class="mySlides fade">
                <div class="numbertext">2 / 4</div>
                <img src="../assets/images/banner4.jpg" alt="Banner 2">
            </div>
            <div class="mySlides fade">
                <div class="numbertext">3 / 4</div>
                <img src="../assets/images/nu_banner.jpg" alt="Banner 3">
            </div>
            <div class="mySlides fade">
                <div class="numbertext">4 / 4</div>
                <img src="../assets/images/rolex_banner3.jpg" alt="Banner 4">
            </div>
            <a class="prev" onclick="plusSlides(-1)">❮</a>
            <a class="next" onclick="plusSlides(1)">❯</a>
        </div>
        <br>
        <div style="text-align:center">
            <span class="dot" onclick="currentSlide(1)"></span>
            <span class="dot" onclick="currentSlide(2)"></span>
            <span class="dot" onclick="currentSlide(3)"></span>
        </div>
    </div>

    <section class="highest-price-carousel">
        <h2>Sản phẩm nổi bật</h2>
        <div class="carousel-container">
            <?php
            $sql_highest = "SELECT * FROM tbmathang ORDER BY dongia DESC LIMIT 7";
            $result_highest = $pdo->query($sql_highest);
            while ($row_high = $result_highest->fetch(PDO::FETCH_ASSOC)) {
                $hinhanh = !empty($row_high['hinhanh']) && file_exists("../assets/images/" . $row_high['hinhanh']) ? "../assets/images/" . $row_high['hinhanh'] : "../assets/images/default.jpg";
            ?>
                <div class="carousel-item">
                    <a href="product_detail.php?mahang=<?php echo urlencode($row_high['mahang']); ?>">
                        <img src="<?php echo $hinhanh; ?>" alt="<?php echo htmlspecialchars($row_high['tenhang']); ?>">
                        <h3><?php echo htmlspecialchars($row_high['tenhang']); ?></h3>
                        <span class="price"><?php echo number_format($row_high['dongia'], 0, ',', '.'); ?> VND</span>
                    </a>
                </div>
            <?php } ?>
        </div>
    </section>

    <aside class="sidebar">
        <h3>Lọc theo</h3>
        <form action="index.php" method="GET">
            <div class="filter-group">
                <label for="brand">Hãng:</label>
                <select name="brand" id="brand">
                    <option value="">Tất cả</option>
                    <?php
                    $brands_sql = "SELECT DISTINCT thuonghieu FROM tbmathang WHERE thuonghieu IS NOT NULL";
                    $brands_result = $pdo->query($brands_sql);
                    while ($brand_row = $brands_result->fetch(PDO::FETCH_ASSOC)) { ?>
                        <option value="<?php echo htmlspecialchars($brand_row['thuonghieu']); ?>" <?php echo $brand === $brand_row['thuonghieu'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($brand_row['thuonghieu']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="price_range">Khoảng giá:</label>
                <select name="price_range" id="price_range">
                    <option value="">Tất cả</option>
                    <option value="0-50000000" <?php echo $price_range === '0-50000000' ? 'selected' : ''; ?>>Dưới 50 triệu</option>
                    <option value="50000000-200000000" <?php echo $price_range === '50000000-200000000' ? 'selected' : ''; ?>>50 triệu - 200 triệu</option>
                    <option value="200000000-500000000" <?php echo $price_range === '200000000-500000000' ? 'selected' : ''; ?>>200 triệu - 500 triệu</option>
                    <option value="500000000-1000000000" <?php echo $price_range === '500000000-1000000000' ? 'selected' : ''; ?>>500 triệu - 1 tỷ</option>
                    <option value="1000000000+" <?php echo $price_range === '1000000000+' ? 'selected' : ''; ?>>Trên 1 tỷ</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="sort">Sắp xếp:</label>
                <select name="sort" id="sort">
                    <option value="" <?php echo $sort === "" ? 'selected' : ''; ?>>Mặc định</option>
                    <option value="price_asc" <?php echo $sort === "price_asc" ? 'selected' : ''; ?>>Giá tăng dần</option>
                    <option value="price_desc" <?php echo $sort === "price_desc" ? 'selected' : ''; ?>>Giá giảm dần</option>
                </select>
            </div>
            <input type="hidden" name="seed" value="<?php echo $seed; ?>">
            <button type="submit">Lọc sản phẩm</button>
        </form>
    </aside>

    <section class="product-listing">
        <div class="product-grid">
            <?php
            $limit = 8;
            $page = (int)($_GET['page'] ?? 1);
            $offset = ($page - 1) * $limit;

            $where = "WHERE tenhang LIKE :search";
            $params = [':search' => "%$search%"];

            if (!empty($brand)) {
                $where .= " AND thuonghieu = :brand";
                $params[':brand'] = $brand;
            }

            if (!empty($price_range)) {
                switch ($price_range) {
                    case '0-50000000': $where .= " AND dongia <= 50000000"; break;
                    case '50000000-200000000': $where .= " AND dongia BETWEEN 50000000 AND 200000000"; break;
                    case '200000000-500000000': $where .= " AND dongia BETWEEN 200000000 AND 500000000"; break;
                    case '500000000-1000000000': $where .= " AND dongia BETWEEN 500000000 AND 1000000000"; break;
                    case '1000000000+': $where .= " AND dongia > 1000000000"; break;
                }
            }

            $order_by = "ORDER BY RAND($seed)";
            if ($sort === "price_asc") { $order_by = "ORDER BY dongia ASC"; }
            elseif ($sort === "price_desc") { $order_by = "ORDER BY dongia DESC"; }
            
            $sql = "SELECT * FROM tbmathang $where $order_by LIMIT :limit OFFSET :offset";

            try {
                $stmt = $pdo->prepare($sql);
                foreach ($params as $key => $value) { $stmt->bindValue($key, $value); }
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die("Lỗi truy vấn dữ liệu (sản phẩm): " . $e->getMessage());
            }

            foreach ($result as $row) {
                $hinhanh = !empty($row['hinhanh']) && file_exists("../assets/images/" . $row['hinhanh']) ? "../assets/images/" . $row['hinhanh'] : "../assets/images/default.jpg";
                $disabled = (strtolower(trim($row['conhang'])) === 'hết hàng') ? 'disabled' : '';
            ?>
                <div class="product-card">
                    <a href="product_detail.php?mahang=<?php echo urlencode($row['mahang']); ?>">
                        <img src="<?php echo $hinhanh; ?>" alt="<?php echo htmlspecialchars($row['tenhang']); ?>">
                    </a>
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($row['tenhang']); ?></h3>
                        <div class="action-group">
                            <span class="price"><?php echo number_format($row['dongia'], 0, ',', '.'); ?> VND</span>
                            <button class="add-to-cart" data-id="<?php echo $row['mahang']; ?>" <?php echo $disabled; ?>>
                                <?php echo $disabled ? "Hết hàng" : '<i class="fas fa-cart-plus"></i> Thêm vào giỏ'; ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </section>

    <div class="pagination">
        <?php
        $sql_total = "SELECT COUNT(*) AS count FROM tbmathang $where";
        try {
            $stmt_total = $pdo->prepare($sql_total);
            $stmt_total->execute($params);
            $total_items = $stmt_total->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (PDOException $e) { die("Lỗi truy vấn tổng số: " . $e->getMessage()); }
        $total_pages = ceil($total_items / $limit) ?: 1;
        $pagination_query_params = '&seed=' . $seed . '&search=' . urlencode($search) . '&brand=' . urlencode($brand) . '&price_range=' . urlencode($price_range) . '&sort=' . urlencode($sort);

        if ($page > 1) { echo '<a href="index.php?page=' . ($page - 1) . $pagination_query_params . '">« Trước</a>'; }
        for ($i = 1; $i <= $total_pages; $i++) {
            $active = ($i == $page) ? 'active' : '';
            echo '<a class="' . $active . '" href="index.php?page=' . $i . $pagination_query_params . '">' . $i . '</a>';
        }
        if ($page < $total_pages) { echo '<a href="index.php?page=' . ($page + 1) . $pagination_query_params . '">Sau »</a>'; }
        ?>
    </div>

    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i> Sản phẩm đã được thêm vào giỏ hàng!
    </div>

    <!-- ================== WIDGET CHATBOT (HTML) ================== -->
    <div id="chat-bubble" class="chat-bubble">
        <i class="fas fa-comment-dots"></i>
    </div>

    <div id="chat-window" class="chat-window">
        <div class="chat-header">
            <p>🤖 Trợ lý ảo & Hỗ trợ</p>
            <button id="close-chat" class="close-chat-btn">&times;</button>
        </div>
        
        <div id="chat-body" class="chat-body">
            <!-- Tin nhắn chào mừng -->
            <div class="chat-message bot">
                Xin chào! Em có thể giúp gì cho anh/chị ạ?
            </div>
        </div>

        <div id="typing-indicator" class="typing-indicator">Đang trả lời...</div>

        <div class="chat-footer">
            <input type="text" id="chat-input" placeholder="Nhập tin nhắn..." autocomplete="off">
            <button id="send-btn"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
    <!-- ================== END WIDGET ================== -->

    <?php include '../templates/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js"></script>
    
    <!-- ================== JAVASCRIPT CHATBOT (NHÚNG TRỰC TIẾP) ================== -->
    <script>
    $(document).ready(function() {
        // === 1. LOGIC WEBSITE (SLIDER & CART) ===
        $(".add-to-cart").click(function() {
            if ($(this).is(':disabled')) return;
            var item_id = $(this).data("id");
            $.ajax({
                url: "cart.php", type: "GET", data: { add_to_cart: item_id, get_count: true },
                success: function(response) {
                    var new_cart_count = parseInt(response);
                    $("#toast").fadeIn(400).delay(1500).fadeOut(400);
                    if (typeof updateCartDisplay === 'function') { updateCartDisplay(new_cart_count); }
                    else {
                        var badge = $("#header-cart-count");
                        if (badge.length === 0) badge = $(".cart-box .cart-count");
                        if (new_cart_count > 0) {
                            if (badge.length === 0) $(".cart-box a").append('<span class="cart-count">' + new_cart_count + '</span>');
                            else badge.text(new_cart_count).show();
                        } else badge.hide();
                    }
                },
                error: function() { alert("Có lỗi xảy ra, vui lòng thử lại!"); }
            });
        });

        $('.carousel-container').slick({
            slidesToShow: 4, slidesToScroll: 4, infinite: true, autoplay: true, autoplaySpeed: 3000, arrows: true,
            prevArrow: '<button type="button" class="slick-prev"></button>',
            nextArrow: '<button type="button" class="slick-next"></button>',
            responsive: [
                { breakpoint: 1024, settings: { slidesToShow: 3, slidesToScroll: 3 } },
                { breakpoint: 768, settings: { slidesToShow: 2, slidesToScroll: 2 } },
                { breakpoint: 480, settings: { slidesToShow: 1, slidesToScroll: 1 } }
            ]
        });
        let slideIndex = 1; showSlides(slideIndex);
        window.plusSlides = function(n) { showSlides(slideIndex += n); }
        window.currentSlide = function(n) { showSlides(slideIndex = n); }
        function showSlides(n) {
            let i; let slides = document.getElementsByClassName("mySlides"); let dots = document.getElementsByClassName("dot");
            if (n > slides.length) slideIndex = 1; if (n < 1) slideIndex = slides.length;
            for (i = 0; i < slides.length; i++) slides[i].style.display = "none";
            for (i = 0; i < dots.length; i++) dots[i].className = dots[i].className.replace(" active", "");
            slides[slideIndex - 1].style.display = "block"; dots[slideIndex - 1].className += " active";
        }

        // === 2. LOGIC CHATBOT (QUAN TRỌNG) ===
        const CHAT_PROCESS_URL = 'ajax_chat_process.php';   
        const GET_MESSAGES_URL = 'ajax.get_messages.php';   
        const POLL_INTERVAL = 3000;                         

        var chatPollInterval = null;
        var isChatOpen = false;

        const $chatWindow = $('#chat-window');
        const $chatBody = $('#chat-body');
        const $chatInput = $('#chat-input');
        const $typingIndicator = $('#typing-indicator');

        // Mở chat (Click vào bubble)
        $('#chat-bubble').click(function() {
            $(this).fadeOut(200);
            $chatWindow.css('display', 'flex').hide().fadeIn(300);
            isChatOpen = true;
            loadChatMessages(); 
            scrollToBottom();
        });

        // Đóng chat
        $('#close-chat').click(function() {
            $chatWindow.fadeOut(300);
            $('#chat-bubble').fadeIn(300);
            isChatOpen = false;
            stopPolling(); 
        });

        // Gửi tin
        $('#send-btn').click(function() { sendMessage(); });
        $chatInput.keypress(function(e) { if (e.which == 13) { sendMessage(); } });

        function sendMessage() {
            var msg = $chatInput.val().trim();
            if (msg === '') return;

            appendMessage(msg, 'user');
            $chatInput.val('');
            $typingIndicator.show();
            scrollToBottom();

            $.ajax({
                url: CHAT_PROCESS_URL,
                method: 'POST',
                dataType: 'json',
                data: { message: msg },
                success: function(response) {
                    $typingIndicator.hide();
                    if (response.reply) { appendMessage(response.reply, 'bot'); }
                    if (response.status === 'waiting' || response.conversation_status === 'human_requested') { startPolling(); }
                    scrollToBottom();
                },
                error: function(xhr, status, error) {
                    $typingIndicator.hide();
                    console.error("Lỗi:", error);
                    // appendMessage("Lỗi kết nối server.", 'bot');
                }
            });
        }

        function loadChatMessages() {
            $.ajax({
                url: GET_MESSAGES_URL,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    // Chuẩn hóa HTML class để khớp với CSS
                    var fixedHtml = data.html
                        .replace(/msg-row/g, '') 
                        .replace(/msg-bubble/g, '')
                        .replace(/msg-user/g, 'chat-message user')
                        .replace(/msg-bot/g, 'chat-message bot');

                    $chatBody.html(fixedHtml);
                    scrollToBottom();

                    if (data.status === 'in_progress' || data.status === 'human_requested') {
                        startPolling();
                    }
                }
            });
        }

        function startPolling() {
            if (chatPollInterval) return;
            chatPollInterval = setInterval(function() {
                if (!isChatOpen) return;
                $.ajax({
                    url: GET_MESSAGES_URL,
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        var fixedHtml = data.html
                            .replace(/msg-row/g, '')
                            .replace(/msg-bubble/g, '')
                            .replace(/msg-user/g, 'chat-message user')
                            .replace(/msg-bot/g, 'chat-message bot');

                        var currentHtml = $chatBody.html();
                        if (fixedHtml.length !== currentHtml.length) {
                            $chatBody.html(fixedHtml);
                            scrollToBottom();
                        }
                        if (data.status === 'closed' || data.status === 'bot') { stopPolling(); }
                    }
                });
            }, POLL_INTERVAL);
        }

        function stopPolling() {
            if (chatPollInterval) { clearInterval(chatPollInterval); chatPollInterval = null; }
        }

        function appendMessage(text, sender) {
            var html = `<div class="chat-message ${sender}">${escapeHtml(text)}</div>`;
            $chatBody.append(html);
        }

        function scrollToBottom() { $chatBody.scrollTop($chatBody[0].scrollHeight); }

        function escapeHtml(text) {
            if (!text) return "";
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
    });
    </script>
</body>
</html>
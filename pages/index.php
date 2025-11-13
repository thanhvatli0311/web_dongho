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

// Calculate the number of unique products in the cart from the session
$cart_count = isset($_SESSION['cart']) && !empty($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// Get filter data from URL
$search = trim($_GET['search'] ?? "");
$brand = trim($_GET['brand'] ?? "");
$price_range = trim($_GET['price_range'] ?? "");
$sort = trim($_GET['sort'] ?? ""); 

// Get seed from URL or create new if not present (Dùng cho ORDER BY RAND)
$seed = (int)($_GET['seed'] ?? mt_rand());
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cửa Hàng Đồng Hồ Cao Cấp</title>
    <link rel="stylesheet" href="../LIB/fontawesome-free-6.4.2-web/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.css" />
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.css" />
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<style>
    /* --- CSS cho Chatbot Widget --- */

    /* Container chung cho icon và chatbox */
    .chatbot-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
    }

    /* Icon Chatbot */
    .chatbot-icon {
        width: 60px;
        height: 60px;
        background-color: #007bff;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        transition: transform 0.3s ease;
    }

    .chatbot-icon:hover {
        transform: scale(1.05);
    }

    .chatbot-icon img {
        width: 40px;
        height: 40px;
        filter: invert(1);
    }

    /* Khung Chatbox */
    .chatbox-widget {
        display: none;
        position: absolute;
        bottom: 80px;
        right: 0;
        width: 350px;
        height: 450px;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    /* Header của khung chat */
    .chatbox-header {
        background-color: #007bff;
        color: white;
        padding: 15px;
        font-size: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
    }

    .chatbox-header h3 {
        margin: 0;
        font-size: 18px;
    }

    .close-button {
        cursor: pointer;
        font-size: 24px;
        font-weight: bold;
        line-height: 1;
    }

    .close-button:hover {
        color: #ccc;
    }

    /* Khu vực hiển thị tin nhắn */
    .chatbox-messages {
        flex-grow: 1;
        padding: 15px;
        overflow-y: auto;
        background-color: #f9f9f9;
        word-wrap: break-word;
    }

    .chatbox-messages p {
        margin-bottom: 8px;
        line-height: 1.4;
        font-size: 14px;
    }

    .chatbox-messages p strong {
        color: #007bff;
    }
    .chatbox-messages p strong:first-child {
        color: #28a745;
    }


    /* Khu vực nhập liệu */
    .chatbox-input {
        display: flex;
        padding: 10px;
        border-top: 1px solid #eee;
        background-color: #fff;
    }

    .chatbox-input input[type="text"] {
        flex-grow: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-right: 10px;
        font-size: 14px;
    }

    .chatbox-input button {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.2s ease;
        font-size: 14px;
    }

    .chatbox-input button:hover {
        background-color: #0056b3;
    }

</style>

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
            // PDO: Sử dụng query() cho truy vấn đơn giản không có tham số
            $sql_highest = "SELECT * FROM tbmathang ORDER BY dongia DESC LIMIT 7";
            $result_highest = $pdo->query($sql_highest);
            // PDO: Sử dụng fetch(PDO::FETCH_ASSOC) để lặp qua kết quả
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
                    // PDO: Sử dụng query() cho truy vấn đơn giản không có tham số
                    $brands_sql = "SELECT DISTINCT thuonghieu FROM tbmathang WHERE thuonghieu IS NOT NULL";
                    $brands_result = $pdo->query($brands_sql);
                    // PDO: Sử dụng fetch(PDO::FETCH_ASSOC)
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

            // Xây dựng câu truy vấn WHERE và mảng tham số cho PDO
            $where = "WHERE tenhang LIKE :search";
            $params = [':search' => "%$search%"];

            if (!empty($brand)) {
                $where .= " AND thuonghieu = :brand";
                $params[':brand'] = $brand;
            }

            if (!empty($price_range)) {
                switch ($price_range) {
                    case '0-50000000':
                        $where .= " AND dongia <= 50000000";
                        break;
                    case '50000000-200000000':
                        $where .= " AND dongia BETWEEN 50000000 AND 200000000";
                        break;
                    case '200000000-500000000':
                        $where .= " AND dongia BETWEEN 200000000 AND 500000000";
                        break;
                    case '500000000-1000000000':
                        $where .= " AND dongia BETWEEN 500000000 AND 1000000000";
                        break;
                    case '1000000000+':
                        $where .= " AND dongia > 1000000000";
                        break;
                }
            }

            // Xây dựng mệnh đề ORDER BY
            $order_by = "";
            if ($sort === "price_asc") {
                $order_by = "ORDER BY dongia ASC";
            } elseif ($sort === "price_desc") {
                $order_by = "ORDER BY dongia DESC";
            } else {
                // Sắp xếp ngẫu nhiên theo seed để giữ thứ tự sản phẩm ổn định khi chuyển trang
                $order_by = "ORDER BY RAND($seed)";
            }
            
            // Truy vấn chính
            $sql = "SELECT * FROM tbmathang $where $order_by LIMIT :limit OFFSET :offset";

            try {
                $stmt = $pdo->prepare($sql);
                
                // Bind tất cả các tham số lọc/tìm kiếm
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value); 
                }
                
                // Bind riêng LIMIT và OFFSET với kiểu INT
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC); // Lấy tất cả kết quả
            } catch (PDOException $e) {
                // Xử lý lỗi PDO
                die("Lỗi truy vấn dữ liệu (sản phẩm): " . $e->getMessage());
            }

            // Lặp qua kết quả
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
        // Truy vấn đếm tổng số mục (Sử dụng lại $where và $params đã tạo ở trên)
        $sql_total = "SELECT COUNT(*) AS count FROM tbmathang $where";
        
        try {
            $stmt_total = $pdo->prepare($sql_total);
            // Sử dụng execute($params) vì $params chỉ chứa các tham số String/Search
            $stmt_total->execute($params); 
            $total_items = $stmt_total->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (PDOException $e) {
            die("Lỗi truy vấn tổng số (phân trang): " . $e->getMessage());
        }

        $total_pages = ceil($total_items / $limit) ?: 1;

        // Xây dựng chuỗi tham số cho phân trang
        $pagination_query_params = '&seed=' . $seed . '&search=' . urlencode($search) . '&brand=' . urlencode($brand) . '&price_range=' . urlencode($price_range) . '&sort=' . urlencode($sort);

        if ($page > 1) {
            echo '<a href="index.php?page=' . ($page - 1) . $pagination_query_params . '">« Trước</a>';
        }
        for ($i = 1; $i <= $total_pages; $i++) {
            $active = ($i == $page) ? 'active' : '';
            echo '<a class="' . $active . '" href="index.php?page=' . $i . $pagination_query_params . '">' . $i . '</a>';
        }
        if ($page < $total_pages) {
            echo '<a href="index.php?page=' . ($page + 1) . $pagination_query_params . '">Sau »</a>';
        }
        ?>
    </div>

    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i> Sản phẩm đã được thêm vào giỏ hàng!
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle add to cart button
            $(".add-to-cart").click(function() {
                if ($(this).is(':disabled')) return;
                var btn = $(this);
                var item_id = btn.data("id");
                $.ajax({
                    url: "cart.php",
                    type: "GET",
                    data: {
                        add_to_cart: item_id
                    },
                    success: function(response) {
                        $("#toast").fadeIn(400).delay(1500).fadeOut(400);
                        // Cập nhật số lượng giỏ hàng trên badge
                        var cartLink = $(".header-nav .cart-box a[href='cart.php']");
                        var badge = cartLink.find(".cart-count");
                        
                        // Kiểm tra xem header.php có tồn tại và đã được nạp hay không
                        if (cartLink.length === 0) {
                             console.error("Lỗi: Không tìm thấy liên kết giỏ hàng trong header.");
                             return;
                        }

                        // Nếu badge chưa tồn tại, thêm mới với số lượng 1
                        if (badge.length === 0) {
                            cartLink.append('<span class="cart-count">1</span>');
                        } else {
                            // Tăng số lượng hiện tại
                            var currentCount = parseInt(badge.text()) || 0;
                            badge.text(currentCount + 1);
                        }
                    },
                    error: function() {
                        alert("Có lỗi xảy ra, vui lòng thử lại!");
                    }
                });
            });

            // Initialize Slick Slider for product carousel
            $('.carousel-container').slick({
                slidesToShow: 4,
                slidesToScroll: 4,
                infinite: true,
                autoplay: true,
                autoplaySpeed: 3000,
                arrows: true,
                prevArrow: '<button type="button" class="slick-prev"></button>',
                nextArrow: '<button type="button" class="slick-next"></button>',
                responsive: [{
                        breakpoint: 1024,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 3
                        }
                    },
                    {
                        breakpoint: 768,
                        settings: {
                            slidesToShow: 2,
                            slidesToScroll: 2
                        }
                    },
                    {
                        breakpoint: 480,
                        settings: {
                            slidesToShow: 1,
                            slidesToScroll: 1
                        }
                    }
                ]
            });

            // Slideshow code
            let slideIndex = 1;
            showSlides(slideIndex);
            window.plusSlides = function(n) {
                showSlides(slideIndex += n);
            }
            window.currentSlide = function(n) {
                showSlides(slideIndex = n);
            }

            function showSlides(n) {
                let i;
                let slides = document.getElementsByClassName("mySlides");
                let dots = document.getElementsByClassName("dot");
                if (n > slides.length) {
                    slideIndex = 1;
                }
                if (n < 1) {
                    slideIndex = slides.length;
                }
                for (i = 0; i < slides.length; i++) {
                    slides[i].style.display = "none";
                }
                for (i = 0; i < dots.length; i++) {
                    dots[i].className = dots[i].className.replace(" active", "");
                }
                slides[slideIndex - 1].style.display = "block";
                dots[slideIndex - 1].className += " active";
            }
        });
    </script>
    <?php include '../templates/footer.php'; ?>
    <div id="chat-bubble" class="chat-bubble">
        <img src="../assets/images/chatbot.png" alt="Chat" width="60"> </div>

    <div id="chat-window" class="chat-window">
        <div class="chat-header">
            <p>Trợ lý Đồng hồ</p>
            <button id="close-chat" class="close-chat-btn">&times;</button>
        </div>
        <div id="chat-body" class="chat-body">
            <div class="chat-message bot">
                <p>Xin chào! Tôi có thể giúp gì cho bạn? Bạn có thể hỏi về:</p>
                <ul>
                    <li>Mẫu mới nhất</li>
                    <li>Chính sách bảo hành</li>
                    <li>Theo dõi đơn hàng</li>
                </ul>
            </div>
        </div>
        <div class="chat-footer">
            <input type="text" id="chat-input" placeholder="Nhập tin nhắn...">
            <button id="send-btn">Gửi</button>
        </div>
    </div>

    <link rel="stylesheet" href="../chatbot/chatbot.css">
    <script src="../chatbot/chatbot.js"></script>
</body>

</html>
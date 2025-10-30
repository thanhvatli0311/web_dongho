<?php 
session_start();
// --- KHÔNG CÓ FILE FUNCTIONS.PHP ---
// Loại bỏ dòng: include '../includes/functions.php'; 

// Yêu cầu các file cần thiết (giả định tồn tại)
include '../includes/db.php';
include '../templates/header.php'; // Chứa thẻ HTML mở đầu, navigation, v.v.

// Biến cho CSS/JS
$mahang = '';
$product = null;
$images = [];
$reviews = [];
$can_review = false;
$user_id = $_SESSION['username'] ?? null;

// Tính số lượng sản phẩm duy nhất trong giỏ hàng từ session (cho mục đích hiển thị trong header/navigation)
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']);
}

// Kiểm tra mã hàng trong URL
if (isset($_GET['mahang'])) {
    $mahang = trim($_GET['mahang']);

    // 1. Lấy thông tin sản phẩm từ tbmathang
    $sql_product = "SELECT * FROM tbmathang WHERE mahang = ?";
    $stmt_product = $conn->prepare($sql_product);
    
    if ($stmt_product) {
        $stmt_product->bind_param("s", $mahang);
        $stmt_product->execute();
        $result_product = $stmt_product->get_result();
        $product = $result_product->fetch_assoc();
        $stmt_product->close();
    } else {
        // Xử lý lỗi prepare statement
        error_log("Prepare failed: " . $conn->error);
    }
    
    // Kiểm tra xem sản phẩm có tồn tại không
    if (!$product) {
        echo "<p style='text-align: center; color: red; padding-top: 50px;'>Không tìm thấy sản phẩm với mã hàng: " . htmlspecialchars($mahang) . "</p>";
        include '../templates/footer.php';
        exit;
    }

    // 2. Lấy ảnh chi tiết từ tbhinhanhchitiet
    $sql_images = "SELECT hinhanh_chitiet FROM tbhinhanhchitiet WHERE mahang = ?";
    $stmt_images = $conn->prepare($sql_images);

    if ($stmt_images) {
        $stmt_images->bind_param("s", $mahang);
        $stmt_images->execute();
        $db_images = $stmt_images->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_images->close();

        // Tạo mảng danh sách ảnh từ DB
        foreach ($db_images as $row) {
            $images[] = $row['hinhanh_chitiet'];
        }
    }

    // 3. Xử lý sắp xếp ảnh từ file JSON (logic đã có, giữ nguyên)
    $orderFile = "../assets/image_orders/order_{$mahang}.json";
    if (file_exists($orderFile)) {
        $jsonOrder = json_decode(file_get_contents($orderFile), true);
        if (is_array($jsonOrder)) {
            $orderedImages = [];
            foreach ($jsonOrder as $fname) {
                if (in_array($fname, $images)) {
                    $orderedImages[] = $fname;
                }
            }
            // Thêm những ảnh chưa có trong file JSON vào cuối danh sách
            foreach ($images as $img) {
                if (!in_array($img, $orderedImages)) {
                    $orderedImages[] = $img;
                }
            }
            $images = $orderedImages;
        }
    }

    // 4. Lấy các đánh giá ĐÃ ĐƯỢC DUYỆT cho sản phẩm này
    $sql_reviews = "SELECT r.*, u.username FROM reviews r JOIN tbuser u ON r.user_id = u.username WHERE r.mathang_id = ? AND r.status = 'approved' ORDER BY r.created_at DESC";
    $stmt_reviews = $conn->prepare($sql_reviews);
    
    if ($stmt_reviews) {
        $stmt_reviews->bind_param("s", $mahang);
        $stmt_reviews->execute();
        $reviews = $stmt_reviews->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_reviews->close();
    }

    // 5. Kiểm tra quyền được đánh giá
    if ($user_id) {
        // Giả sử makhach trong tbdonhang là username
        $sql_check_purchase = "SELECT COUNT(*) FROM tbdonhang dh JOIN tbchitietdonhang ctdh ON dh.madonhang = ctdh.madonhang WHERE dh.makhach = ? AND ctdh.mahang = ? AND dh.tinhtrang = 'Đã giao'";
        $stmt_check_purchase = $conn->prepare($sql_check_purchase);
        
        if ($stmt_check_purchase) {
            $stmt_check_purchase->bind_param("ss", $user_id, $mahang);
            $stmt_check_purchase->execute();
            $result_check_purchase = $stmt_check_purchase->get_result()->fetch_row()[0];
            $stmt_check_purchase->close();

            if ($result_check_purchase > 0) {
                // Kiểm tra xem người dùng đã đánh giá sản phẩm này chưa (chỉ cho phép 1 đánh giá duy nhất/sản phẩm)
                $sql_check_review_exist = "SELECT COUNT(*) FROM reviews WHERE user_id = ? AND mathang_id = ? AND (status = 'approved' OR status = 'pending')";
                $stmt_check_review_exist = $conn->prepare($sql_check_review_exist);
                
                if ($stmt_check_review_exist) {
                    $stmt_check_review_exist->bind_param("ss", $user_id, $mahang);
                    $stmt_check_review_exist->execute();
                    $review_exist_count = $stmt_check_review_exist->get_result()->fetch_row()[0];
                    $stmt_check_review_exist->close();

                    if ($review_exist_count == 0) {
                        $can_review = true;
                    }
                }
            }
        }
    }
    
} else {
    echo "<p style='text-align: center; color: red; padding-top: 50px;'>Vui lòng cung cấp mã hàng!</p>";
    include '../templates/footer.php';
    exit;
}

// Hàm format sao cho hiển thị
function render_stars($rating) {
    $output = '';
    for ($i = 0; $i < $rating; $i++) {
        $output .= '&#9733;'; // Sao vàng
    }
    for ($i = 0; $i < (5 - $rating); $i++) {
        $output .= '&#9734;'; // Sao rỗng
    }
    return $output;
}
?>

<div class="product-detail-container">
    <div class="product-header">
        <h1 class="product-title"><?php echo htmlspecialchars($product['tenhang']); ?></h1>
    </div>
    
    <div class="product-content">
        <!-- Cột bên trái: Ảnh sản phẩm -->
        <div class="product-images">
            <?php if (!empty($images)) { ?>
                <div class="slideshow-container">
                    <?php foreach ($images as $index => $image) { ?>
                        <div class="mySlides fade">
                            <div class="numbertext"><?php echo $index + 1; ?> / <?php echo count($images); ?></div>
                            <img src="../assets/images/<?php echo htmlspecialchars($image); ?>" alt="Ảnh chi tiết">
                        </div>
                    <?php } ?>
                    <a class="prev" onclick="plusSlides(-1)">❮</a>
                    <a class="next" onclick="plusSlides(1)">❯</a>
                </div>
                <div class="thumbnail-container">
                    <?php foreach ($images as $index => $image) { ?>
                        <img class="thumbnail" src="../assets/images/<?php echo htmlspecialchars($image); ?>" onclick="currentSlide(<?php echo $index + 1; ?>)">
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="no-images">Không có ảnh chi tiết nào.</div>
            <?php } ?>
        </div>

        <!-- Cột bên phải: Thông tin sản phẩm, mô tả, tình trạng & nút mua -->
        <div class="product-details">
            <p class="price"><?php echo number_format($product['dongia'], 0, ',', '.'); ?> VND</p>
            
            <!-- Mô tả sản phẩm -->
            <div class="product-description">
                <div class="description-content">
                    <h3>Mô tả sản phẩm</h3>
                    <?php echo nl2br(htmlspecialchars($product['mota'])); ?>
                </div>
            </div>
            
            <!-- Container cho tình trạng và nút Thêm vào giỏ hàng trên cùng 1 dòng -->
            <div class="status-add-container">
                <div class="product-info">
                    <ul class="info-list">
                        <li><strong>Thương hiệu:</strong> <?php echo htmlspecialchars($product['thuonghieu']); ?></li>
                        <li><strong>Nguồn gốc:</strong> <?php echo htmlspecialchars($product['nguongoc']); ?></li>
                        <li><strong>Tình trạng:</strong> 
                            <span class="stock-status <?php echo (strtolower(trim($product['conhang'])) === 'hết hàng') ? 'out-of-stock' : 'in-stock'; ?>">
                                <?php echo htmlspecialchars($product['conhang']); ?>
                            </span>
                        </li>
                    </ul>
                </div>
            
                <div class="action-buttons">
                    <button class="add-to-cart-btn" data-id="<?php echo htmlspecialchars($product['mahang']); ?>" <?php echo (strtolower(trim($product['conhang'])) === 'hết hàng') ? 'disabled' : ''; ?>>
                        Thêm vào giỏ hàng
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- --- Phần đánh giá sản phẩm --- -->
    <div class="product-reviews-section">
        <h2>Đánh giá sản phẩm</h2>

        <?php if ($can_review) { ?>
            <!-- Form gửi đánh giá -->
            <div class="review-form-container">
                <h3>Gửi đánh giá của bạn</h3>
                <form id="review-form" action="submit_review.php" method="POST">
                    <input type="hidden" name="mathang_id" value="<?php echo htmlspecialchars($mahang); ?>">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                    
                    <div class="rating-input">
                        <label for="rating">Đánh giá sao:</label>
                        <div class="stars" data-rating="0">
                            <span class="star" data-value="1">&#9733;</span>
                            <span class="star" data-value="2">&#9733;</span>
                            <span class="star" data-value="3">&#9733;</span>
                            <span class="star" data-value="4">&#9733;</span>
                            <span class="star" data-value="5">&#9733;</span>
                        </div>
                        <input type="hidden" name="rating" id="rating" value="0" required>
                    </div>

                    <div class="form-group">
                        <label for="comment">Bình luận của bạn:</label>
                        <textarea name="comment" id="comment" rows="5" placeholder="Viết bình luận của bạn về sản phẩm..." required></textarea>
                    </div>

                    <button type="submit" class="submit-review-btn">Gửi đánh giá</button>
                </form>
                <div id="review-message" style="margin-top: 10px;"></div>
            </div>
        <?php } elseif ($user_id && !$can_review) { 
            // Logic kiểm tra xem đã gửi hay chưa (status pending/approved)
            $sql_check_review_status = "SELECT status FROM reviews WHERE user_id = ? AND mathang_id = ?";
            $stmt_check_status = $conn->prepare($sql_check_review_status);
            $stmt_check_status->bind_param("ss", $user_id, $mahang);
            $stmt_check_status->execute();
            $review_status = $stmt_check_status->get_result()->fetch_assoc();
            $stmt_check_status->close();
            
            if ($review_status && $review_status['status'] === 'pending') { ?>
                 <p class="review-status warning-status">Bạn đã gửi đánh giá. Đánh giá của bạn đang chờ quản trị viên duyệt.</p>
            <?php } elseif ($review_status && $review_status['status'] === 'approved') { ?>
                 <p class="review-status success-status">Bạn đã đánh giá sản phẩm này. Cảm ơn!</p>
            <?php } else { ?>
                 <p class="review-status info-status">Bạn cần mua sản phẩm này (với trạng thái 'Đã giao') để có thể gửi đánh giá.</p>
            <?php }
        } elseif (!$user_id) { ?>
            <p class="review-status info-status">Vui lòng <a href="login.php">đăng nhập</a> để gửi đánh giá về sản phẩm này.</p>
        <?php } ?>

        <div class="existing-reviews">
            <h3>Các đánh giá đã được duyệt (<?php echo count($reviews); ?>)</h3>
            <?php if (!empty($reviews)) { ?>
                <?php foreach ($reviews as $review) { ?>
                    <div class="review-item">
                        <div class="review-meta">
                            <strong><?php echo htmlspecialchars($review['username']); ?></strong>
                            <span class="review-rating">
                                <?php echo render_stars($review['rating']); ?>
                            </span>
                            <span class="review-date"><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></span>
                        </div>
                        <p class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <p>Chưa có đánh giá nào cho sản phẩm này.</p>
            <?php } ?>
        </div>
    </div>
    
</div>


<!-- Toast Notification -->
<div class="toast" id="toast">
    <i class="fas fa-check-circle"></i> Sản phẩm đã được thêm vào giỏ hàng!
</div>

<!-- JavaScript xử lý slideshow và Thêm vào giỏ hàng -->
<script>
let slideIndex = 1;
// Chỉ gọi showSlides nếu có ảnh
if (document.getElementsByClassName("mySlides").length > 0) {
    showSlides(slideIndex);
}

function plusSlides(n) {
    showSlides(slideIndex += n);
}

function currentSlide(n) {
    showSlides(slideIndex = n);
}

function showSlides(n) {
    let i;
    let slides = document.getElementsByClassName("mySlides");
    let thumbnails = document.getElementsByClassName("thumbnail");
    if (slides.length === 0) return;
    if (n > slides.length) { slideIndex = 1; }
    if (n < 1) { slideIndex = slides.length; }
    for (i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
    }
    for (i = 0; i < thumbnails.length; i++) {
        thumbnails[i].className = thumbnails[i].className.replace(" active", "");
    }
    slides[slideIndex - 1].style.display = "block";
    thumbnails[slideIndex - 1].className += " active";
}

// Xử lý nút "Thêm vào giỏ hàng" qua AJAX
document.querySelectorAll('.add-to-cart-btn').forEach(button => {
    button.addEventListener('click', function() {
        if (this.disabled) return;
        const productId = this.getAttribute('data-id');
        
        // Giả định cart.php?add_to_cart=... sẽ xử lý logic giỏ hàng
        fetch(`cart.php?add_to_cart=${encodeURIComponent(productId)}`)
            .then(response => {
                // Giả định cart.php trả về 1 (hoặc text/json thành công)
                if (response.ok) {
                    return response.text();
                } else {
                    throw new Error('Lỗi thêm sản phẩm vào giỏ hàng');
                }
            })
            .then(data => {
                // Hiển thị toast notification
                const toast = document.getElementById('toast');
                // Thay vì opacity = '1', chỉ cần thay đổi display = 'block'
                // Tuy nhiên, để animation hoạt động, ta nên dùng opacity và bỏ display: none trong CSS
                toast.style.opacity = '1';
                setTimeout(() => {
                    toast.style.opacity = '0';
                }, 1500);

                // Cập nhật số đếm giỏ hàng trên header (cần đảm bảo header.php có phần tử này)
                const badge = document.querySelector('.cart-box .cart-count');
                if (badge) {
                    // Để đơn giản, chỉ tăng số lượng
                    badge.textContent = parseInt(badge.textContent || 0) + 1;
                }
            })
            .catch(error => {
                console.error(error);
                // Sử dụng custom modal hoặc thông báo thay vì alert()
                const reviewMessage = document.getElementById('review-message') || document.createElement('div');
                reviewMessage.style.color = 'red';
                reviewMessage.textContent = "Có lỗi xảy ra, vui lòng thử lại!";
                document.querySelector('.action-buttons').appendChild(reviewMessage);
            });
    });
});

// --- JavaScript cho form đánh giá sao và AJAX gửi đánh giá ---
document.addEventListener('DOMContentLoaded', function() {
    const starsContainer = document.querySelector('.stars');
    const ratingInput = document.getElementById('rating');
    const stars = document.querySelectorAll('.stars .star');

    if (starsContainer) {
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const value = parseInt(this.dataset.value);
                ratingInput.value = value;
                highlightStars(value);
            });
            star.addEventListener('mouseover', function() {
                highlightStars(parseInt(this.dataset.value), true);
            });
            star.addEventListener('mouseout', function() {
                highlightStars(parseInt(ratingInput.value));
            });
        });
    }

    function highlightStars(value, hover = false) {
        stars.forEach(star => {
            if (parseInt(star.dataset.value) <= value) {
                star.classList.add('selected');
            } else {
                star.classList.remove('selected');
            }
        });
    }

    // Khởi tạo trạng thái sao khi tải trang
    highlightStars(parseInt(ratingInput ? ratingInput.value : 0));

    // Xử lý form gửi đánh giá bằng AJAX
    const reviewForm = document.getElementById('review-form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault(); 

            const formData = new FormData(this);
            const reviewMessage = document.getElementById('review-message');
            reviewMessage.textContent = 'Đang gửi...';
            reviewMessage.style.color = '#333';

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    reviewMessage.style.color = 'green';
                    reviewMessage.textContent = data.message;
                    // Ẩn form và cập nhật trạng thái
                    reviewForm.style.display = 'none';
                    const reviewSection = document.querySelector('.product-reviews-section');
                    if (reviewSection) {
                        reviewSection.insertAdjacentHTML('afterbegin', `<p class="review-status warning-status">Bạn đã gửi đánh giá. Đánh giá của bạn đang chờ quản trị viên duyệt.</p>`);
                    }
                } else {
                    reviewMessage.style.color = 'red';
                    reviewMessage.textContent = data.message || 'Có lỗi xảy ra khi gửi đánh giá.';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                reviewMessage.style.color = 'red';
                reviewMessage.textContent = 'Lỗi kết nối khi gửi đánh giá.';
            });
        });
    }
});
</script>

<style>
/* CSS cho trang chi tiết sản phẩm */
.product-detail-container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 20px;
    background: #fff;
    font-family: 'Inter', Arial, sans-serif;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    border-radius: 12px;
}

/* Các phần CSS giữ nguyên từ code gốc */
.product-header {
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 15px;
    margin-bottom: 25px;
    text-align: center;
}

.product-title {
    font-size: 36px;
    color: #2c3e50;
    margin: 0;
    font-weight: 700;
}

.product-content {
    display: flex;
    gap: 40px;
    flex-wrap: wrap;
    justify-content: center;
}

.product-images {
    flex: 1;
    min-width: 450px;
    max-width: 550px;
}

.slideshow-container {
    position: relative;
    width: 100%;
    padding-top: 100%; 
    margin: auto;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.mySlides {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: none;
    align-items: center;
    justify-content: center;
}

.mySlides img {
    width: 100%;
    height: 100%;
    object-fit: contain; 
}

.prev, .next {
    cursor: pointer;
    position: absolute;
    top: 50%;
    padding: 15px;
    color: #fff;
    font-size: 24px;
    background: rgba(44, 62, 80, 0.6);
    border-radius: 50%;
    transform: translateY(-50%);
    transition: background 0.3s;
    user-select: none;
    z-index: 10;
    opacity: 0.8;
}
.prev:hover, .next:hover { background: rgba(44, 62, 80, 0.9); opacity: 1; }
.prev { left: 15px; }
.next { right: 15px; }

.thumbnail-container {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.thumbnail {
    width: 80px; 
    height: 80px;
    object-fit: cover;
    border: 3px solid transparent;
    border-radius: 8px;
    cursor: pointer;
    transition: border-color 0.3s, transform 0.2s;
    opacity: 0.7;
}

.thumbnail.active, .thumbnail:hover {
    border-color: #3498db;
    transform: scale(1.05);
    opacity: 1;
}

.product-details {
    flex: 1;
    min-width: 400px;
    max-width: 550px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    background: #fdfdfd;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

.price {
    font-size: 40px;
    color: #e74c3c;
    font-weight: 800;
    margin: 0;
    text-align: right;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 20px;
}

.product-description {
    background: #fff;
    padding: 20px;
    border: 1px solid #eee;
    border-radius: 8px;
    box-shadow: inset 0 1px 5px rgba(0,0,0,0.03);
}
.description-content h3 {
    color: #3498db;
    font-size: 20px;
    border-bottom: 1px solid #ddd;
    padding-bottom: 8px;
    margin-bottom: 15px;
}

.product-info {
    background: #fff;
    padding: 20px;
    border: 1px solid #eee;
    border-radius: 8px;
    flex-grow: 1;
}

.stock-status {
    font-weight: bold;
    padding: 3px 8px;
    border-radius: 4px;
    display: inline-block;
}
.in-stock {
    color: #27ae60;
    background-color: #e6f6ed;
}
.out-of-stock {
    color: #e74c3c;
    background-color: #fcebeb;
}

.add-to-cart-btn {
    width: 100%;
    padding: 18px 20px;
    background: linear-gradient(145deg, #f1c40f, #e67e22);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 5px 15px rgba(241, 196, 15, 0.4);
    text-transform: uppercase;
}
.add-to-cart-btn:hover {
    background: linear-gradient(145deg, #e67e22, #f1c40f);
    box-shadow: 0 8px 20px rgba(230, 126, 34, 0.5);
    transform: translateY(-3px);
}
.add-to-cart-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}

/* --- CSS cho phần đánh giá --- */
.product-reviews-section {
    margin-top: 50px;
    padding-top: 30px;
    border-top: 2px solid #f0f0f0;
}
.review-form-container, .existing-reviews {
    background: #fff;
    padding: 25px;
    border: 1px solid #eee;
    border-radius: 10px;
    margin-bottom: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.review-form-container h3, .existing-reviews h3 {
    font-size: 24px;
    color: #3498db;
    border-bottom: 2px dashed #f0f0f0;
    padding-bottom: 10px;
    margin-bottom: 20px;
}
.rating-input {
    gap: 15px;
}
.stars {
    font-size: 32px;
}
.stars .star {
    text-shadow: 0 1px 1px rgba(0,0,0,0.1);
}
.stars .star.selected,
.stars .star:hover {
    color: #f39c12; 
}
.stars .star:hover ~ .star {
    color: #ccc; /* Đảm bảo sao không được hover theo sau trở về màu xám */
}
.submit-review-btn {
    background: #3498db;
}
.submit-review-btn:hover {
    background: #2980b9;
    box-shadow: 0 4px 10px rgba(52, 152, 219, 0.4);
}
.review-status {
    font-size: 17px;
    font-weight: 500;
    border-left: 5px solid;
    padding: 15px;
}
.info-status {
    background: #eaf5ff;
    color: #2980b9;
    border-left-color: #3498db;
}
.warning-status {
    background: #fff3e0;
    color: #e67e22;
    border-left-color: #f39c12;
}
.success-status {
    background: #e8f8f5;
    color: #27ae60;
    border-left-color: #2ecc71;
}

.review-item {
    border: 1px solid #f0f0f0;
    border-left: 4px solid #3498db;
    margin-bottom: 20px;
    padding: 15px;
    background: #fcfcfc;
}
.review-meta {
    border-bottom: 1px dashed #eee;
}
.review-meta strong {
    font-weight: 700;
    color: #2c3e50;
}
.review-rating {
    font-size: 20px;
    color: #f1c40f; 
}
.review-comment {
    font-style: italic;
    color: #555;
    line-height: 1.7;
}

/* Toast Notification */
.toast {
    visibility: visible;
    min-width: 250px;
    background-color: #2ecc71;
    color: #fff;
    text-align: center;
    border-radius: 8px;
    padding: 16px;
    position: fixed;
    z-index: 9999;
    left: 50%;
    bottom: 30px;
    transform: translateX(-50%);
    font-size: 17px;
    opacity: 0; /* Đã sửa: dùng opacity để ẩn thay vì display: none */
    transition: opacity 0.5s, transform 0.5s;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    /* display: none; đã bỏ */
}

.toast i {
    margin-right: 10px;
}

/* Responsive adjustments */
@media (max-width: 900px) {
    .product-content {
        flex-direction: column;
        align-items: center;
    }
    .product-images, .product-details {
        min-width: unset;
        width: 100%;
        max-width: 100%;
    }
    .slideshow-container {
        padding-top: 75%; /* Tỷ lệ 4:3 */
    }
    .status-add-container {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    .action-buttons {
        margin-left: 0;
        width: 100%;
    }
}
</style>

<?php include '../templates/footer.php'; ?>

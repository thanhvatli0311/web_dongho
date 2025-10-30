<?php
/**
 * File: test_review_logic.php
 * Kiểm thử độc lập (Static Testing) các hàm logic đánh giá.
 * KHÔNG YÊU CẦU KẾT NỐI CSDL THẬT.
 */
include 'review_functions.php';

echo "<h1>Kiểm Thử Độc Lập (Unit Test) Chức Năng Đánh Giá</h1>";
echo "<p>Kết quả dựa trên dữ liệu giả lập trong hàm <code>simulate_can_review_check</code> và <code>simulate_get_approved_reviews</code>.</p>";
echo "<hr>";

// Dữ liệu kiểm thử
$test_cases = [
    // Trường hợp 1: Có quyền đánh giá
    ['user_id' => 'user_A', 'mahang' => 'SP001', 'description' => 'User_A đã mua SP001 và chưa đánh giá'],
    
    // Trường hợp 2: Đã đánh giá
    ['user_id' => 'user_B', 'mahang' => 'SP001', 'description' => 'User_B đã mua SP001 và đã đánh giá'],
    
    // Trường hợp 3: Chưa mua (hoặc đơn hàng chưa 'Đã giao')
    ['user_id' => 'user_C', 'mahang' => 'SP001', 'description' => 'User_C đã mua nhưng đơn hàng chưa Đã giao'],

    // Trường hợp 4: Sản phẩm không tồn tại (chưa mua)
    ['user_id' => 'user_A', 'mahang' => 'SP999', 'description' => 'User_A kiểm tra sản phẩm không tồn tại/chưa mua'],
    
    // Trường hợp 5: Chưa đăng nhập
    ['user_id' => '', 'mahang' => 'SP001', 'description' => 'Chưa đăng nhập'],
];

// --- 1. Kiểm thử Quyền Đánh giá (check_can_review) ---
echo "<h2>1. Kiểm Thử Quyền Đánh Giá (check_can_review)</h2>";

foreach ($test_cases as $i => $case) {
    echo "<h3>CASE " . ($i + 1) . ": " . $case['description'] . "</h3>";
    $result = check_can_review($case['user_id'], $case['mahang']);
    
    $status_class = $result['can_review'] ? 'success' : 'error';
    $expected = ($i === 0) ? 'Có' : 'Không'; // Chỉ Case 1 là được phép

    echo "<div class='test-result {$status_class}'>";
    echo "<strong>Kết quả mong đợi:</strong> {$expected} quyền đánh giá.<br>";
    echo "<strong>Kết quả thực tế:</strong> " . ($result['can_review'] ? 'CÓ' : 'KHÔNG') . " quyền đánh giá.<br>";
    echo "<strong>Lý do:</strong> {$result['reason']}";
    echo "</div>";
}

// --- 2. Kiểm thử Hiển thị Đánh giá (get_approved_reviews) ---
echo "<h2>2. Kiểm Thử Lấy Đánh Giá Đã Duyệt (get_approved_reviews)</h2>";

// Trường hợp 1: Lấy đánh giá cho SP001 (có 2 đánh giá giả lập)
$reviews_sp001 = get_approved_reviews('SP001');
echo "<h3>CASE 6: Lấy đánh giá cho SP001 (Dự kiến: 2 đánh giá)</h3>";
echo "<div class='test-result'>";
echo "<strong>Số lượng đánh giá thực tế:</strong> " . count($reviews_sp001) . "<br>";
echo "<strong>Chi tiết:</strong>";
echo "<ul>";
foreach ($reviews_sp001 as $review) {
    echo "<li>User: {$review['username']} | Rating: {$review['rating']} sao | Comment: " . htmlspecialchars($review['comment']) . "</li>";
}
echo "</ul>";
echo "</div>";

// Trường hợp 2: Lấy đánh giá cho SP999 (không có đánh giá)
$reviews_sp999 = get_approved_reviews('SP999');
echo "<h3>CASE 7: Lấy đánh giá cho SP999 (Dự kiến: 0 đánh giá)</h3>";
echo "<div class='test-result " . (empty($reviews_sp999) ? 'success' : 'error') . "'>";
echo "<strong>Số lượng đánh giá thực tế:</strong> " . count($reviews_sp999) . " (Dự kiến: 0)";
echo "</div>";
?>

<style>
body { font-family: 'Inter', Arial, sans-serif; line-height: 1.6; padding: 20px; background-color: #f4f7f9; }
h1, h2, h3 { color: #2c3e50; border-bottom: 2px solid #ecf0f1; padding-bottom: 5px; }
.test-result {
    margin: 15px 0;
    padding: 15px;
    border-radius: 8px;
    font-size: 16px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.test-result.success {
    background-color: #e6f6ed;
    border-left: 5px solid #2ecc71;
    color: #27ae60;
}
.test-result.error {
    background-color: #fcebeb;
    border-left: 5px solid #e74c3c;
    color: #c0392b;
}
ul { margin-top: 10px; padding-left: 20px; }
</style>

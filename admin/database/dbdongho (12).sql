-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th12 05, 2025 lúc 06:53 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `dbdongho`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `user_identifier` varchar(255) NOT NULL,
  `status` enum('bot','human_requested','in_progress','closed') NOT NULL DEFAULT 'bot',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `conversations`
--

INSERT INTO `conversations` (`id`, `user_identifier`, `status`, `created_at`, `updated_at`) VALUES
(1, 'gk2839suaqso2cf36k2l4p5rjg', 'bot', '2025-11-12 12:31:54', '2025-12-04 04:03:08'),
(2, 'vsadm2vvjhplu445lna7chhdei', 'bot', '2025-11-12 12:47:47', '2025-12-04 04:03:08'),
(3, 'o8d9289m42gaqn8dordi1l3b6o', 'bot', '2025-11-14 02:46:50', '2025-11-14 02:46:50'),
(4, 'o8d9289m42gaqn8dordi1l3b6o', 'bot', '2025-11-14 02:48:44', '2025-11-14 02:48:44'),
(5, '07j5nfeluli8a47g0ru86if1ng', 'bot', '2025-11-20 08:36:04', '2025-11-20 08:36:04'),
(6, 'lgl2nh8rhoffiekh0g78mund5v', 'bot', '2025-12-04 02:26:41', '2025-12-04 02:26:41'),
(7, 'po9fdn8j2dkj8qtl8c24ojgado', 'in_progress', '2025-12-04 02:39:59', '2025-12-04 04:16:07'),
(8, 'mu3132uv25j4fdh7k3nqjrq1nm', 'in_progress', '2025-12-04 04:16:46', '2025-12-04 04:23:59'),
(9, 't2c7otto9bl360ku0gr99srk3g', 'bot', '2025-12-04 04:24:33', '2025-12-04 04:24:33'),
(10, '0asulm8hjv7c21ud86hmhu9rff', 'bot', '2025-12-04 04:30:18', '2025-12-04 04:31:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `customer_leads`
--

CREATE TABLE `customer_leads` (
  `id` int(11) NOT NULL,
  `user_identifier` varchar(255) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `message_content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `customer_leads`
--

INSERT INTO `customer_leads` (`id`, `user_identifier`, `phone_number`, `message_content`, `created_at`) VALUES
(1, '0asulm8hjv7c21ud86hmhu9rff', '0944745991', '0944745991', '2025-12-04 04:31:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `intents`
--

CREATE TABLE `intents` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `intents`
--

INSERT INTO `intents` (`id`, `name`) VALUES
(1, '#BAO_HANH'),
(2, '#CHAO_HOI'),
(7, '#DIA_CHI'),
(4, '#GIA_CA'),
(6, '#THANH_TOAN'),
(5, '#THONG_TIN_SP'),
(9, '#TU_VAN'),
(3, '#VAN_CHUYEN'),
(8, '#VO_VAN');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender` enum('user','bot','admin') NOT NULL,
  `message_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender`, `message_text`, `created_at`) VALUES
(1, 1, 'user', 'hi', '2025-11-12 12:31:54'),
(2, 1, 'bot', 'Xin chào! Tôi là DB Chatbot, tôi có thể hỗ trợ bạn tìm hiểu thông tin về các mẫu đồng hồ và dịch vụ tại DBĐồng Hồ.', '2025-11-12 12:31:54'),
(3, 1, 'user', 'a', '2025-11-12 12:32:16'),
(4, 1, 'bot', 'Tôi chưa hiểu rõ ý bạn. Tôi đã chuyển yêu cầu của bạn đến chuyên viên tư vấn. Vui lòng chờ trong giây lát...', '2025-11-12 12:32:16'),
(5, 1, 'user', 'b', '2025-11-12 12:44:06'),
(6, 1, 'bot', 'Tôi chưa hiểu rõ ý bạn. Tôi đã chuyển yêu cầu của bạn đến chuyên viên tư vấn. Vui lòng chờ trong giây lát...', '2025-11-12 12:44:06'),
(7, 2, 'user', 'alo', '2025-11-12 12:47:47'),
(8, 2, 'bot', 'Chào bạn! Bạn cần tư vấn về sản phẩm, giá cả, hay chính sách mua hàng?', '2025-11-12 12:47:47'),
(9, 2, 'user', 'giá cả', '2025-11-12 12:47:56'),
(10, 2, 'bot', 'Vui lòng cho tôi biết tên hoặc mã sản phẩm để tôi có thể kiểm tra giá chính xác nhất.', '2025-11-12 12:47:56'),
(11, 2, 'user', 'mh001', '2025-11-12 12:48:08'),
(12, 2, 'bot', 'Bạn có thể xem chi tiết giá và thông tin sản phẩm trên trang web của chúng tôi, hoặc cho tôi biết mã sản phẩm bạn quan tâm nhé.', '2025-11-12 12:48:08'),
(13, 2, 'user', 'uk', '2025-11-12 12:48:25'),
(14, 2, 'bot', 'Tôi chưa hiểu rõ ý bạn. Tôi đã chuyển yêu cầu của bạn đến chuyên viên tư vấn. Vui lòng chờ trong giây lát...', '2025-11-12 12:48:26'),
(15, 2, 'user', 'f', '2025-11-12 13:00:55'),
(16, 3, 'user', 'alo', '2025-11-14 02:46:50'),
(17, 3, 'bot', 'Xin chào! Tôi là DB Chatbot, tôi có thể hỗ trợ bạn tìm hiểu thông tin về các mẫu đồng hồ và dịch vụ tại DBĐồng Hồ.', '2025-11-14 02:46:50'),
(18, 4, 'user', 'alo', '2025-11-14 02:48:44'),
(19, 4, 'bot', 'Chào bạn, tôi là trợ lý ảo. Tôi có thể giúp gì cho bạn?', '2025-11-14 02:48:45'),
(20, 4, 'user', 'alo', '2025-11-14 02:48:48'),
(21, 4, 'bot', 'Chào bạn! Bạn cần tư vấn về sản phẩm, giá cả, hay chính sách mua hàng?', '2025-11-14 02:48:49'),
(22, 5, 'user', 'hi', '2025-11-20 08:36:04'),
(23, 5, 'bot', 'Chào bạn! Bạn cần tư vấn về sản phẩm, giá cả, hay chính sách mua hàng?', '2025-11-20 08:36:04'),
(24, 7, 'user', 'hh', '2025-12-04 02:51:03'),
(25, 7, 'bot', 'Em chưa hiểu rõ ý của anh/chị lắm. Em đã chuyển cuộc hội thoại cho nhân viên tư vấn. Anh/chị vui lòng đợi trong giây lát nhé!', '2025-12-04 02:51:03'),
(26, 7, 'admin', '👋', '2025-12-04 02:59:12'),
(27, 7, 'user', 'hh', '2025-12-04 03:09:24'),
(28, 7, 'user', 'a', '2025-12-04 03:09:43'),
(29, 7, 'admin', 'd', '2025-12-04 03:11:03'),
(30, 7, 'user', 'lô', '2025-12-04 03:14:38'),
(31, 7, 'user', 't', '2025-12-04 03:19:38'),
(32, 7, 'user', 'hi', '2025-12-04 03:45:48'),
(33, 7, 'admin', 'hi', '2025-12-04 03:46:28'),
(34, 7, 'user', 'hi', '2025-12-04 04:05:58'),
(35, 7, 'bot', 'Em chưa hiểu rõ ý của anh/chị lắm. Em đã chuyển cuộc hội thoại cho nhân viên tư vấn. Anh/chị vui lòng đợi trong giây lát nhé!', '2025-12-04 04:06:00'),
(36, 7, 'user', 'hi', '2025-12-04 04:10:42'),
(37, 7, 'user', 'hi', '2025-12-04 04:11:01'),
(38, 7, 'user', 'hh', '2025-12-04 04:16:07'),
(39, 8, 'user', 'xin chào', '2025-12-04 04:16:46'),
(40, 8, 'bot', 'Chào bạn! Bạn cần tư vấn về sản phẩm, giá cả, hay chính sách mua hàng?', '2025-12-04 04:16:46'),
(41, 8, 'user', 'tôi muốn được biết về đông hồ patek philippe', '2025-12-04 04:17:39'),
(42, 8, 'bot', 'Bạn có thể xem chi tiết giá và thông tin sản phẩm trên trang web của chúng tôi, hoặc cho tôi biết mã sản phẩm bạn quan tâm nhé.', '2025-12-04 04:17:39'),
(43, 8, 'user', 'mã mh001', '2025-12-04 04:18:00'),
(44, 8, 'bot', 'Mức giá cho các mẫu đồng hồ của chúng tôi rất đa dạng. Bạn có thể cho biết ngân sách của mình (ví dụ: dưới 10 triệu) hoặc thương hiệu bạn quan tâm không?', '2025-12-04 04:18:00'),
(45, 8, 'user', 'hh', '2025-12-04 04:18:46'),
(46, 8, 'bot', 'Dạ anh chị có thể cho em biết mình đang cần hỗ trợ gì không ạ?', '2025-12-04 04:18:46'),
(47, 8, 'user', 'u', '2025-12-04 04:18:51'),
(48, 8, 'bot', 'Chào bạn, tôi là trợ lý ảo. Tôi có thể giúp gì cho bạn?', '2025-12-04 04:18:51'),
(49, 8, 'user', 'we', '2025-12-04 04:18:56'),
(50, 8, 'bot', 'Chào bạn! Bạn cần tư vấn về sản phẩm, giá cả, hay chính sách mua hàng?', '2025-12-04 04:18:56'),
(51, 8, 'user', 'ejejwjwej', '2025-12-04 04:19:04'),
(52, 8, 'bot', 'Chào bạn, tôi là trợ lý ảo. Tôi có thể giúp gì cho bạn?', '2025-12-04 04:19:04'),
(53, 8, 'user', 'tôi muốn gặp nhân viên', '2025-12-04 04:19:29'),
(54, 8, 'bot', 'Chào bạn, tôi là trợ lý ảo. Tôi có thể giúp gì cho bạn?', '2025-12-04 04:19:29'),
(55, 8, 'user', 'bao hanh', '2025-12-04 04:21:32'),
(56, 8, 'bot', 'Chúng tôi miễn phí vận chuyển toàn quốc cho đơn hàng từ 1.000.000 VNĐ. Thời gian giao hàng dự kiến từ 2-4 ngày.', '2025-12-04 04:21:32'),
(57, 8, 'user', 'um', '2025-12-04 04:22:27'),
(58, 8, 'bot', 'Em chưa hiểu rõ ý của anh/chị lắm. Em đã chuyển cuộc hội thoại cho nhân viên tư vấn. Anh/chị vui lòng đợi trong giây lát nhé!', '2025-12-04 04:22:27'),
(59, 8, 'user', 'hi', '2025-12-04 04:22:35'),
(60, 8, 'user', 'xin', '2025-12-04 04:23:47'),
(61, 8, 'user', 't', '2025-12-04 04:23:59'),
(62, 9, 'user', 'hi', '2025-12-04 04:24:33'),
(63, 9, 'bot', 'Chào bạn! Bạn cần tư vấn về sản phẩm, giá cả, hay chính sách mua hàng?', '2025-12-04 04:24:33'),
(64, 10, 'user', 'tư vấn', '2025-12-04 04:30:18'),
(65, 10, 'bot', 'Chào bạn! Bạn cần tư vấn về sản phẩm, giá cả, hay chính sách mua hàng?', '2025-12-04 04:30:19'),
(66, 10, 'user', 'Tôi cần hỗ trợ gấp', '2025-12-04 04:31:03'),
(67, 10, 'bot', 'Dạ, để được hỗ trợ tốt nhất, anh/chị vui lòng để lại Số Điện Thoại, chuyên viên sẽ gọi lại tư vấn ngay ạ!', '2025-12-04 04:31:03'),
(68, 10, 'user', '0944745991', '2025-12-04 04:31:17'),
(69, 10, 'bot', 'Em đã ghi nhận số điện thoại <b>0944745991</b>. Nhân viên cửa hàng sẽ liên hệ với anh/chị sớm nhất ạ! 🥰', '2025-12-04 04:31:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `responses`
--

CREATE TABLE `responses` (
  `id` int(11) NOT NULL,
  `intent_id` int(11) NOT NULL,
  `response_text` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `responses`
--

INSERT INTO `responses` (`id`, `intent_id`, `response_text`) VALUES
(1, 1, 'Tất cả sản phẩm được bảo hành 12 tháng chính hãng. Bạn có thể xem chi tiết tại trang chính sách bảo hành nhé.'),
(2, 1, 'Chính sách bảo hành của chúng tôi là 1 năm. Nếu có lỗi từ nhà sản xuất, bạn sẽ được hỗ trợ 1 đổi 1 trong 7 ngày đầu.'),
(3, 2, 'Chào bạn, tôi là trợ lý ảo. Tôi có thể giúp gì cho bạn?'),
(4, 3, 'Chúng tôi miễn phí vận chuyển toàn quốc cho đơn hàng từ 1.000.000 VNĐ. Thời gian giao hàng dự kiến từ 2-4 ngày.'),
(5, 4, 'Bạn có thể xem chi tiết giá và thông tin sản phẩm trên trang web của chúng tôi, hoặc cho tôi biết mã sản phẩm bạn quan tâm nhé.'),
(6, 4, 'Vui lòng cho tôi biết tên hoặc mã sản phẩm để tôi có thể kiểm tra giá chính xác nhất.'),
(7, 1, 'Sản phẩm được bảo hành chính hãng 12 tháng tại các trung tâm dịch vụ được ủy quyền. Nếu có vấn đề, bạn vui lòng mang sản phẩm và phiếu bảo hành đến cửa hàng gần nhất.'),
(8, 1, 'Chính sách đổi trả áp dụng trong 7 ngày nếu sản phẩm có lỗi từ nhà sản xuất và chưa qua sử dụng. Bạn vui lòng giữ nguyên hộp và phụ kiện.'),
(10, 2, 'Chào bạn! Bạn cần tư vấn về sản phẩm, giá cả, hay chính sách mua hàng?'),
(11, 3, 'Thời gian giao hàng tiêu chuẩn là 2-4 ngày làm việc. Nếu ở TP.HCM hoặc Hà Nội, dịch vụ giao hàng nhanh có thể chỉ mất 24h.'),
(12, 3, 'Chi phí vận chuyển sẽ được tính dựa trên địa chỉ của bạn, và có thể được miễn phí cho các đơn hàng có giá trị trên 5 triệu VND.'),
(13, 4, 'Mức giá cho các mẫu đồng hồ của chúng tôi rất đa dạng. Bạn có thể cho biết ngân sách của mình (ví dụ: dưới 10 triệu) hoặc thương hiệu bạn quan tâm không?'),
(14, 4, 'Bạn có thể xem giá niêm yết và các chương trình khuyến mãi hiện tại trên trang sản phẩm. Nếu cần thêm chi tiết, hãy cho tôi biết mã sản phẩm.'),
(15, 5, 'Để tìm thông tin chi tiết nhất, bạn vui lòng cung cấp mã sản phẩm hoặc tên thương hiệu và dòng sản phẩm bạn muốn tìm hiểu (ví dụ: Orient Sun and Moon).'),
(16, 5, 'Các thông số kỹ thuật như chất liệu, kích thước mặt số, loại máy (Automatic/Quartz) đều có trên trang sản phẩm. Bạn cần thông tin cụ thể nào?'),
(17, 6, 'Chúng tôi chấp nhận thanh toán bằng Thẻ tín dụng/ATM, chuyển khoản ngân hàng, và thanh toán tiền mặt khi nhận hàng (COD).'),
(18, 6, 'Bạn có thể thanh toán trả góp 0% qua thẻ tín dụng của một số ngân hàng đối tác. Bạn muốn tôi gửi thông tin chi tiết không?'),
(21, 8, 'Dạ anh chị có thể cho em biết mình đang cần hỗ trợ gì không ạ?'),
(22, 9, 'Dạ, để được hỗ trợ tốt nhất, anh/chị vui lòng để lại Số Điện Thoại, chuyên viên sẽ gọi lại tư vấn ngay ạ!'),
(23, 7, 'Cửa hàng đồng hồ TSP địa chỉ số 19 ngõ 39, đường Hồ Tùng Mậu, quận Cầu Giấy, Hà Nội');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tbchitietdonhang`
--

CREATE TABLE `tbchitietdonhang` (
  `machitiet` varchar(20) NOT NULL,
  `madonhang` varchar(20) NOT NULL,
  `mahang` varchar(10) DEFAULT NULL,
  `soluong` int(11) DEFAULT NULL,
  `dongia` decimal(18,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tbchitietdonhang`
--

INSERT INTO `tbchitietdonhang` (`machitiet`, `madonhang`, `mahang`, `soluong`, `dongia`) VALUES
('CTDH1763770937833800', 'DH17637709378309737', 'MH017', 1, 1390000000.000),
('CTDH1763770937836400', 'DH17637709378309737', 'MH009', 1, 500000000.000),
('CTDH176377093785003', 'DH17637709378309737', 'MH014', 1, 350000000.000),
('CTDH1763770959553400', 'DH17637709595421342', 'MH003', 4, 72000000.000),
('CTDH1763770991178600', 'DH17637709911733615', 'MH015', 1, 1550000000.000),
('CTDH1763770991180300', 'DH17637709911733615', 'MH013', 1, 200000000.000),
('CTDH1763770991204200', 'DH17637709911733615', 'MH012', 1, 4500000000.000),
('CTDH1763771231372800', 'DH17637712313593348', 'MH005', 1, 1050000000.000),
('CTDH1763771231385500', 'DH17637712313593348', 'MH013', 1, 200000000.000),
('CTDH1763771245070100', 'DH17637712450674110', 'MH010', 1, 270000000.000),
('CTDH1764378449937100', 'DH17643784499283141', 'MH005', 1, 1050000000.000);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tbdonhang`
--

CREATE TABLE `tbdonhang` (
  `madonhang` varchar(20) NOT NULL,
  `makhach` varchar(20) DEFAULT NULL,
  `ngaymua` datetime DEFAULT NULL,
  `tinhtrang` enum('Đã giao','Đang xử lý','Đã hủy','Đang giao hàng') DEFAULT NULL,
  `phuongthuctt` varchar(50) DEFAULT NULL,
  `trangthaitt` varchar(50) DEFAULT NULL,
  `mavandon` varchar(50) DEFAULT NULL,
  `thoigian_capnhat` datetime DEFAULT NULL,
  `makhuyenmai` varchar(20) DEFAULT NULL,
  `giatrigiam` decimal(15,2) DEFAULT 0.00,
  `tongtiendonhang` decimal(18,3) DEFAULT 0.000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tbdonhang`
--

INSERT INTO `tbdonhang` (`madonhang`, `makhach`, `ngaymua`, `tinhtrang`, `phuongthuctt`, `trangthaitt`, `mavandon`, `thoigian_capnhat`, `makhuyenmai`, `giatrigiam`, `tongtiendonhang`) VALUES
('DH17637709378309737', 'KH20251027002', '2025-11-22 07:22:17', 'Đang giao hàng', 'COD', 'Chưa thanh toán', NULL, NULL, 'aa', 0.00, 2217600000.000),
('DH17637709595421342', 'KH20251027002', '2025-11-22 07:22:39', 'Đã giao', 'COD', 'Chưa thanh toán', NULL, NULL, NULL, 0.00, 288000000.000),
('DH17637709911733615', 'KH20251027002', '2025-11-22 07:23:11', 'Đã giao', 'BANK_TRANSFER', 'Chờ thanh toán', NULL, NULL, 'GIAMGIA20AHDSHAD', 0.00, 5000000000.000),
('DH17637712313593348', 'KH20251030001', '2025-11-22 07:27:11', 'Đang xử lý', 'BANK_TRANSFER', 'Chờ thanh toán', NULL, NULL, NULL, 0.00, 1250000000.000),
('DH17637712450674110', 'KH20251030001', '2025-11-22 07:27:25', 'Đã hủy', 'COD', 'Chưa thanh toán', NULL, NULL, NULL, 0.00, 270000000.000),
('DH17643784499283141', 'KH20251027002', '2025-11-29 08:07:29', 'Đang xử lý', 'BANK_TRANSFER', 'Chờ thanh toán', NULL, NULL, NULL, 0.00, 1050000000.000);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tbgiohang_luu`
--

CREATE TABLE `tbgiohang_luu` (
  `makhach` varchar(20) NOT NULL,
  `mahang` varchar(20) NOT NULL,
  `soluong` int(11) NOT NULL,
  `checked` tinyint(1) DEFAULT 1 COMMENT 'Trạng thái được chọn thanh toán'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tbgiohang_luu`
--

INSERT INTO `tbgiohang_luu` (`makhach`, `mahang`, `soluong`, `checked`) VALUES
('KH20251027002', 'MH013', 1, 1),
('KH20251030001', 'MH002', 1, 0),
('KH20251030001', 'MH003', 1, 0),
('KH20251030001', 'MH004', 1, 1),
('KH20251030001', 'MH008', 1, 0),
('KH20251030001', 'MH017', 1, 0),
('KH20251129001', 'MH007', 1, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tbhinhanhchitiet`
--

CREATE TABLE `tbhinhanhchitiet` (
  `id` int(11) NOT NULL,
  `mahang` varchar(10) DEFAULT NULL,
  `hinhanh_chitiet` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tbhinhanhchitiet`
--

INSERT INTO `tbhinhanhchitiet` (`id`, `mahang`, `hinhanh_chitiet`) VALUES
(529, 'MH001', 'patek-philippe-5205r-5-da432e62-0d15-4be4-bfc7-ff888501846e.webp'),
(530, 'MH001', 'patek-philippe-5205r-4-51db541e-1f69-4a40-8b2f-82e2b6072668.webp'),
(531, 'MH001', 'patek-philippe-5205r-2-6ea59142-4663-4ff5-9e06-467ff5fb8c65.webp'),
(532, 'MH001', 'patek-philippe-complications-5205r-010.webp'),
(533, 'MH002', 'dong-ho-nu-patek-philippe-twenty-4-7300-1200a-011-mau-bac-xanh-66389f06104bf-06052024161238.webp'),
(534, 'MH002', 'dong-ho-nu-patek-philippe-twenty-4-7300-1200a-011-mau-bac-xanh-66389f06101ba-06052024161238.webp'),
(535, 'MH002', 'dong-ho-nu-patek-philippe-twenty-4-7300-1200a-011-mau-bac-xanh-66389f060fe7b-06052024161238.webp'),
(536, 'MH002', 'dong-ho-nu-patek-philippe-twenty-4-7300-1200a-011-mau-bac-xanh-66389f060faf1-06052024161238.webp'),
(537, 'MH003', 'dong-ho-patek-philippe-nautilus-5711-1a-014-olive-green-mau-bac-xanh-663314fc73d90-02052024112220.webp'),
(538, 'MH003', 'dong-ho-patek-philippe-nautilus-5711-1a-014-olive-green-mau-bac-xanh-663314fc73adb-02052024112220.webp'),
(539, 'MH003', 'dong-ho-patek-philippe-nautilus-5711-1a-014-olive-green-mau-bac-xanh-663314fc7385b-02052024112220.webp'),
(540, 'MH003', 'dong-ho-patek-philippe-nautilus-5711-1a-014-olive-green-mau-bac-xanh-663314fc73590-02052024112220.webp'),
(541, 'MH003', 'dong-ho-patek-philippe-nautilus-5711-1a-014-olive-green-mau-bac-xanh-663314fc731d6-02052024112220.webp'),
(542, 'MH004', 'dong-ho-nam-patek-philippe-complications-5905r-010-mau-xanh-vang-hong-66680222aa5fe-11062024145202.webp'),
(543, 'MH004', 'dong-ho-nam-patek-philippe-complications-5905r-010-mau-xanh-vang-hong-66680222aa00f-11062024145202.webp'),
(544, 'MH004', 'dong-ho-nam-patek-philippe-complications-5905r-010-mau-xanh-vang-hong-66680222a9814-11062024145202.webp'),
(545, 'MH004', 'dong-ho-nam-patek-philippe-complications-5905r-010-mau-xanh-vang-hong-66680222a927c-11062024145202.webp'),
(546, 'MH005', 'dong-ho-nu-patek-philippe-gondo-serata-4962-200r-001-mau-nau-vang-65d57d9a60a26-21022024113538.webp'),
(547, 'MH005', 'dong-ho-nu-patek-philippe-gondo-serata-4962-200r-001-mau-nau-vang-65d57d9a606d1-21022024113538.webp'),
(548, 'MH006', 'dong-ho-nam-patek-philippe-grand-complications-5531r-012-minute-repeater-world-time-mau-nau-6589259de1bb9-25122023134757.webp'),
(549, 'MH006', 'dong-ho-nam-patek-philippe-grand-complications-5531r-012-minute-repeater-world-time-mau-nau-6589259de14db-25122023134757.webp'),
(550, 'MH007', 'dong-ho-nu-rolex-datejust-31m-278384rbr-0022-mau-bac-xanh-67f8870239784-11042025100538.webp'),
(551, 'MH007', 'dong-ho-nu-rolex-datejust-31m-278384rbr-0022-mau-bac-xanh-67f887023901a-11042025100538.webp'),
(552, 'MH008', 'dong-ho-rolex-cosmograph-daytona-steel-116500ln-0001-mau-bac-trang-669746e8472ef-17072024112200.webp'),
(553, 'MH008', 'dong-ho-rolex-cosmograph-daytona-steel-116500ln-0001-mau-bac-trang-669746e846e42-17072024112200.webp'),
(554, 'MH008', 'dong-ho-rolex-cosmograph-daytona-steel-116500ln-0001-mau-bac-trang-669746e84693a-17072024112200.webp'),
(555, 'MH008', 'dong-ho-rolex-cosmograph-daytona-steel-116500ln-0001-mau-bac-trang-669746e846425-17072024112200.webp'),
(556, 'MH008', 'dong-ho-rolex-cosmograph-daytona-steel-116500ln-0001-mau-bac-trang-669746e845e74-17072024112200.webp'),
(557, 'MH009', 'dong-ho-nu-rolex-oyster-perpetual-36mm-celebration-dial-automatic-chronometer-126000-0009-mau-bac-xanh-66d90f6815ce7-05092024085448.webp'),
(558, 'MH009', 'dong-ho-nu-rolex-oyster-perpetual-36mm-celebration-dial-automatic-chronometer-126000-0009-mau-bac-xanh-66d90f6815972-05092024085448.webp'),
(559, 'MH009', 'dong-ho-nu-rolex-oyster-perpetual-36mm-celebration-dial-automatic-chronometer-126000-0009-mau-bac-xanh-66d90f68156ba-05092024085448.webp'),
(560, 'MH009', 'dong-ho-nu-rolex-oyster-perpetual-36mm-celebration-dial-automatic-chronometer-126000-0009-mau-bac-xanh-66d90f681533b-05092024085448.webp'),
(561, 'MH010', 'dong-ho-nam-rolex-daydate-228398tbr-mau-vang-den-667a40d2b1a87-25062024110018.webp'),
(562, 'MH010', 'dong-ho-nam-rolex-daydate-228398tbr-mau-vang-den-667a40d2b1562-25062024110018.webp'),
(563, 'MH010', 'dong-ho-nam-rolex-daydate-228398tbr-mau-vang-den-667a40d2b10fc-25062024110018.webp'),
(564, 'MH010', 'dong-ho-nam-rolex-daydate-228398tbr-mau-vang-den-667a40d2b0a5f-25062024110018.webp'),
(565, 'MH010', 'dong-ho-nam-rolex-daydate-228398tbr-mau-vang-den-667a40d2b029d-25062024110018.webp'),
(566, 'MH011', 'dong-ho-nam-rolex-yacht-master-116655-oyster-40mm-mau-den-6656def4170fe-29052024145324.webp'),
(567, 'MH011', 'dong-ho-nam-rolex-yacht-master-116655-oyster-40mm-mau-den-6656def416caf-29052024145324.webp'),
(568, 'MH011', 'dong-ho-nam-rolex-yacht-master-116655-oyster-40mm-mau-den-6656def41685e-29052024145324.webp'),
(569, 'MH011', 'dong-ho-nam-rolex-yacht-master-116655-oyster-40mm-mau-den-6656def41641c-29052024145324.webp'),
(570, 'MH011', 'dong-ho-nam-rolex-yacht-master-116655-oyster-40mm-mau-den-6656def415fe5-29052024145324.webp'),
(571, 'MH012', 'dong-ho-rolex-daytona-116598saco-leopard-hoa-tiet-da-bao-66359ddf35bde-04052024093055.webp'),
(572, 'MH012', 'dong-ho-rolex-daytona-116598saco-leopard-hoa-tiet-da-bao-66359ddf357d4-04052024093055.webp'),
(573, 'MH012', 'dong-ho-rolex-daytona-116598saco-leopard-hoa-tiet-da-bao-66359ddf354aa-04052024093055.webp'),
(574, 'MH012', 'dong-ho-rolex-daytona-116598saco-leopard-hoa-tiet-da-bao-66359ddf351a8-04052024093055.webp'),
(575, 'MH012', 'dong-ho-rolex-daytona-116598saco-leopard-hoa-tiet-da-bao-66359ddf34dc1-04052024093055.webp'),
(576, 'MH013', 'dong-ho-nam-hublot-classic-fusion-titanium-42mm-511-nx-7071-lr-mau-xam-brand-new-fresh-date-t4-2025-d-pt-67f6447509476-09042025165709.webp'),
(577, 'MH014', 'dong-ho-nu-hublot-classic-fusion-king-gold-diamond-blue-mau-xanh-duong-6720622a4287e-29102024111850.webp'),
(578, 'MH014', 'dong-ho-nu-hublot-classic-fusion-king-gold-diamond-blue-mau-xanh-duong-6720622a4228b-29102024111850.webp'),
(579, 'MH014', 'dong-ho-nu-hublot-classic-fusion-king-gold-diamond-blue-mau-xanh-duong-6720622a42017-29102024111850.webp'),
(580, 'MH014', 'dong-ho-nu-hublot-classic-fusion-king-gold-diamond-blue-mau-xanh-duong-6720622a41bd9-29102024111850.webp'),
(581, 'MH015', 'dong-ho-hublot-big-bang-unico-yellow-sapphire-limited-42-441-jy-4909-rt-mau-vang-667a3b7493308-25062024103724.webp'),
(582, 'MH015', 'dong-ho-hublot-big-bang-unico-yellow-sapphire-limited-42-441-jy-4909-rt-mau-vang-667a3b7492fff-25062024103724.webp'),
(583, 'MH015', 'dong-ho-hublot-big-bang-unico-yellow-sapphire-limited-42-441-jy-4909-rt-mau-vang-667a3b7492cd5-25062024103724.webp'),
(584, 'MH015', 'dong-ho-hublot-big-bang-unico-yellow-sapphire-limited-42-441-jy-4909-rt-mau-vang-667a3b749293c-25062024103724.webp'),
(585, 'MH015', 'dong-ho-hublot-big-bang-unico-yellow-sapphire-limited-42-441-jy-4909-rt-mau-vang-667a3b7492605-25062024103724.webp'),
(586, 'MH016', 'dong-ho-nam-hublot-spirit-of-bigbang-sang-bleu-sapphire-42mm-limited-100pcs-mau-trong-suot-6667ecde3e5e9-11062024132118.webp'),
(587, 'MH016', 'dong-ho-nam-hublot-spirit-of-bigbang-sang-bleu-sapphire-42mm-limited-100pcs-mau-trong-suot-6667ecde3e1e6-11062024132118.webp'),
(588, 'MH016', 'dong-ho-nam-hublot-spirit-of-bigbang-sang-bleu-sapphire-42mm-limited-100pcs-mau-trong-suot-6667ecde3de64-11062024132118.webp'),
(589, 'MH016', 'dong-ho-nam-hublot-spirit-of-bigbang-sang-bleu-sapphire-42mm-limited-100pcs-mau-trong-suot-6667ecde3daf8-11062024132118.webp'),
(590, 'MH016', 'dong-ho-nam-hublot-spirit-of-bigbang-sang-bleu-sapphire-42mm-limited-100pcs-mau-trong-suot-6667ecde3d703-11062024132118.webp'),
(591, 'MH017', 'dong-ho-nu-hublot-hublot-oneclick-rainbow-steel-39mm-phoi-mau-662b5afb4b7c1-26042024144251.webp'),
(592, 'MH017', 'dong-ho-nu-hublot-hublot-oneclick-rainbow-steel-39mm-phoi-mau-662b5afb4b415-26042024144251.webp'),
(593, 'MH017', 'dong-ho-nu-hublot-hublot-oneclick-rainbow-steel-39mm-phoi-mau-662b5afb4b0c2-26042024144251.webp'),
(594, 'MH017', 'dong-ho-nu-hublot-hublot-oneclick-rainbow-steel-39mm-phoi-mau-662b5afb4ae03-26042024144251.webp'),
(595, 'MH017', 'dong-ho-nu-hublot-hublot-oneclick-rainbow-steel-39mm-phoi-mau-662b5afb4aacf-26042024144251.webp'),
(596, 'MH018', 'dong-ho-hublot-bigbang-41mm-gold-linen-limited-200pcs-341-xg-1280-lr-1229-mau-xanh-den-662b322e1b10d-26042024114846.webp'),
(597, 'MH018', 'dong-ho-hublot-bigbang-41mm-gold-linen-limited-200pcs-341-xg-1280-lr-1229-mau-xanh-den-662b322e1ae14-26042024114846.webp'),
(598, 'MH018', 'dong-ho-hublot-bigbang-41mm-gold-linen-limited-200pcs-341-xg-1280-lr-1229-mau-xanh-den-662b322e1ab22-26042024114846.webp'),
(599, 'MH018', 'dong-ho-hublot-bigbang-41mm-gold-linen-limited-200pcs-341-xg-1280-lr-1229-mau-xanh-den-662b322e1a785-26042024114846.webp'),
(600, 'MH018', 'dong-ho-hublot-bigbang-41mm-gold-linen-limited-200pcs-341-xg-1280-lr-1229-mau-xanh-den-662b322e1a49c-26042024114846.webp');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tbkhachhang`
--

CREATE TABLE `tbkhachhang` (
  `makhach` varchar(20) NOT NULL,
  `tenkhach` varchar(35) DEFAULT NULL,
  `ngaysinh` date NOT NULL DEFAULT '1970-01-01',
  `sodienthoai` varchar(15) DEFAULT NULL,
  `diachi` varchar(255) DEFAULT NULL,
  `gioitinh` enum('Nam','Nữ') DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tbkhachhang`
--

INSERT INTO `tbkhachhang` (`makhach`, `tenkhach`, `ngaysinh`, `sodienthoai`, `diachi`, `gioitinh`, `username`) VALUES
('KH20251027001', 'admin', '2011-11-11', '0944745991', 'Thôn 8 Phúc Sơn, Anh Sơn, Nghệ An', 'Nam', 'admin@gmail.com'),
('KH20251027002', 'Tâm', '2004-11-03', '0944745991', 'Thôn 8 Phúc Sơn, Anh Sơn, Nghệ An', 'Nam', 'nv1'),
('KH20251030001', 'Nguyễn Văn Tâm', '2011-11-11', '0944745991', 'Thôn 8 Phúc Sơn, Anh Sơn, Nghệ An', 'Nam', 'nv2'),
('KH20251030002', 'Tam', '2011-11-11', '0944745991', 'số 19', 'Nam', 'nv3'),
('KH20251121001', 'Nguyễn Văn Tâm Tâm', '1111-11-11', '0944745991', 'Thôn 8 Phúc Sơn, Anh Sơn, Nghệ An', 'Nam', 'nv4'),
('KH20251129001', 'Nguyễn Văn Tâm Tâm', '1111-11-11', '0944745991', 'Thôn 8 Phúc Sơn, Anh Sơn, Nghệ An', 'Nam', '111');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tbkhuyenmai`
--

CREATE TABLE `tbkhuyenmai` (
  `makhuyenmai` varchar(20) NOT NULL,
  `tenkhuyenmai` varchar(255) NOT NULL,
  `loai` enum('PHAN_TRAM','TIEN_MAT') NOT NULL,
  `giatri` decimal(10,2) NOT NULL,
  `dieukientoithieu` decimal(15,2) DEFAULT 0.00,
  `ngaybatdau` datetime NOT NULL,
  `ngayketthuc` datetime NOT NULL,
  `trangthai` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tbkhuyenmai`
--

INSERT INTO `tbkhuyenmai` (`makhuyenmai`, `tenkhuyenmai`, `loai`, `giatri`, `dieukientoithieu`, `ngaybatdau`, `ngayketthuc`, `trangthai`) VALUES
('AA', '', 'PHAN_TRAM', 1.00, 0.00, '2025-11-21 16:52:00', '2025-11-28 16:52:00', 1),
('GIAMGIA20AHDSHAD', '', 'PHAN_TRAM', 20.00, 0.00, '2025-11-20 06:58:00', '2025-11-22 06:58:00', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tbmathang`
--

CREATE TABLE `tbmathang` (
  `mahang` varchar(10) NOT NULL,
  `tenhang` varchar(255) DEFAULT NULL,
  `mota` text DEFAULT NULL,
  `dongia` decimal(18,3) DEFAULT NULL,
  `nguongoc` varchar(100) DEFAULT NULL,
  `thuonghieu` varchar(255) DEFAULT NULL,
  `hinhanh` varchar(255) DEFAULT NULL,
  `conhang` enum('Còn hàng','Hết hàng') DEFAULT 'Còn hàng'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tbmathang`
--

INSERT INTO `tbmathang` (`mahang`, `tenhang`, `mota`, `dongia`, `nguongoc`, `thuonghieu`, `hinhanh`, `conhang`) VALUES
('MH001', 'Đồng hồ Patek Philippe Complications 5205R-010', 'Patek Philippe 5205R-010 là chiếc đồng hồ lịch hàng năm (Annual Calendar) mang phong cách thanh lịch và đẳng cấp, kết hợp hoàn hảo giữa kỹ thuật chế tác tinh xảo và thẩm mỹ sang trọng. Với vỏ vàng hồng 18K, mặt số đen sunburst, và chức năng moonphase, đây là biểu tượng của sự tinh tế trong dòng Complications của thương hiệu Thụy Sĩ danh tiếng.\r\n\r\nThông số sản phẩm\r\nThương hiệu:	Patek Philippe\r\nXuất xứ:	Thụy Sĩ\r\nĐối tượng:	Nam\r\nChống nước:	30m (3ATM)\r\nLoại mặt số:	Cơ tự động (Automatic)\r\nLoại máy:	Calibre 324 S QA LU 24H/206\r\nChất liệu kính:	Sapphire chống trầy xước\r\nChất liệu dây:	Da cá sấu cao cấp\r\nSize mặt:	40 mm\r\nĐộ dày:	11.36 mm\r\nMàu mặt:	Đen sunburst\r\nSeries:	Complications\r\nĐường kính mặt:	40 mm\r\nMàu vỏ:	Vàng hồng 18K\r\nHình dáng mặt:	Mặt tròn\r\nBộ sưu tập:	Complications Annual Calendar\r\nTiện ích:	Lịch thứ – ngày – tháng, Moonphase, hiển thị 24 giờ, tự động lên cót, nắp lưng trong suốt', 15500000.000, 'Thụy Sĩ', 'Patek Philippe', 'patek-philippe-complications-5205r-010.png', 'Hết hàng'),
('MH002', 'Đồng Hồ Nữ Patek Philippe Twenty~4 7300/1200A-011 Màu Bạc Xanh', 'Chiếc Twenty~4 7300/1200A-011 là sự kết hợp tinh tế giữa nét hiện đại và sang trọng dành cho nữ giới: vỏ bằng thép không gỉ, mặt số xanh (olive sunburst) bắt mắt, và viền bezel đính kim cương – thể hiện đẳng cấp từ thương hiệu Thụy Sĩ danh tiếng.\r\n\r\nThông số sản phẩm\r\nThương hiệu:	Patek Philippe\r\nXuất xứ:	Thụy Sĩ\r\nĐối tượng:	Nữ\r\nChống nước:	30 m (3 ATM) \r\nLoại mặt số:	Cơ tự động (Automatic)\r\nLoại máy:	Calibre 26-330 S C – tự lên cót, hiển thị ngày tại vị trí 6 giờ \r\nChấtiệu kính:	Sapphire chống trầy xước, mặt đáy trong suốt\r\nChất liệu dây:	Thép không gỉ (Steel) bọc toàn bộ, khoá gập đặc biệt\r\nSize mặt:	36 mm đường kính \r\nĐộ dày:	10.05 mm \r\n\r\nMàu mặt:	Xanh olive (sunburst)\r\n\r\nSeries:	Twenty~4 Automatic\r\nĐường kính mặt:	36 mm (như “Size mặt”)\r\nMàu vỏ:	Bạc – Thép không gỉ đánh bóng\r\nHình dáng mặt:	Mặt tròn\r\nBộ sưu tập:	Twenty~4 – dành cho nữ giới hiện đại \r\n\r\nTiện ích:	Hiển thị giờ – phút – giây, cửa sổ ngày ngày (date) tại 6 giờ; vỏ đính kim cương (160 viên trên vành bezel)', 8900000.000, 'Thụy Sĩ', 'Patek Philippe', 'dong-ho-nu-patek-philippe-twenty-4-7300-1200a-011-mau-bac-xanh-66389f060f7f3-06052024161238.webp', 'Còn hàng'),
('MH003', 'Đồng Hồ Patek Philippe Nautilus 5711/1A-014 Olive Green Màu Bạc Xanh', 'Chiếc Nautilus 5711/1A-014 là phiên bản giới hạn nổi bật với mặt số màu xanh olive sunburst hiếm có, được xem là “lời tạm biệt” sang trọng của Patek Philippe cho dòng 5711 bằng thép huyền thoại. Mẫu đồng hồ kết hợp hoàn hảo giữa phong cách thể thao thanh lịch, độ hoàn thiện tinh xảo và giá trị sưu tầm cao.\r\n\r\nThông số sản phẩm\r\nThương hiệu:	Patek Philippe\r\nXuất xứ:	Thụy Sĩ\r\nĐối tượng:	Nam / Unisex\r\nChống nước:	120m (12 ATM)\r\nLoại mặt số:	Cơ tự động (Automatic)\r\nLoại máy:	Calibre 26-330 S C\r\nChất liệu kính:	Sapphire chống trầy xước (trước và sau)\r\nChất liệu dây:	Thép không gỉ\r\nSize mặt:	40 mm\r\nĐộ dày:	8.3 mm\r\nMàu mặt:	Xanh olive (sunburst)\r\nSeries:	Nautilus\r\nĐường kính mặt:	40 mm\r\nMàu vỏ:	Bạc – Thép không gỉ\r\nHình dáng mặt:	Mặt tròn đặc trưng Nautilus\r\nBộ sưu tập:	Nautilus Automatic\r\nTiện ích:	Giờ – Phút – Giây trung tâm, Lịch ngày tại vị trí 3 giờ', 72000000.000, 'Thụy Sĩ', 'Patek Philippe', 'dong-ho-patek-philippe-nautilus-5711-1a-014-olive-green-mau-bac-xanh-663314fc72da2-02052024112220.webp', 'Còn hàng'),
('MH004', 'Đồng Hồ Nam Patek Philippe Complications 5905R-010 Màu Xanh/ Vàng Hồng', 'Đồng Hồ Nam Patek Philippe Complications 5905R-010 Màu Xanh/ Vàng Hồng là chiếc đồng hồ cao cấp đến từ thương hiệu Patek Philippe nổi tiếng Thụy Sỹ. Chiếc đồng hồ Patek Philippe 5905R-010 được trang bị chức năng cao cấp và mang đến vẻ đẹp sang trọng cho người dùng. \r\nThiết Kế Đồng Hồ Nam Patek Philippe Complications 5905R-010 Màu Xanh/ Vàng Hồng\r\nĐồng hồ Patek Philippe Complications 5905R-010 sở hữu vỏ vàng nguyên khối có kích thước 42mm, không quá lớn, rất vừa vặn với nhiều dáng cổ tay của nam giới. Giống như mẫu đã có từ trước, cỗ máy Patek Philippe Complications 5905R-010 vẫn giữ chức năng là lịch thường niên kết hợp với chronograph trong 60 phút. Các vạch chia rõ ràng trên mặt số khiến cho đồng hồ có vẻ đẹp chặt chẽ và đối xứng, rất dễ để theo dõi các chỉ số. \r\n\r\nĐồng Hồ Nam Patek Philippe Complications 5905R-010 Màu Xanh/ Vàng Hồng\r\n\r\nPhiên bản Patek Philippe Complications 5905R-010 có mặt số màu xanh kèm bề mặt dạng chải tia đem đến cái nhìn hiện đại và thanh lịch. Vỏ với cấu trúc đặc biệt tinh xảo đã được điểm xuyết thêm vẻ ấn tượng với vành bezel hơi lõm. \r\n\r\nĐồng Hồ Nam Patek Philippe Complications 5905R-010 Màu Xanh/ Vàng Hồng\r\n\r\nPatek Philippe Complications 5905R-010 sử dụng bộ máy cơ tự động Patek Philippe Caliber CH 28-520 IRM QA 24H, với độ chính xác tương đối cao.', 20000000.000, 'Thụy Sĩ', 'Patek Philippe', 'dong-ho-nam-patek-philippe-complications-5905r-010-mau-xanh-vang-hong-66680222a8c3a-11062024145202.webp', 'Còn hàng'),
('MH005', 'Đồng Hồ Nữ Patek Philippe Gondo Serata 4962/200R-001 Màu Nâu Vàng', 'Đồng Hồ Nữ Patek Philippe Gondo Serata 4962/200R-001 Màu Nâu Vàng là chiếc đồng hồ cao cấp đã qua sử dụng đến từ thương hiệu Patek Philippe nổi tiếng Thụy Sỹ. Chiếc đồng hồ Patek Philippe Gondo Serata 4962/200R-001 thiết kế nổi bật, cao cấp, mang đến vẻ đẹp sang trọng cho người dùng. \r\n\r\nĐồng Hồ Nữ Patek Philippe Gondo Serata 4962/200R-001 Màu Nâu Vàng\r\n\r\nĐôi nét về thương hiệu Patek Philippe\r\nPatek Philippe hay còn gọi là Patek Philippe Geneva là một nhà sản xuất đồng hồ đeo tay và đồng hồ bỏ túi cao cấp của Thụy Sỹ, được thành lập năm 1851 có trụ sở tại Geneva và thung lũng Joux. Hãng thiết kế sản xuất đồng hồ và bộ chuyển động đồng hồ, trong đó có những chiếc đồng hồ cơ cực kì tinh xảo.\r\n\r\nĐồng Hồ Nữ Patek Philippe Gondo Serata 4962/200R-001 Màu Nâu Vàng\r\n\r\nRất nhiều các chuyên gia, người hâm mộ đồng hồ đánh giá Patek Philippe là thương hiệu đồng hồ đeo tay có uy tín, danh tiếng trên thế giới.\r\n\r\nThiết kế Đồng Hồ Nữ Patek Philippe Gondo Serata 4962/200R-001 Màu Nâu Vàng\r\nĐồng Hồ Nữ Patek Philippe Gondo Serata 4962/200R-001 Màu Nâu Vàng mang phong cách nổi bật, sang trọng, sở hữu đường kính mặt số là 28.6mm × 40.85mm với lớp vỏ bằng vàng hồng và dây đeo được làm từ da cao cấp. \r\n\r\nĐồng Hồ Nữ Patek Philippe Gondo Serata 4962/200R-001 Màu Nâu Vàng\r\n\r\nMặt kính sapphire có khả năng chịu lực và hạn chế trầy xước tốt, hệ số chịu nước 3ATM.\r\n\r\nĐồng Hồ Nữ Patek Philippe Gondo Serata 4962/200R-001 Màu Nâu Vàng\r\n\r\nĐồng Hồ Patek Philippe Gondo Serata 4962/200R-001 là một siêu phẩm mang lại vẻ sang trọng, sẽ là mẫu đồng hồ mang đến cho người dùng trải nghiệm hoàn hảo. Patek Philippe không chỉ đơn thuần là thương hiệu mang đến những thiết kế đẹp, chất lượng, mà đó còn là cả một đẳng cấp, như một chuẩn mực để người ta phải hướng tới.', 1050000000.000, 'Thụy Sĩ', 'Patek Philippe', 'dong-ho-nu-patek-philippe-gondo-serata-4962-200r-001-mau-nau-vang-65d57d9a602d6-21022024113538.webp', 'Còn hàng'),
('MH006', 'Đồng Hồ Nam Patek Philippe Grand Complications 5531R-012 Minute Repeater World Time Màu Nâu', 'Đồng Hồ Nam Patek Philippe Grand Complications 5531R-012 Minute Repeater World Time Màu Nâu là chiếc đồng hồ cao cấp đến từ thương hiệu Patek Philippe nổi tiếng Thụy Sỹ. Chiếc đồng hồ Patek Philippe Grand Complications 5531R-012 được trang bị tiện ích, cao cấp, mang đến vẻ đẹp sang trọng cho người dùng. \r\n\r\nĐồng Hồ Nam Patek Philippe Grand Complications 5531R-012 Minute Repeater World Time Màu Nâu\r\n\r\nĐôi nét về thương hiệu Patek Philippe\r\nPatek Philippe hay còn gọi là Patek Philippe Geneva là một nhà sản xuất đồng hồ đeo tay và đồng hồ bỏ túi cao cấp của Thụy Sỹ, được thành lập năm 1851 có trụ sở tại Geneva và thung lũng Joux. Hãng thiết kế sản xuất đồng hồ và bộ chuyển động đồng hồ, trong đó có những chiếc đồng hồ cơ cực kì tinh xảo.\r\n\r\nĐồng Hồ Nam Patek Philippe Grand Complications 5531R-012 Minute Repeater World Time Màu Nâu\r\n\r\nRất nhiều các chuyên gia, người hâm mộ đồng hồ đánh giá Patek Philippe là thương hiệu đồng hồ đeo tay có uy tín, danh tiếng trên thế giới.\r\n\r\nThiết kế Đồng Hồ Nam Patek Philippe Grand Complications 5531R-012 Minute Repeater World Time Màu Nâu\r\nĐồng Hồ Nam Patek Philippe Grand Complications 5531R-012 Minute Repeater World Time mang phong cách thanh lịch, sang trọng với lớp vỏ vàng hồng 18k được chải bóng và trang trí với hoa văn hobnail bằng tay, sở hữu đường kính 40mm và độ dày vỏ 11.49 mm. Dây đeo bằng da vân cá nâu mềm mại, ôm tay hoàn hảo.\r\n\r\nĐồng Hồ Nam Patek Philippe Grand Complications 5531R-012 Minute Repeater World Time Màu Nâu\r\n\r\nMặt số đồng hồ màu trắng cùng bộ kim giờ, phút hình lá, làm từ vàng hồng nổi bật dễ quan sát và một mặt số nhỏ trung tâm tráng men cloisonné mô tả các vườn nho Lavaux trên bờ hồ Geneva đầy ấn tượng. \r\n\r\nĐồng hồ được trang bị bộ chuyển động Caliber R 27 HU với cơ chế điểm chuông Minute Repeater và World Time, giờ, phút, chu kỳ mặt trăng, trang bị khoảng 48 giờ dự trữ năng lượng vô cùng tiện lợi và khả năng kháng nước. \r\n\r\nĐồng Hồ Nam Patek Philippe Grand Complications 5531R-012 Minute Repeater World Time Màu Nâu\r\n\r\nĐồng Hồ Nam Patek Philippe Grand Complications 5531R-012 là một siêu phẩm đo thời gian mang lại vẻ sang trọng đầy lịch lãm, sẽ là mẫu đồng hồ mang đến cho người dùng trải nghiệm ấn tượng. Patek Philippe không chỉ đơn thuần là thương hiệu mang đến những thiết kế đẹp, chất lượng, mà đó còn là cả một đẳng cấp, như một chuẩn mực để người ta phải hướng tới.', 307800000.000, 'Thụy Sĩ', 'Patek Philippe', 'dong-ho-nam-patek-philippe-grand-complications-5531r-012-minute-repeater-world-time-mau-nau-6589259de2125-25122023134757.webp', 'Còn hàng'),
('MH007', 'Đồng Hồ Nữ Rolex Datejust 31m 278384RBR-0022 Màu Bạc/Xanh', 'Đồng Hồ Nữ Rolex Datejust 31m 278384RBR-0022 Màu Bạc/Xanh là chiếc đồng hồ cao cấp đến từ thương hiệu Rolex. Mẫu đồng hồ Rolex Datejust 31m 278384RBR-0022 với thiết kế ấn tượng mang đến vẻ đẹp sang trọng cho người dùng.\r\n\r\nĐồng Hồ Nữ Rolex Datejust 31m 278384RBR-0022 Màu Bạc/Xanh\r\n\r\nVề thương hiệu Rolex\r\nTính đến nửa đầu năm 2017 thì thương hiệu đồng hồ nổi tiếng trên thế giới đó chính là Rolex – nhà sản xuất lừng danh đến từ Thụy Sĩ với nguồn gốc từ Anh Quốc. Với giá t.r.ị thương hiệu ước tính là 8.053 tỷ USD, Rolex cũng là thương hiệu sản xuất đồng hồ đeo tay trong top 10 thương hiệu hàng sang trọng trên thế giới.\r\n\r\nĐồng Hồ Nữ Rolex Datejust 31m 278384RBR-0022 Màu Bạc/Xanh\r\n\r\nBắt đầu nổi lên với phiên bản đồng hồ đeo tay kháng nước và từng bước phát triển, khẳng định được vị trí và tên tuổi của thương hiệu với phong cách lịch lãm. Và logo hình vương miện 5 đỉnh ở trên sản phẩm đồng hồ đeo tay của Rolex chính là một biểu tượng rất nổi tiếng ở thị trường đồng hồ thế giới.\r\n\r\nThiết Kế Đồng Hồ Nữ Rolex Datejust 31m 278384RBR-0022 Màu Bạc/Xanh\r\nĐồng Hồ Nữ Rolex Datejust 31m 278384RBR-0022 Màu Bạc/Xanh sở hữu mặt số hình tròn với đường kính 31mm. Vỏ và dây đeo được hoàn thiện từ thép không gỉ Oystersteel kết hợp vành bezel khía bằng vàng trắng 18K cao cấp, sáng bóng, cứng cáp, chịu lực tốt và hạn chế trầy xước, ngừa ăn mòn vượt trội.\r\n\r\nĐồng Hồ Nữ Rolex Datejust 31m 278384RBR-0022 Màu Bạc/Xanh\r\n\r\nMặt kính sapphire trong suốt, rõ nét cho khả năng hạn chế trầy xươc tốt. Viền vỏ nạm kim cương lấp lánh kết hợp mặt số màu xanh lá họa tiết sunray đặc biệt với cọc chỉ giờ và kim thanh mảnh, sáng, được hoàn thiện tinh xảo, sắc nét, dễ dàng quan sát. \r\n\r\nBộ máy: máy cơ, tự lên dây\r\nTính năng: Kim giờ, kim phút, kim giây trung tâm, hiển thị ngày với chức năng cài đặt nhanh\r\nDự trữ năng lượng: Xấp xỉ 55 tiếng\r\nChịu nước lên đến 100 mét / 330 feet phù hợp mọi hoạt động hàng ngày\r\nĐồng Hồ Nữ Rolex Datejust 31m 278384RBR-0022 Màu Bạc/Xanh\r\n\r\nĐồng Hồ Nữ Rolex Datejust 31m 278384RBR-0022 dễ dàng kết hợp với phụ kiện và trang phục để trở nên nổi bật hàng ngày cũng như các sự kiện, bữa tiệc, là một siêu phẩm mang lại vẻ sang trọng đầy nữ tính và cho người dùng trải nghiệm hoàn hảo.', 490000000.000, 'Thụy Sĩ', 'Rolex', 'dong-ho-nu-rolex-datejust-31m-278384rbr-0022-mau-bac-xanh-67f8870238a51-11042025100538.webp', 'Còn hàng'),
('MH008', 'Đồng Hồ Rolex Cosmograph Daytona Steel 116500LN-0001 Màu Bạc Trắng', 'Đồng Hồ Rolex Cosmograph Daytona Steel 116500LN-0001 Màu Bạc Trắng là chiếc đồng hồ cao cấp đến từ thương hiệu Rolex. Khi sở hữu siêu phẩm Steel 116500LN-0001 bạn sẽ cảm nhận như cả thế giới đang ở trên cổ tay mình.\r\n\r\nĐồng Hồ Rolex Cosmograph Daytona Steel 116500LN-0001 Màu Bạc Trắng\r\n\r\nVề thương hiệu Rolex\r\nTính đến nửa đầu năm 2017 thì thương hiệu đồng hồ nổi tiếng trên thế giới đó chính là Rolex – nhà sản xuất lừng danh đến từ Thụy Sĩ với nguồn gốc từ Anh Quốc. Với giá t.r.ị thương hiệu ước tính là 8.053 tỷ USD, Rolex cũng là thương hiệu sản xuất đồng hồ đeo tay trong top 10 thương hiệu hàng sang trọng trên thế giới.\r\n\r\nĐồng Hồ Rolex Cosmograph Daytona Steel 116500LN-0001 Màu Bạc Trắng\r\n\r\nBắt đầu nổi lên với phiên bản đồng hồ đeo tay kháng nước và từng bước phát triển, khẳng định được vị trí và tên tuổi của thương hiệu với phong cách lịch lãm. Và logo hình vương miện 5 đỉnh ở trên sản phẩm đồng hồ đeo tay của Rolex chính là một biểu tượng rất nổi tiếng ở thị trường đồng hồ thế giới.\r\n\r\nThiết Kế Đồng Hồ Rolex Cosmograph Daytona Steel 116500LN-0001 Màu Bạc Trắng\r\nĐồng hồ Rolex Cosmograph Daytona Steel 116500LN-0001 có điểm nhấn nổi bật là vành bezel được làm từ gốm Cerachrom, vật liệu mới được Rolex sử dụng trong năm 2005. Trước đó Cerachrom chỉ được Rolex ứng dụng trên dòng đồng hồ GMT-Master và Submariner, đến năm 2016, những chiếc Daytona mới có vành bezel Cerachrom, thay thế hoàn toàn cho vành bằng thép không gỉ.\r\n\r\nĐồng Hồ Rolex Cosmograph Daytona Steel 116500LN-0001 Màu Bạc Trắng\r\n\r\nVỏ khung của mẫu đồng hồ Cosmograph Daytona Steel 116500LN-0001 được làm từ thép không gỉ có kích thước 40mm, chịu nước ở độ sâu 100m. Bộ vỏ khung Oyster được đánh bóng cẩn thận, giữ nét thể thao khỏe khoắn. Ở cạnh bên phải của vỏ khung đồng hồ chắc chắn là hai nút bấm chronograph cùng núm điều chỉnh thời gian đã được vặn kín. Hai nút bấm góc 2 và 4 giờ vận hành chức năng chronograph tại mặt số phụ góc 3 và 9 giờ trên mặt số chính. Còn núm vặn góc 3 giờ được dùng để điều chỉnh chức năng thời gian cả mẫu đồng hồ Daytona.\r\n\r\nĐồng Hồ Rolex Cosmograph Daytona Steel 116500LN-0001 Màu Bạc Trắng\r\n\r\nVới ba mặt số phụ xếp lần lượt tại góc 3, 6, 9 giờ, nhiều người đã gọi đây là thiết kế mặt số Panda bởi nhìn rất giống gương mặt của những chú gấu trúc. Rolex Cosmograph Daytona Steel 116500LN-0001 có mặt số trắng được đánh giá cao về mặt thẩm mỹ hơn hẳn.\r\n\r\nĐồng Hồ Rolex Cosmograph Daytona Steel 116500LN-0001 Màu Bạc Trắng\r\n\r\nỞ bên trong phiên bản này là bộ máy tự động 4130 - đây là bộ máy chronograph đầu được Rolex nghiên cứu, phát triển và lắp ráp, do đó khả năng về hiệu suất, sự chính xác,… đều được nâng lên. Rolex 116500LN-0001 có thể vận hành chính xác trong vòng 72 giờ đồng hồ và tần số hoạt động là 4Hz, cùng sai số hàng là -2/+2 giây.', 950000000.000, 'Thụy Sĩ', 'Rolex', 'dong-ho-rolex-cosmograph-daytona-steel-116500ln-0001-mau-bac-trang-669746e845890-17072024112200.webp', 'Còn hàng'),
('MH009', 'Đồng Hồ Nữ Rolex Oyster Perpetual 36mm Celebration Dial Automatic Chronometer 126000-0009 Màu Bạc Xanh', 'Đồng Hồ Nữ Rolex Oyster Perpetual 36mm Celebration Dial Automatic Chronometer 126000-0009 Màu Bạc Xanh là chiếc đồng hồ cao cấp được nhiều tín đồ thời trang yêu thích hiện nay. Sở hữu thiết kế hiện đại, cùng gam màu sang trọng Rolex 126000-0009 mang đến cho các cô gái vẻ đẹp thanh lịch, hiện đại và không kém phần năng động.\r\n\r\nĐồng Hồ Nữ Rolex Oyster Perpetual 36mm Celebration Dial Automatic Chronometer 126000-0009 Màu Bạc Xanh\r\n\r\nVề thương hiệu Rolex\r\nTính đến nửa đầu năm 2017 thì thương hiệu đồng hồ có giá tr.ị thế giới đó chính là Rolex – nhà sản xuất lừng danh đến từ Thụy Sĩ với nguồn gốc từ Anh Quốc. Với giá tr.ị thương hiệu ước tính là 8.053 tỷ USD, Rolex cũng là thương hiệu chỉ sản xuất đồng hồ đeo tay trong top 10 thương hiệu hàng sang trọng cao cấp thế giới.\r\n\r\nĐồng Hồ Nữ Rolex Oyster Perpetual 36mm Celebration Dial Automatic Chronometer 126000-0009 Màu Bạc Xanh\r\n\r\nBắt đầu nổi lên với phiên bản đồng hồ đeo tay ngăn nước và từng bước phát triển, khẳng định được vị trí và tên tuổi của thương hiệu cao cấp với sự đẳng cấp. Và logo hình vương miện 5 đỉnh ở trên sản phẩm đồng hồ đeo tay của Rolex chính là một biểu tượng rất nổi tiếng ở thị trường đồng hồ thế giới.\r\n\r\nThiết Kế Đồng Hồ Nữ Rolex Oyster Perpetual 36mm Celebration Dial Automatic Chronometer 126000-0009 Màu Bạc Xanh\r\nĐồng hồ Rolex Oyster Perpetual 36mm Celebration Dial Automatic Chronometer 126000-0009 thiết kế bộ vỏ và dây từ chất liệu thép không gỉ Oystersteel - loại hợp kim được sử dụng thông dụng trong ngành công nghệ cao, có độ kháng ăn mòn cực đại, lưu giữ được vẻ đẹp của sản phẩm theo thời gian.\r\n\r\nĐồng Hồ Nữ Rolex Oyster Perpetual 36mm Celebration Dial Automatic Chronometer 126000-0009 Màu Bạc Xanh\r\n\r\nVới kích thước 36mm, chiếc đồng hồ này phù hợp cho những cô nàng có cổ tay trung bình. Mặt số màu xanh dương và được tạo điểm nhấn với những họa tiết Celebration đa sắc màu trông thật độc đáo và lôi cuốn, phản chiếu ánh sáng một cách rực rỡ. Rolex Oyster 126000-0009 là sự kết hợp hoàn hảo giữa thiết kế đẳng cấp, chất lượng và tinh tế, thể hiện phần nào vẻ đẳng cấp và cá tính của chủ nhân.\r\n\r\nĐồng Hồ Nữ Rolex Oyster Perpetual 36mm Celebration Dial Automatic Chronometer 126000-0009 Màu Bạc Xanh\r\n\r\nRolex Oyster Perpetual 36mm Celebration Dial Automatic Chronometer 126000-0009 được chế tạo và phát triển độc quyền bởi chính Rolex với bộ máy Automatic (tự động), giúp chiếc đồng hồ của bạn luôn hoạt động hiệu quả và chính xác trong mọi điều kiện môi trường.', 500000000.000, 'Thụy Sĩ', 'Rolex', 'dong-ho-nu-rolex-oyster-perpetual-36mm-celebration-dial-automatic-chronometer-126000-0009-mau-bac-xanh-66d90f6814dd7-05092024085448.webp', 'Còn hàng'),
('MH010', 'Đồng Hồ Nam Rolex Daydate 228398TBR Màu Vàng Đen', 'Đồng Hồ Nam Rolex Daydate 228398TBR Màu Vàng Đen là chiếc đồng hồ cao cấp đến từ thương hiệu Rolex. Mẫu đồng hồ với gam màu và thiết kế đẹp mắt, Đồng hồ Rolex 228398TBR khiến cho những ai sở hữu nó đều như bước lên một đẳng cấp mới danh giá, lịch lãm hơn bao giờ.\r\n\r\nĐồng Hồ Nam Rolex Daydate 228398TBR Màu Vàng Đen\r\n\r\nVề thương hiệu Rolex\r\nTính đến nửa đầu năm 2017 thì thương hiệu đồng hồ nổi tiếng trên thế giới đó chính là Rolex – nhà sản xuất lừng danh đến từ Thụy Sĩ với nguồn gốc từ Anh Quốc. Với giá t.r.ị thương hiệu ước tính là 8.053 tỷ USD, Rolex cũng là thương hiệu sản xuất đồng hồ đeo tay trong top 10 thương hiệu hàng sang trọng trên thế giới.\r\n\r\nĐồng Hồ Nam Rolex Daydate 228398TBR Màu Vàng Đen\r\n\r\nBắt đầu nổi lên với phiên bản đồng hồ đeo tay kháng nước và từng bước phát triển, khẳng định được vị trí và tên tuổi của thương hiệu với phong cách lịch lãm. Và logo hình vương miện 5 đỉnh ở trên sản phẩm đồng hồ đeo tay của Rolex chính là một biểu tượng rất nổi tiếng ở thị trường đồng hồ thế giới.\r\n\r\nThiết Kế Đồng Hồ Nam Rolex Daydate 228398TBR Màu Vàng Đen\r\nĐồng hồ Rolex Daydate 228398TBR có đường kính mặt số 40 mm không quá lớn cũng không quá nhỏ giúp các quý ông tự tin hơn khi phối với phong cách thời trang lịch lãm và đầy tinh tế. Chiếc đồng hồ sử dụng bộ máy tự động có xuất xứ Thụy Sĩ, có thể vận hành trong thời gian dài, có độ bền và giá tr.ị sưu tầm cao.\r\n\r\nĐồng Hồ Nam Rolex Daydate 228398TBR Màu Vàng Đen\r\n\r\nChất liệu vỏ được chế tác từ vàng vàng 18k kết hợp với kim cương lộng lẫy đã nhanh chóng giúp cho sản phẩm đến gần hơn với quý ông Việt. Chất liệu kính làm từ Sapphire đem lại khả năng chịu lực và hạn chể trầy xước ấn tượng.\r\n\r\nĐồng Hồ Nam Rolex Daydate 228398TBR Màu Vàng Đen\r\n\r\nMặt số Rolex Daydate 228398TBR được bao phủ bởi một màu đen huyền bí và sang trọng, thể hiện cho tính thể thao và tương phản với màu vàng của chất liệu cao cấp tạo nên một hiệu ứng thẩm mỹ khiến tất cả các quý ông phải “xiêu lòng”.', 270000000.000, 'Thụy Sĩ', 'Rolex', 'dong-ho-nam-rolex-daydate-228398tbr-mau-vang-den-667a41d79cd9f-25062024110439.webp', 'Còn hàng'),
('MH011', 'Đồng Hồ Nam Rolex Yacht Master 116655 Oyster 40mm Màu Đen', 'Đồng Hồ Nam Rolex Yacht Master 116655 Oyster 40mm Màu Đen là chiếc đồng hồ cao cấp đến từ thương hiệu Rolex. Model Rolex Master 116655 vô cùng thu hút sự chú ý, nhanh chóng trở thành mẫu được yêu thích trong bộ sưu tập dành riêng cho thủy thủ này.\r\n\r\nĐồng Hồ Nam Rolex Yacht Master 116655 Oyster 40mm Màu Đen\r\n\r\nVề thương hiệu Rolex\r\nTính đến nửa đầu năm 2017 thì thương hiệu đồng hồ nổi tiếng trên thế giới đó chính là Rolex – nhà sản xuất lừng danh đến từ Thụy Sĩ với nguồn gốc từ Anh Quốc. Với giá t.r.ị thương hiệu ước tính là 8.053 tỷ USD, Rolex cũng là thương hiệu sản xuất đồng hồ đeo tay trong top 10 thương hiệu hàng sang trọng trên thế giới.\r\n\r\nĐồng Hồ Nam Rolex Yacht Master 116655 Oyster 40mm Màu Đen\r\n\r\nBắt đầu nổi lên với phiên bản đồng hồ đeo tay kháng nước và từng bước phát triển, khẳng định được vị trí và tên tuổi của thương hiệu với phong cách lịch lãm. Và logo hình vương miện 5 đỉnh ở trên sản phẩm đồng hồ đeo tay của Rolex chính là một biểu tượng rất nổi tiếng ở thị trường đồng hồ thế giới.\r\n\r\nThiết Kế Đồng Hồ Nam Rolex Yacht Master 116655 Oyster 40mm Màu Đen\r\nĐồng hồ Rolex Yacht Master 116655 Oyster 40mm sở hữu bộ vỏ khung dáng Oyster quen thuộc, dưới chất liệu vàng hồng Everose có tính thẩm mỹ và hạn chế sự ăn mòn cao. Để giúp các thủy thủ tính toán thời gian thả phao, nhà sản xuất đã trang bị vòng bezel có tính năng xoay hai chiều cùng những khía chia cố định. Dưới chất liệu vàng hồng kết hợp bề mặt bằng gốm Cerachrom đen, vành bezel hiện lên vô cùng chắc chắn và dễ nhìn bởi những cọc số được làm nổi cùng bề mặt nhẵn bóng.\r\n\r\nĐồng Hồ Nam Rolex Yacht Master 116655 Oyster 40mm Màu Đen\r\n\r\nBên trong bộ vỏ khung là mặt số đen mang đậm thiết kể cổ điển của cỗ máy thời gian Yacht-Master 40mm. Mặt số xuất hiện trong tông màu đen huyền bí, đồng thời được xử lý mờ bề mặt, nên đem lại cái nhìn của một thời xưa cũ. Hiện nổi bật bên trên là hệ thống cọc giờ hình học, đem lại một chút gì đó hiện đại hơn với việc được phủ kín chất phát quang Chromalight thế hệ mới, thách thức cả bóng đêm.\r\n\r\nĐồng Hồ Nam Rolex Yacht Master 116655 Oyster 40mm Màu Đen\r\n\r\nHoàn thiện cho mặt số và sự vận hành trơn chu của bộ kim dáng Mecerdes nhà Rolex kích thước lớn, cùng ô cửa báo ngày tại góc 3 giờ. Bên trên mặt số, cũng góc 3 giờ là ô kính Cyclops, có khả năng phóng đại ngày một cách chân thực và rõ nét, một trong những đặc điểm nhận dạng của dòng đồng hồ Rolex.', 720000000.000, 'Thụy Sĩ', 'Rolex', 'dong-ho-nam-rolex-yacht-master-116655-oyster-40mm-mau-den-6656def415a78-29052024145324.webp', 'Còn hàng'),
('MH012', 'Đồng Hồ Rolex Daytona 116598SACO Leopard Họa Tiết Da Báo', 'Đồng Hồ Rolex Daytona 116598SACO Leopard Họa Tiết Da Báo là chiếc đồng hồ dành cho cả nam và nữ đến từ thương hiệu Rolex. Mẫu đồng hồ với thiết kế ấn tượng mang đến vẻ đẹp sang trọng dành cho người đeo khi đứng trước đám đông.\r\n\r\nĐồng Hồ Rolex Daytona 116598SACO Leopard Họa Tiết Da Báo\r\n\r\nVề thương hiệu Rolex\r\nTính đến nửa đầu năm 2017 thì thương hiệu đồng hồ nổi tiếng trên thế giới đó chính là Rolex – nhà sản xuất lừng danh đến từ Thụy Sĩ với nguồn gốc từ Anh Quốc. Với giá t.r.ị thương hiệu ước tính là 8.053 tỷ USD, Rolex cũng là thương hiệu sản xuất đồng hồ đeo tay trong top 10 thương hiệu hàng sang trọng trên thế giới.\r\n\r\nĐồng Hồ Rolex Daytona 116598SACO Leopard Họa Tiết Da Báo\r\n\r\nBắt đầu nổi lên với phiên bản đồng hồ đeo tay kháng nước và từng bước phát triển, khẳng định được vị trí và tên tuổi của thương hiệu với phong cách lịch lãm. Và logo hình vương miện 5 đỉnh ở trên sản phẩm đồng hồ đeo tay của Rolex chính là một biểu tượng rất nổi tiếng ở thị trường đồng hồ thế giới.\r\n\r\nThiết Kế Đồng Hồ Rolex Daytona 116598SACO Leopard Họa Tiết Da Báo\r\nĐồng hồ Rolex Daytona 116598SACO Leopard nổi bật với những viên đá sapphire màu cam lộng lẫy được nạm trên vành bezel bằng vàng vàng 18k. Cả bộ vỏ đồng hồ đều là được làm từ vàng và theo đó có 36 viên sapphire màu cam trên vành bezel, 45 viên kim cương đẳng cấp xuất hiện trên càng nối dây.\r\n\r\nĐồng Hồ Rolex Daytona 116598SACO Leopard Họa Tiết Da Báo\r\n\r\nKhông khó để nhận ra, mặt số của mẫu đồng hồ Rolex Daytona 116598SACO Leopard lấy chi tiết da báo làm nguồn cảm hứng. Ba mặt số phụ nằm cân đối hiện lên với gam màu vàng vàng và theo đó ở khu vực xung quanh la lớp nền vân da báo sống động xung quanh. Không cần phải chối cãi khi khẳng định Rolex 116598SACO là một chiếc đồng hồ sẽ khó có thể quên được nếu được nhìn thấy một lần.\r\n\r\nĐồng Hồ Rolex Daytona 116598SACO Leopard Họa Tiết Da Báo\r\n\r\nDù được nạm thêm đá quý cùng những chi tiết trang trí mặt số khác biệt, mẫu đồng hồ Rolex Daytona 116598SACO Leopard vẫn giữ được tính chính xác, tính kháng nước. Bên dưới mặt số là bộ máy chronograph vận hành tự động 4130 được vận hành trong vòng 72 giờ đồng hồ.', 4500000000.000, 'Thụy Sĩ', 'Rolex', 'dong-ho-rolex-daytona-116598saco-leopard-hoa-tiet-da-bao-66359ddf348e5-04052024093055.webp', 'Còn hàng'),
('MH013', 'Đồng Hồ Nam Hublot Classic Fusion Titanium 42mm 511.NX.7071.LR Màu Xám (Brand New Fresh Date T4/2025)', 'Đồng Hồ Nam Hublot Classic Fusion Titanium 42mm 511.NX.7071.LR Màu Xám là chiếc đồng hồ cao cấp đến từ thương hiệu Hublot nổi tiếng. Đồng hồ Hublot 511.NX.7071.LR thiết kế hiện đại khi đeo lên tay sẽ vô cùng sang trọng và đẳng cấp.\r\n\r\nVề thương hiệu Hublot nổi tiếng\r\nĐồng hồ Hublot là một tác phẩm nghệ thuật thực thụ khi kết hợp những kinh nghiệm chế tác của Thụy Sĩ và phong cách của Italia. Điều này giúp cho thương hiệu ngày càng đạt đến mức hoàn hảo về hình thức và chất lượng, \"mê hoặc\" hàng triệu khách hàng trên toàn thế giới. \r\n\r\nNgay từ đầu, nhà sáng lập Carlo Crocco đã rất chú trọng đến việc chế tác những chiếc đồng hồ \"Hàng Hải\", để các vị khách có thể sử dụng sản phẩm này ở bất cứ hoàn cảnh nào, dù đó là một bữa tiệc sang trọng hay một buổi tập luyện thể thao, lặn biển. Sản phẩm thông thường của Hublot có khả năng chịu áp lực nước ở độ sâu 165 feet, phiên bản lặn biển là 1000 feet.\r\n\r\nĐồng Hồ Nam Hublot Classic Fusion Titanium 42mm 511.NX.7071.LR Màu Xám (Brand New Fresh Date T4/2025)\r\n\r\nMột điểm đặc biệt khác của Hublot là sự xuất hiện của những viên đá quý sang trọng, lịch lãm, tạo nên một diện mạo vô cùng xa hoa cho những chiếc đồng hồ được sản xuất với số lượng hữu hạn. \r\n\r\nThương hiệu đồng hồ Hublot luôn gắn liền với hình ảnh của những người nổi tiếng trên thế giới và luôn là niềm \"ao ước\" của hàng triệu người dùng.\r\n\r\nThiết kế Đồng Hồ Nam Hublot Classic Fusion Titanium 42mm 511.NX.7071.LR Màu Xám\r\nĐồng Hồ Nam Hublot Classic Fusion Titanium 42mm 511.NX.7071.LR Màu Xám là một trong những phiên bản nổi bật và được giới trẻ yêu thích. Vỏ đồng hồ được hoàn thiện từ chất liệu titan. Thiết kế dây đeo bằng da cao cấp, mang đến sự thoải mái khi đeo trên tay.\r\n\r\nĐồng Hồ Nam Hublot Classic Fusion Titanium 42mm 511.NX.7071.LR Màu Xám (Brand New Fresh Date T4/2025)\r\n\r\nĐồng hồ có đường kính mặt là 42mm, bộ máy tự động HUB1112 có độ chính xác cao.\r\n\r\nĐồng Hồ Nam Hublot Classic Fusion Titanium 42mm 511.NX.7071.LR Màu Xám (Brand New Fresh Date T4/2025)\r\n\r\nĐồng Hồ Hublot Classic Fusion Titanium 42mm 511.NX.7071.LR dễ dàng kết hợp với phụ kiện và trang phục để trở nên nổi bật hàng ngày cũng như các sự kiện, bữa tiệc, là một siêu phẩm mang lại vẻ sang trọng đầy lịch lãm và cho người dùng trải nghiệm hoàn hảo.', 200000000.000, 'Thụy Sĩ', 'Hublot', 'dong-ho-nam-hublot-classic-fusion-titanium-42mm-511-nx-7071-lr-mau-xam-brand-new-fresh-date-t4-2025-d-pt-67f6447508dae-09042025165709.webp', 'Còn hàng'),
('MH014', 'Đồng Hồ Nữ Hublot Classic Fusion King Gold Diamond Blue Màu Xanh Dương', 'Đồng Hồ Nữ Hublot Classic Fusion King Gold Diamond Blue Màu Xanh Dương là sản phẩm đến từ thương hiệu đồng hồ Hublot nổi tiếng. Chất liệu cao cấp, tông màu sang trọng, ấn tượng. Mẫu sản phẩm này hứa hẹn sẽ là món phụ kiện hoàn hảo cho những cô nàng yêu thích phong cách thời thượng, sang trọng và hiện đại. \r\n\r\nĐồng Hồ Nữ Hublot Classic Fusion King Gold Diamond Blue Màu Xanh Dương\r\n\r\nVề thương hiệu Hublot\r\n\r\nHublot, một thương hiệu đồng hồ đến từ Thuỵ Sĩ, được ra mắt tại Baselworld vào năm 1980. Với nguồn gốc từ vùng đất phát triển ngành công nghiệp đồng hồ thế giới, Hublot nhanh chóng thu hút sự chú ý của giới mộ điệu. Đồng hồ Hublot được chế tạo bằng các vật liệu đắt đỏ và có phần quý hiếm, từ vàng đến titanium, gốm và cao su.\r\n\r\nĐồng Hồ Nữ Hublot Classic Fusion King Gold Diamond Blue Màu Xanh Dương\r\n\r\nSự kết hợp giữa truyền thống và hiện đại trong thiết kế làm nên nét đẹp độc đáo của Hublot. Được xem là một biểu tượng thời trang lớn, Hublot không chỉ mang đến sự sang trọng mà còn kết hợp tính thể thao. Hublot đã được xếp vào danh sách những hãng đồng hồ cao cấp và đắt giá trên thế giới.\r\n\r\nThiết kế của Đồng Hồ Nữ Hublot Classic Fusion King Gold Diamond Blue Màu Xanh Dương  \r\n\r\nĐồng Hồ Nữ Hublot Classic Fusion King Gold Diamond Blue Màu Xanh Dương nằm trong bộ sưu tập của thương hiệu Hublot, được thiết kế vô cùng sang trọng, hiện đại với đường kính 33mm. Mặt kính làm bằng kính Sapphire chịu lực tốt, độ trong của kính giúp bạn nhìn rõ mặt số đồng hồ. Đặc biệt logo Hublot tạo điểm nhấn kết hợp với các viên kim cương lấp lánh được đính trên vành giúp cho tổng thể hài hòa và ấn tượng. \r\n\r\nĐồng Hồ Nữ Hublot Classic Fusion King Gold Diamond Blue Màu Xanh Dương\r\n\r\nĐồng hồ có độ cứng cao, tông màu xanh dương sang trọng, hiện đại, nữ tính giúp cho phong cách của bạn trở nên thời thượng hơn. Vỏ đồng hồ được làm bằng chất liệu vàng hồng 18K có độ cứng cáp, chịu nhiệt tốt, độ bền cao, không bị ăn mòn trong môi trường nước biển hoặc chất lỏng có tính axit như mồ hôi. Dây đồng hồ gồm phần lõi cao su bên trong giúp cân bằng đàn hồi cho đồng hồ, bao bọc bên ngoài là da cá sấu màu xanh đồng bộ với mặt số đồng hồ.\r\n\r\nĐồng Hồ Nữ Hublot Classic Fusion King Gold Diamond Blue Màu Xanh Dương\r\n\r\nĐồng Hồ Nữ Hublot Classic Fusion King Gold Diamond Blue Màu Xanh Dương được trang bị bộ máy Quartz, hệ số chịu nước 5 ATM phù hợp với các hoạt động hàng ngày khi rửa tay, đi mưa nhẹ. Đây là món phụ kiện vừa thời trang, sang trọng, quý phái, phù hợp nhiều kiểu trang phục và mọi dịp giúp tôn lên vẻ đẹp của các chị em.', 350000000.000, 'Thụy Sĩ', 'Hublot', 'dong-ho-nu-hublot-classic-fusion-king-gold-diamond-blue-mau-xanh-duong-6720622a424d6-29102024111850.webp', 'Còn hàng'),
('MH015', 'Đồng Hồ Hublot Big Bang Unico Yellow Sapphire Limited 42 441.JY.4909.RT Màu Vàng', 'Đồng Hồ Hublot Big Bang Unico Yellow Sapphire Limited 42 441.JY.4909.RT Màu Vàng là chiếc đồng hồ cao cấp đến từ thương hiệu Hublot nổi tiếng. Đây là phiên bản đồng hồ đã qua sử dụng nhưng với thiết kế hiện đại, cùng gam màu sang trọng Hublot 441.JY.4909.RT khi đeo lên tay sẽ vô cùng sang trọng và đẳng cấp.\r\n\r\nĐồng Hồ Hublot Big Bang Unico Yellow Sapphire Limited 42 441.JY.4909.RT Màu Vàng\r\n\r\nVề thương hiệu Hublot nổi tiếng\r\nĐồng hồ Hublot là một tác phẩm nghệ thuật thực thụ khi kết hợp những kinh nghiệm chế tác của Thụy Sĩ và phong cách của Italia. Điều này giúp cho thương hiệu ngày càng đạt đến mức hoàn hảo về hình thức và chất lượng, \"mê hoặc\" hàng triệu khách hàng trên toàn thế giới. \r\n\r\nNgay từ đầu, nhà sáng lập Carlo Crocco đã rất chú trọng đến việc chế tác những chiếc đồng hồ \"Hàng Hải\", để các vị khách có thể sử dụng sản phẩm này ở bất cứ hoàn cảnh nào, dù đó là một bữa tiệc sang trọng hay một buổi tập luyện thể thao, lặn biển. Sản phẩm thông thường của Hublot có khả năng chịu áp lực nước ở độ sâu 165 feet, phiên bản lặn biển là 1000 feet.\r\n\r\nĐồng Hồ Hublot Big Bang Unico Yellow Sapphire Limited 42 441.JY.4909.RT Màu Vàng\r\n\r\nMột điểm đặc biệt khác của Hublot là sự xuất hiện của những viên đá quý sang trọng, lịch lãm, tạo nên một diện mạo vô cùng xa hoa cho những chiếc đồng hồ được sản xuất với số lượng hữu hạn. \r\n\r\nThương hiệu đồng hồ Hublot luôn gắn liền với hình ảnh của những người nổi tiếng trên thế giới và luôn là niềm \"ao ước\" của hàng triệu người dùng.\r\n\r\nThiết Kế Đồng Hồ Hublot Big Bang Unico Yellow Sapphire Limited 42 441.JY.4909.RT Màu Vàng\r\nĐồng hồ Hublot Big Bang Unico Yellow Sapphire Limited 42 441.JY.4909.RT thừa hưởng thiết kế vỏ tròn của dòng Big Bang, và được trang trí bằng 6 vít chữ H trên khung. Kết cấu trong suốt độc đáo của vật liệu sapphire màu vàng làm cho cấu trúc cơ học của đồng hồ được thể hiện một cách độc đáo, mới lạ và thu hút.\r\n\r\nĐồng Hồ Hublot Big Bang Unico Yellow Sapphire Limited 42 441.JY.4909.RT Màu Vàng\r\n\r\nNgoài ra, Hublot 42 441.JY.4909.RT còn được trang bị chức năng chronograph, có các nút bấm giờ ở cả hai mặt của núm vặn, có thể được sử dụng để bắt đầu, dừng và đặt lại đồng hồ bấm giờ.\r\n\r\nĐồng Hồ Hublot Big Bang Unico Yellow Sapphire Limited 42 441.JY.4909.RT Màu Vàng\r\n\r\nThiết kế rỗng là một điểm nổi bật khác của chiếc đồng hồ Big Bang Unico 42 441.JY.4909.RT. Bạn có thể dễ dàng nhận thấy cấu trúc cơ học chính xác của bộ chuyển động. Ở vị trí 3 giờ và 9 giờ, một cửa sổ hiển thị ngày và một mặt số chức năng nhỏ được đặt lần lượt. Các vạch giờ và kim hiển thị màu vàng dưới ánh sáng rực rỡ, tạo nên phong cách tổng thể của đồng hồ. Khi màn đêm buông xuống, thang đo thời gian và kim được phủ dạ quang màu xanh lá cây sẽ phát sáng, thuận tiện cho người đeo trong việc đọc giờ.\r\n\r\nĐồng Hồ Hublot Big Bang Unico Yellow Sapphire Limited 42 441.JY.4909.RT Màu Vàng\r\n\r\nDây đeo của đồng hồ Hublot  441.JY.4909.RT Yellow có cấu trúc và lớp lót màu vàng trong suốt kết hợp với chốt gấp titan phủ màu đen, mang phong cách thể thao và sinh động hơn. Bộ máy do UNICO 2 tự sản xuất này cũng có chức năng bấm giờ flyback, đồng thời sử dụng công nghệ vật liệu silicon tiên tiến. Bộ máy này dao động ở tần số 28.800vph, 43 chân kính với khả năng dự trữ năng lượng trong 72 giờ.', 1550000000.000, 'Thụy Sĩ', 'Hublot', 'dong-ho-hublot-big-bang-unico-yellow-sapphire-limited-42-441-jy-4909-rt-mau-vang-667a3b749208e-25062024103724.webp', 'Còn hàng'),
('MH016', 'Đồng Hồ Nam Hublot Spirit Of Bigbang Sang Bleu Sapphire 42mm Limited 100pcs Màu Trong Suốt', 'Đồng Hồ Nam Hublot Spirit Of Bigbang Sang Bleu Sapphire 42mm Limited 100pcs Màu Trong Suốt là chiếc đồng hồ cao cấp được nhiều tín đồ săn đón hiện nay. Sở hữu thiết kế hiện đại, cùng gam màu sang trọng, mẫu đồng hồ này sẽ vô cùng đẳng cấp và sang trọng khi đeo lên tay.\r\n\r\nĐồng Hồ Nam Hublot Spirit Of Bigbang Sang Bleu Sapphire 42mm Limited 100pcs Màu Trong Suốt\r\n\r\nVề thương hiệu Hublot nổi tiếng\r\nĐồng hồ Hublot là một tác phẩm nghệ thuật thực thụ khi kết hợp những kinh nghiệm chế tác của Thụy Sỹ và phong cách của Italia. Điều này giúp cho thương hiệu ngày càng đạt đến mức hoàn hảo về hình thức và chất lượng, \"mê hoặc\" hàng triệu khách hàng trên toàn thế giới. \r\n\r\nNgay từ đầu, nhà sáng lập Carlo Crocco đã rất chú trọng đến việc chế tác những chiếc đồng hồ \"Hàng Hải\", để các vị khách có thể sử dụng sản phẩm này ở bất cứ hoàn cảnh nào, dù đó là một bữa tiệc sang trọng hay một buổi tập luyện thể thao, lặn biển. Sản phẩm thông thường của Hublot có khả năng chịu áp lực nước ở độ sâu 165 feet, phiên bản lặn biển là 1000 feet.\r\n\r\nĐồng Hồ Nam Hublot Spirit Of Bigbang Sang Bleu Sapphire 42mm Limited 100pcs Màu Trong Suốt\r\n\r\nMột điểm đặc biệt khác của Hublot là sự xuất hiện của những viên đá quý sang trọng, lịch lãm, tạo nên một diện mạo vô cùng xa hoa cho những chiếc đồng hồ được sản xuất với số lượng hữu hạn. \r\n\r\nThương hiệu đồng hồ Hublot luôn gắn liền với hình ảnh của những người nổi tiếng trên thế giới và luôn là niềm \"ao ước\" của hàng triệu người dùng.\r\n\r\nThiết Kế Đồng Hồ Nam Hublot Spirit Of Bigbang Sang Bleu Sapphire 42mm Limited 100pcs Màu Trong Suốt\r\nĐồng hồ Hublot Spirit Of Bigbang Sang Bleu Sapphire 42mm Limited 100pcs là mẫu đồng hồ thuộc bộ sưu tập Spirit of Big Bang, nó mang một cơn gió mới đến với các sản phẩm của Hublot với phong cách trẻ trung, lịch lãm và nam tính nhưng vẫn toát lên sự sang trọng, đẳng cấp.\r\n\r\nĐồng Hồ Nam Hublot Spirit Of Bigbang Sang Bleu Sapphire 42mm Limited 100pcs Màu Trong Suốt\r\n\r\nVề thiết kế bên ngoài, Hublot Spirit Of Bigbang Sang Bleu Sapphire sở hữu lớp vỏ tonneau hình thùng vô cùng khỏe khoắn gợi lên những cảm giác về những chiếc đồng hồ Richard Mille. Mặt số bên trong được thiết kế vô cùng tỉ mỉ, chi tiết và độc đáo. giúp cho mẫu đồng hồ thêm phần khỏe khoắn, năng động.\r\n\r\nĐồng Hồ Nam Hublot Spirit Of Bigbang Sang Bleu Sapphire 42mm Limited 100pcs Màu Trong Suốt\r\n\r\nPhần vỏ sử dụng chất liệu phiên bản pha lê Sapphire trong suốt cực kì ấn tượng, vô cùng chắc chắn và bền bỉ theo thời gian. Dây đeo cao su mềm mại và có thể kháng nước ở độ sâu 100m nên người dùng có thể đeo khi đang rửa tay, đi mưa.', 3300000000.000, 'Thụy Sĩ', 'Hublot', 'dong-ho-nam-hublot-spirit-of-bigbang-sang-bleu-sapphire-42mm-limited-100pcs-mau-trong-suot-6667ecde3d260-11062024132118.webp', 'Còn hàng'),
('MH017', 'Đồng Hồ Nữ Hublot Hublot OneClick Rainbow Steel 39mm Phối Màu', 'Đồng Hồ Nữ Hublot Hublot OneClick Rainbow Steel 39mm Phối Màu là chiếc đồng hồ thời trang được thiết kế vô cùng bắt mắt và hiện đại đến từ thương hiệu Hublot nổi tiếng. Chiếc đồng hồ này mang đến cho người dùng vẻ đẹp nữ tính, sang trọng. \r\n\r\nĐồng Hồ Nữ Hublot Hublot OneClick Rainbow Steel 39mm Phối Màu\r\n\r\nVề thương hiệu Hublot nổi tiếng\r\nĐồng hồ Hublot là một tác phẩm nghệ thuật thực thụ khi kết hợp những kinh nghiệm chế tác của Thụy Sĩ và phong cách của Italia. Điều này giúp cho thương hiệu ngày càng đạt đến mức hoàn hảo về hình thức và chất lượng, \"mê hoặc\" hàng triệu khách hàng trên toàn thế giới. \r\n\r\nNgay từ đầu, nhà sáng lập Carlo Crocco đã rất chú trọng đến việc chế tác những chiếc đồng hồ \"Hàng Hải\", để các vị khách có thể sử dụng sản phẩm này ở bất cứ hoàn cảnh nào, dù đó là một bữa tiệc sang trọng hay một buổi tập luyện thể thao, lặn biển. Sản phẩm thông thường của Hublot có khả năng chịu áp lực nước ở độ sâu 165 feet, phiên bản lặn biển là 1000 feet.\r\n\r\nĐồng Hồ Nữ Hublot Hublot OneClick Rainbow Steel 39mm Phối Màu\r\n\r\nMột điểm đặc biệt khác của Hublot là sự xuất hiện của những viên đá quý sang trọng, lịch lãm, tạo nên một diện mạo vô cùng xa hoa cho những chiếc đồng hồ được sản xuất với số lượng hữu hạn. \r\n\r\nThương hiệu đồng hồ Hublot luôn gắn liền với hình ảnh của những người nổi tiếng trên thế giới và luôn là niềm \"ao ước\" của hàng triệu người dùng.\r\n\r\nThiết Kế Đồng Hồ Nữ Hublot Hublot OneClick Rainbow Steel 39mm Phối Màu\r\nĐồng hồ Hublot Hublot OneClick Rainbow Steel 39m có đường kính mặt số 39mm và được phủ kín 307 viên đá quý tạo nên vẻ đẹp trẻ trung và đầy nữ tính. Bộ vỏ đồng hồ chế tác từ vàng trắng 18k cao cấp vô cùng sang trọng, phần vành được đính 48 viên sapphire màu cắt baguette, thạch anh tím, hồng ngọc, topazes và Tsavorites.\r\n\r\nĐồng Hồ Nữ Hublot Hublot OneClick Rainbow Steel 39mm Phối Màu\r\n\r\nChiếc đồng hồ Hublot Hublot OneClick Rainbow Steel trang bị bộ máy tự động Caliber HUB 1710, dao động ở tần số 28.800 vph được cấu tạo từ 185 bộ phận, 27 chân kính cho mức dự trữ năng lượng lên đến 50 giờ đồng hồ.\r\n\r\nĐồng Hồ Nữ Hublot Hublot OneClick Rainbow Steel 39mm Phối Màu\r\n\r\nDây đồng hồ làm từ chất liệu da có độ bền bỉ và không dễ bị ăn mòn nhưng vẫn tạo sự sang trọng cho người đeo. Tất cả các chi tiết mang đến cái nhìn đối xứng hài hòa mà vẫn đậm chất thể thao cho đồng hồ Hublot Hublot OneClick Rainbow Steel khác biệt mà vẫn đem tới sự sành điệu và phong cách trẻ trung cho người đeo.', 1390000000.000, 'Thụy Sĩ', 'Hublot', 'dong-ho-nu-hublot-hublot-oneclick-rainbow-steel-39mm-phoi-mau-662b5afb4a5f4-26042024144251.webp', 'Còn hàng'),
('MH018', 'Đồng Hồ Hublot Bigbang 41mm Gold Linen Limited 200PCS 341.XG.1280.LR.1229 Màu Xanh Đen', 'Đồng Hồ Hublot Bigbang 41mm Gold Linen Limited 200PCS 341.XG.1280.LR.1229 Màu Xanh Lá là chiếc đồng hồ cao cấp đến từ thương hiệu Hublot nổi tiếng. Đây là phiên bản đồng hồ đã qua sử dụng nhưng với thiết kế hiện đại, cùng gam màu sang trọng Hublot 341.XG.1280.LR.1229 khi đeo lên tay sẽ vô cùng sang trọng và đẳng cấp.\r\n\r\nĐồng Hồ Hublot Bigbang 41mm Gold Linen Limited 200PCS 341.XG.1280.LR.1229 Màu Xanh Đen\r\n\r\nVề thương hiệu Hublot nổi tiếng\r\nĐồng hồ Hublot là một tác phẩm nghệ thuật thực thụ khi kết hợp những kinh nghiệm chế tác của Thụy Sĩ và phong cách của Italia. Điều này giúp cho thương hiệu ngày càng đạt đến mức hoàn hảo về hình thức và chất lượng, \"mê hoặc\" hàng triệu khách hàng trên toàn thế giới. \r\n\r\nNgay từ đầu, nhà sáng lập Carlo Crocco đã rất chú trọng đến việc chế tác những chiếc đồng hồ \"Hàng Hải\", để các vị khách có thể sử dụng sản phẩm này ở bất cứ hoàn cảnh nào, dù đó là một bữa tiệc sang trọng hay một buổi tập luyện thể thao, lặn biển. Sản phẩm thông thường của Hublot có khả năng chịu áp lực nước ở độ sâu 165 feet, phiên bản lặn biển là 1000 feet.\r\n\r\nĐồng Hồ Hublot Bigbang 41mm Gold Linen Limited 200PCS 341.XG.1280.LR.1229 Màu Xanh Đen\r\n\r\nMột điểm đặc biệt khác của Hublot là sự xuất hiện của những viên đá quý sang trọng, lịch lãm, tạo nên một diện mạo vô cùng xa hoa cho những chiếc đồng hồ được sản xuất với số lượng hữu hạn. \r\n\r\nThương hiệu đồng hồ Hublot luôn gắn liền với hình ảnh của những người nổi tiếng trên thế giới và luôn là niềm \"ao ước\" của hàng triệu người dùng.\r\n\r\nThiết Kế Đồng Hồ Hublot Bigbang 41mm Gold Linen Limited 200PCS 341.XG.1280.LR.1229 Màu Xanh Lá\r\nĐồng hồ Hublot Bigbang 41mm Gold Linen Limited 200PCS 341.XG.1280.LR.1229 với phần mặt số được hoàn thiện đen nhám khá dịu mắt, giúp những cọc số kim cương trở nên nổi bật hơn. Tất nhiên, Hublot cũng không quên tạo điểm nhấn trên bộ mặt của chiếc đồng hồ bằng 3 mặt số phụ được hoàn thiện chải tia khác biệt. Với bộ kim vàng thiết kế Skeleton, người dùng có thể nhìn rõ hơn những cây kim hay cọc số phía sau.\r\n\r\nĐồng Hồ Hublot Bigbang 41mm Gold Linen Limited 200PCS 341.XG.1280.LR.1229 Màu Xanh Đen\r\n\r\nThiết kế kinh điển của Hublot Big Bang vẫn ở nguyên đó: một vành bezel làm từ vàng King Gold với 6 đinh ốc hình chữ H, đi kèm với đó là lớp đệm màu đen bằng Ceramic. Bên cạnh vàng, chất liệu kim cương quý giá cũng được sử dụng trên chiếc đồng hồ này: 36 viên kim cương Brilliant Cut trên vành bezel và 8 cọc số được đính kinh cương.\r\n\r\nĐồng Hồ Hublot Bigbang 41mm Gold Linen Limited 200PCS 341.XG.1280.LR.1229 Màu Xanh Đen\r\n\r\nĐặc biệt hơn, chiếc BigBang 200PCS 341.XG.1280.LR.1229 sử dụng thêm những tấm vàng nguyên chất để kết hợp thêm với hợp chất trên, tạo nên một thiết kế rất đặc biệt vừa có độ cứng cao, vừa có độ bóng bắt mắt của vàng. Chiếc đồng hồ này sở hữu kích thước 41mm rất phổ biến và phù hợp với cổ tay mọi người.\r\n\r\nĐồng Hồ Hublot Bigbang 41mm Gold Linen Limited 200PCS 341.XG.1280.LR.1229 Màu Xanh Đen\r\n\r\nBộ dây của chiếc Big Bang Gold Linen vẫn sử dụng chất liệu cao su phủ da màu xanh lá bắt mắt. Phải nói rằng ít có thương hiệu nào làm được dây cao su phủ da chất lượng tốt như Hublot, với độ thoải mái có thể nói là hoàn hảo. Đi kèm với bộ dây cao su là khóa gập làm từ thép không gỉ mạ PVD , đem lại tổng thể hiện đại cho chiếc đồng hồ.', 490000000.000, 'Thụy Sĩ', 'Hublot', 'dong-ho-hublot-bigbang-41mm-gold-linen-limited-200pcs-341-xg-1280-lr-1229-mau-xanh-den-662b322e19ee9-26042024114846.webp', 'Còn hàng');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tbreview`
--

CREATE TABLE `tbreview` (
  `id` int(11) NOT NULL,
  `mahang` varchar(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `content` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tbreview`
--

INSERT INTO `tbreview` (`id`, `mahang`, `username`, `rating`, `content`, `created_at`) VALUES
(1, 'MH061', 'nv1', 4, 'đm phúc răm', '2025-10-30 10:20:12');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tbreview_reply`
--

CREATE TABLE `tbreview_reply` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tbrole`
--

CREATE TABLE `tbrole` (
  `role` varchar(20) NOT NULL,
  `description` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tbrole`
--

INSERT INTO `tbrole` (`role`, `description`) VALUES
('Admin', 'Quản trị hệ thống'),
('Member', 'Thành viên thông thường');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tbtheodoi`
--

CREATE TABLE `tbtheodoi` (
  `id` int(11) NOT NULL,
  `madonhang` varchar(20) NOT NULL,
  `thoigian` datetime NOT NULL,
  `trangthai_ghtk` varchar(100) NOT NULL,
  `mo_ta` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tbuser`
--

CREATE TABLE `tbuser` (
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tbuser`
--

INSERT INTO `tbuser` (`username`, `password`, `active`) VALUES
('111', '$2y$10$pobnK9I87iIknI.pbYjVHueLLwG8KeH4yeG.a9nR.qU0dx0Xm7Pym', 1),
('admin@gmail.com', '$2y$10$bKjX4wR/qmC/pd4bLK1sGupPGuwmjQJZbLd4YKYgCD.tVxyaGo6/y', 1),
('nv1', '$2y$10$gBo60h3dTPXBdx3rcOWrZ.valgmP45uJ/2YIrnCLofN04IUlhdEhi', 1),
('nv2', '$2y$10$We2S3WqQFfBIwKtV5zVxu.wLJbdT8.Nx6fn8Fut.92jn3SB.rH8wq', 1),
('nv3', '$2y$10$UeXifrw9saLfe2CKlUjqIeyiey2FiyGJfCCnrFQ7tK1A1AsQnyWdW', 1),
('nv4', '$2y$10$9Wl00NXCDjggYXhEyuUw4eOGxKKK/ISLu1ftkjpp5MwxyntQPGN8C', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tbuserinrole`
--

CREATE TABLE `tbuserinrole` (
  `username` varchar(50) NOT NULL,
  `role` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tbuserinrole`
--

INSERT INTO `tbuserinrole` (`username`, `role`) VALUES
('111', 'Member'),
('admin@gmail.com', 'Admin'),
('nv1', 'Member'),
('nv2', 'Member'),
('nv3', 'Member'),
('nv4', 'Member');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `training_phrases`
--

CREATE TABLE `training_phrases` (
  `id` int(11) NOT NULL,
  `intent_id` int(11) NOT NULL,
  `phrase_text` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `training_phrases`
--

INSERT INTO `training_phrases` (`id`, `intent_id`, `phrase_text`) VALUES
(1, 1, 'chế độ bảo hành'),
(2, 1, 'đồng hồ này bảo hành bao lâu'),
(3, 2, 'xin chào'),
(4, 2, 'hello shop'),
(5, 3, 'giao hàng mất bao lâu'),
(6, 3, 'phí ship thế nào'),
(9, 4, 'Đồng hồ này giá bao nhiêu?'),
(10, 4, 'Giá của MH001 là bao nhiêu?'),
(11, 4, 'Tôi muốn hỏi về giá sản phẩm'),
(12, 4, 'Xin báo giá chiếc đồng hồ Patek Philippe'),
(13, 4, 'Bao nhiêu tiền?'),
(14, 1, 'Điều kiện bảo hành sản phẩm là gì?'),
(15, 1, 'Chính sách bảo hành như thế nào?'),
(16, 1, 'Lỗi nhà sản xuất có được đổi không?'),
(17, 3, 'Bao lâu thì tôi nhận được hàng?'),
(18, 3, 'Phí ship là bao nhiêu?'),
(19, 3, 'Có giao hàng miễn phí không?'),
(20, 1, 'Thời gian bảo hành cho đồng hồ là bao lâu?'),
(21, 1, 'Tôi muốn biết chính sách bảo hành'),
(22, 1, 'Làm thế nào để được bảo hành?'),
(23, 1, 'Đồng hồ bị lỗi kỹ thuật thì sao?'),
(24, 1, 'Điều kiện để đổi sản phẩm lỗi?'),
(25, 1, 'Sản phẩm có được đổi trả không?'),
(26, 1, 'Tôi có thể sửa chữa ở đâu?'),
(27, 1, 'Bảo hành có tính phí không?'),
(28, 2, 'Hi'),
(29, 2, 'Alo'),
(30, 2, 'Bạn là ai?'),
(31, 2, 'Trò chuyện với tôi'),
(32, 2, 'Tôi muốn hỏi'),
(33, 2, 'Cần tư vấn'),
(34, 2, 'Chào chatbot'),
(35, 2, 'Xin chào'),
(36, 3, 'Tôi ở Hà Nội thì bao giờ nhận được?'),
(37, 3, 'Phí giao hàng ra sao?'),
(38, 3, 'Mua hàng có mất phí ship không?'),
(39, 3, 'Muốn giao hàng nhanh thì làm sao?'),
(40, 3, 'Thời gian nhận hàng là bao lâu?'),
(41, 3, 'Có giao hàng tận nhà không?'),
(42, 3, 'Shop có ship COD không?'),
(43, 3, 'Chính sách giao hàng của shop là gì?'),
(44, 4, 'Giá chiếc Rolex này là bao nhiêu?'),
(45, 4, 'Đồng hồ có bán trả góp không?'),
(46, 4, 'Cho tôi xem đồng hồ dưới 5 triệu'),
(47, 4, 'Bảng giá sản phẩm'),
(48, 4, 'Mức giá bán ra'),
(49, 4, 'Chiếc này bao nhiêu tiền?'),
(50, 4, 'Tôi muốn hỏi giá sản phẩm này'),
(51, 5, 'Thông số kỹ thuật của đồng hồ này là gì?'),
(52, 5, 'Đồng hồ có chống nước không?'),
(53, 5, 'Kích cỡ mặt đồng hồ này bao nhiêu?'),
(54, 5, 'Sản phẩm này là máy cơ hay máy pin?'),
(55, 5, 'Dây đeo làm bằng chất liệu gì?'),
(56, 5, 'Mô tả chi tiết sản phẩm MH005'),
(57, 5, 'Thông tin về đồng hồ Seiko 5'),
(58, 5, 'Chất liệu của chiếc này?'),
(59, 6, 'Tôi có thể thanh toán bằng những cách nào?'),
(60, 6, 'Shop có nhận thanh toán bằng thẻ Visa không?'),
(61, 6, 'Có thể chuyển khoản ngân hàng không?'),
(62, 6, 'Hình thức thanh toán khi nhận hàng'),
(63, 6, 'Có trả góp không?'),
(64, 6, 'Tôi muốn mua trả góp'),
(65, 6, 'Thanh toán trực tuyến được không?'),
(66, 7, 'Cửa hàng ở đâu?'),
(67, 7, 'Địa chỉ chi nhánh gần nhất'),
(68, 7, 'DBĐồng Hồ có những cơ sở nào?'),
(69, 7, 'Giờ mở cửa là mấy giờ?'),
(70, 7, 'Cách liên hệ với cửa hàng'),
(71, 7, 'Số điện thoại hotline'),
(72, 7, 'Tôi muốn đến xem trực tiếp'),
(75, 8, 'hh'),
(76, 9, 'Tư vấn giúp mình'),
(77, 9, 'Mình muốn mua đồng hồ này'),
(78, 9, 'Cách thức mua hàng thế nào'),
(79, 9, 'Có nhân viên tư vấn không'),
(80, 9, 'Gọi lại cho tôi'),
(81, 9, 'Tôi cần hỗ trợ gấp');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_identifier` (`user_identifier`),
  ADD KEY `status` (`status`);

--
-- Chỉ mục cho bảng `customer_leads`
--
ALTER TABLE `customer_leads`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `intents`
--
ALTER TABLE `intents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Chỉ mục cho bảng `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`);

--
-- Chỉ mục cho bảng `responses`
--
ALTER TABLE `responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `intent_id` (`intent_id`);

--
-- Chỉ mục cho bảng `tbchitietdonhang`
--
ALTER TABLE `tbchitietdonhang`
  ADD PRIMARY KEY (`machitiet`),
  ADD KEY `mahang` (`mahang`),
  ADD KEY `tbchitietdonhang_ibfk_1` (`madonhang`);

--
-- Chỉ mục cho bảng `tbdonhang`
--
ALTER TABLE `tbdonhang`
  ADD PRIMARY KEY (`madonhang`),
  ADD KEY `tbdonhang_ibfk_1` (`makhach`),
  ADD KEY `tbdonhang_ibfk_2` (`makhuyenmai`);

--
-- Chỉ mục cho bảng `tbgiohang_luu`
--
ALTER TABLE `tbgiohang_luu`
  ADD PRIMARY KEY (`makhach`,`mahang`);

--
-- Chỉ mục cho bảng `tbhinhanhchitiet`
--
ALTER TABLE `tbhinhanhchitiet`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mahang` (`mahang`);

--
-- Chỉ mục cho bảng `tbkhachhang`
--
ALTER TABLE `tbkhachhang`
  ADD PRIMARY KEY (`makhach`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Chỉ mục cho bảng `tbkhuyenmai`
--
ALTER TABLE `tbkhuyenmai`
  ADD PRIMARY KEY (`makhuyenmai`);

--
-- Chỉ mục cho bảng `tbmathang`
--
ALTER TABLE `tbmathang`
  ADD PRIMARY KEY (`mahang`);

--
-- Chỉ mục cho bảng `tbreview`
--
ALTER TABLE `tbreview`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mahang` (`mahang`),
  ADD KEY `username` (`username`);

--
-- Chỉ mục cho bảng `tbreview_reply`
--
ALTER TABLE `tbreview_reply`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `tbrole`
--
ALTER TABLE `tbrole`
  ADD PRIMARY KEY (`role`);

--
-- Chỉ mục cho bảng `tbtheodoi`
--
ALTER TABLE `tbtheodoi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `madonhang` (`madonhang`);

--
-- Chỉ mục cho bảng `tbuser`
--
ALTER TABLE `tbuser`
  ADD PRIMARY KEY (`username`);

--
-- Chỉ mục cho bảng `tbuserinrole`
--
ALTER TABLE `tbuserinrole`
  ADD PRIMARY KEY (`username`,`role`),
  ADD KEY `role` (`role`);

--
-- Chỉ mục cho bảng `training_phrases`
--
ALTER TABLE `training_phrases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `intent_id` (`intent_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `customer_leads`
--
ALTER TABLE `customer_leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `intents`
--
ALTER TABLE `intents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT cho bảng `responses`
--
ALTER TABLE `responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT cho bảng `tbhinhanhchitiet`
--
ALTER TABLE `tbhinhanhchitiet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=605;

--
-- AUTO_INCREMENT cho bảng `tbreview`
--
ALTER TABLE `tbreview`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `tbreview_reply`
--
ALTER TABLE `tbreview_reply`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `tbtheodoi`
--
ALTER TABLE `tbtheodoi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `training_phrases`
--
ALTER TABLE `training_phrases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `responses`
--
ALTER TABLE `responses`
  ADD CONSTRAINT `responses_ibfk_1` FOREIGN KEY (`intent_id`) REFERENCES `intents` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `tbchitietdonhang`
--
ALTER TABLE `tbchitietdonhang`
  ADD CONSTRAINT `fk_chitietdh_mathang` FOREIGN KEY (`mahang`) REFERENCES `tbmathang` (`mahang`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbchitietdonhang_ibfk_1` FOREIGN KEY (`madonhang`) REFERENCES `tbdonhang` (`madonhang`),
  ADD CONSTRAINT `tbchitietdonhang_ibfk_2` FOREIGN KEY (`mahang`) REFERENCES `tbmathang` (`mahang`);

--
-- Các ràng buộc cho bảng `tbdonhang`
--
ALTER TABLE `tbdonhang`
  ADD CONSTRAINT `fk_dh_khuyenmai` FOREIGN KEY (`makhuyenmai`) REFERENCES `tbkhuyenmai` (`makhuyenmai`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbdonhang_ibfk_1` FOREIGN KEY (`makhach`) REFERENCES `tbkhachhang` (`makhach`),
  ADD CONSTRAINT `tbdonhang_ibfk_2` FOREIGN KEY (`makhuyenmai`) REFERENCES `tbkhuyenmai` (`makhuyenmai`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `tbhinhanhchitiet`
--
ALTER TABLE `tbhinhanhchitiet`
  ADD CONSTRAINT `tbhinhanhchitiet_ibfk_1` FOREIGN KEY (`mahang`) REFERENCES `tbmathang` (`mahang`);

--
-- Các ràng buộc cho bảng `tbkhachhang`
--
ALTER TABLE `tbkhachhang`
  ADD CONSTRAINT `tbkhachhang_ibfk_1` FOREIGN KEY (`username`) REFERENCES `tbuser` (`username`);

--
-- Các ràng buộc cho bảng `tbtheodoi`
--
ALTER TABLE `tbtheodoi`
  ADD CONSTRAINT `tbtheodoi_ibfk_1` FOREIGN KEY (`madonhang`) REFERENCES `tbdonhang` (`madonhang`);

--
-- Các ràng buộc cho bảng `tbuserinrole`
--
ALTER TABLE `tbuserinrole`
  ADD CONSTRAINT `tbuserinrole_ibfk_1` FOREIGN KEY (`username`) REFERENCES `tbuser` (`username`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbuserinrole_ibfk_2` FOREIGN KEY (`role`) REFERENCES `tbrole` (`role`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `training_phrases`
--
ALTER TABLE `training_phrases`
  ADD CONSTRAINT `training_phrases_ibfk_1` FOREIGN KEY (`intent_id`) REFERENCES `intents` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

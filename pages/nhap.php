<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timeline Theo Dõi Đơn Hàng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f6;
            padding: 30px;
        }

        .tracking-container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #007bff;
            text-align: center;
            margin-bottom: 25px;
            font-weight: 600;
        }
        
        h3 {
            margin-top: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            font-weight: 500;
        }

        .current-status {
            background-color: #e6f7ff;
            color: #007bff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            font-size: 1.1em;
            font-weight: bold;
            text-align: center;
            border: 1px solid #b3d9ff;
        }

        .timeline {
            border-left: 3px solid #ced4da;
            padding-left: 20px;
            position: relative;
            margin-top: 20px;
        }

        .timeline.delivered {
            border-left-color: #28a745;
        }

        .timeline-item {
            margin-bottom: 30px;
            position: relative;
            padding-left: 15px;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -29px;
            top: 5px;
            width: 12px;
            height: 12px;
            background-color: #ced4da;
            border-radius: 50%;
            border: 3px solid #ffffff;
            box-shadow: 0 0 0 2px #ced4da;
            transition: all 0.3s;
        }

        .timeline-item.active::before {
            background-color: #007bff;
            box-shadow: 0 0 0 2px #007bff;
        }

        .timeline.delivered .timeline-item:first-child::before {
            background-color: #28a745;
            box-shadow: 0 0 0 2px #28a745;
        }

        .timeline-date {
            color: #6c757d;
            font-size: 0.9em;
        }

        .timeline-status {
            font-weight: 600;
            color: #343a40;
            margin-top: 5px;
        }

        .timeline-detail {
            margin-top: 5px;
            font-size: 0.95em;
            color: #555;
            padding: 8px 0;
        }

        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="tracking-container">
    <h2>🚚 Lịch Sử Vận Chuyển Đơn Hàng</h2>
    <div id="tracking-timeline">
        <div class="current-status">Đang tải dữ liệu...</div>
    </div>
</div>

<script>
    const sampleTrackingData = {
        madonhang: "DH123456789",
        current_status: "Đã giao hàng thành công",
        history: [
            {
                thoigian: "2025-11-20 10:30:00",
                trangthai_ghtk: "Đã giao hàng thành công",
                mo_ta: "Người nhận đã ký xác nhận tại địa chỉ 123 Nguyễn Văn Linh."
            },
            {
                thoigian: "2025-11-20 08:00:00",
                trangthai_ghtk: "Đang trên đường giao",
                mo_ta: "Shipper [Tên Shipper] đang giao đến người nhận."
            },
            {
                thoigian: "2025-11-19 19:45:00",
                trangthai_ghtk: "Đã đến kho phân loại (Hà Nội)",
                mo_ta: "Đơn hàng đã được nhập vào kho phân loại trung tâm."
            },
            {
                thoigian: "2025-11-18 14:20:00",
                trangthai_ghtk: "Đã lấy hàng thành công",
                mo_ta: "Đơn hàng đã được lấy từ người bán và bắt đầu vận chuyển."
            },
            {
                thoigian: "2025-11-18 10:00:00",
                trangthai_ghtk: "Đơn hàng mới tạo",
                mo_ta: "Hệ thống đã ghi nhận đơn hàng."
            },
        ]
    };

    function fetchTrackingData(orderId) {
        return new Promise((resolve) => {
            setTimeout(() => {
                if (orderId === "DH123456789") {
                    resolve(sampleTrackingData);
                } else if (orderId === "ERROR999") {
                    resolve({ error: "Mã đơn hàng không tồn tại." });
                } else {
                    resolve({ madonhang: orderId, current_status: "Chờ xử lý", history: [] });
                }
            }, 500);
        });
    }

    function renderTrackingTimeline(data) {
        const container = document.getElementById('tracking-timeline');
        
        if (data.error) {
            container.innerHTML = `<p class="error-message">❌ Lỗi: ${data.error}</p>`;
            return;
        }

        const isDelivered = data.current_status.includes('Đã giao');
        const timelineClass = isDelivered ? 'timeline delivered' : 'timeline';
        
        let historyHtml = '';
        if (data.history && data.history.length > 0) {
            data.history.forEach((item, index) => {
                const date = new Date(item.thoigian).toLocaleString('vi-VN', {
                    year: 'numeric', month: 'numeric', day: 'numeric',
                    hour: '2-digit', minute: '2-digit', second: '2-digit',
                    hour12: false
                });

                const itemClass = (index === 0) ? 'timeline-item active' : 'timeline-item';

                historyHtml += `
                    <div class="${itemClass}">
                        <div class="timeline-date">${date}</div>
                        <div class="timeline-status">${item.trangthai_ghtk}</div>
                        <div class="timeline-detail">${item.mo_ta || 'Không có mô tả chi tiết.'}</div>
                    </div>
                `;
            });
        } else {
            historyHtml = '<p style="text-align: center; color: #777;">Chưa có dữ liệu lịch sử vận chuyển chi tiết.</p>';
        }

        const finalHtml = `
            <div class="current-status">Mã đơn hàng: ${data.madonhang} | Trạng thái: 
                <span style="color: ${isDelivered ? '#28a745' : '#007bff'}">
                    ${data.current_status || 'Không rõ'}
                </span>
            </div>
            <h3>Lịch sử Vận chuyển</h3>
            <div class="${timelineClass}">
                ${historyHtml}
            </div>
        `;
        
        container.innerHTML = finalHtml;
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Trong thực tế, bạn có thể lấy mã đơn hàng từ URL
        // const urlParams = new URLSearchParams(window.location.search);
        // const orderId = urlParams.get('madonhang') || 'DH123456789';
        
        const orderId = 'DH123456789'; // Sử dụng mã đơn hàng mẫu

        fetchTrackingData(orderId)
            .then(data => {
                renderTrackingTimeline(data);
            })
            .catch(error => {
                const container = document.getElementById('tracking-timeline');
                container.innerHTML = `<p class="error-message">❌ Lỗi không xác định: ${error}</p>`;
            });
    });
</script>

</body>
</html>
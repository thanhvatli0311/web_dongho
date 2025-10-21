document.addEventListener('DOMContentLoaded', () => {
    // --- Lấy các phần tử DOM ---
    const productsListContainer = document.getElementById('products-list-container');
    const categoryFilter = document.getElementById('category-filter');
    const brandFilter = document.getElementById('brand-filter');
    const minPriceInput = document.getElementById('min-price');
    const maxPriceInput = document.getElementById('max-price');
    const searchInput = document.getElementById('search-input');
    const applyFiltersBtn = document.getElementById('apply-filters-btn');
    const sortBySelect = document.getElementById('sort-by');
    const prevPageBtn = document.getElementById('prev-page-btn');
    const nextPageBtn = document.getElementById('next-page-btn');
    const currentPageDisplay = document.getElementById('current-page-display');

    // --- Biến trạng thái ---
    let currentPage = 1;
    const productsPerPage = 9; // Giảm số lượng để dễ kiểm tra phân trang

    // --- Hàm tải và hiển thị sản phẩm ---
    const loadProducts = async () => {
        productsListContainer.innerHTML = '<p style="text-align: center;">Đang tải sản phẩm...</p>';
        const params = new URLSearchParams();

        // Thêm bộ lọc
        if (categoryFilter.value) params.append('category_id', categoryFilter.value);
        if (brandFilter.value) params.append('brand_id', brandFilter.value);
        if (minPriceInput.value) params.append('min_price', minPriceInput.value);
        if (maxPriceInput.value) params.append('max_price', maxPriceInput.value);
        if (searchInput.value) params.append('search', searchInput.value);

        // Thêm sắp xếp
        const [sortBy, sortOrder] = sortBySelect.value.split('_');
        params.append('sort_by', sortBy);
        params.append('sort_order', sortOrder);

        // Thêm phân trang
        params.append('limit', productsPerPage);
        params.append('offset', (currentPage - 1) * productsPerPage);

        try {
            const response = await fetch(`api/products.php?${params.toString()}`);
            const result = await response.json();

            productsListContainer.innerHTML = ''; // Xóa nội dung cũ

            if (result.success && result.data.length > 0) {
                result.data.forEach(product => {
                    const productItem = document.createElement('div');
                    productItem.className = 'product-item';
                    productItem.innerHTML = `
                        <a href="product_detail.html?id=${product.id}">
                            <img src="${product.image_url ? 'images/products/' + product.image_url : 'images/placeholder.jpg'}" alt="${escapeHTML(product.name)}">
                            <h3>${escapeHTML(product.name)}</h3>
                            <p class="price">${formatCurrency(product.price)}</p>
                        </a>
                        <button class="btn-add-to-cart" data-product-id="${product.id}">Thêm vào giỏ</button>
                    `;
                    productsListContainer.appendChild(productItem);
                });
            } else {
                productsListContainer.innerHTML = '<p style="text-align: center;">Không tìm thấy sản phẩm nào phù hợp.</p>';
            }

            // Cập nhật trạng thái phân trang
            currentPageDisplay.textContent = `Trang ${currentPage}`;
            prevPageBtn.disabled = currentPage === 1;
            // Giả sử có nhiều sản phẩm hơn nếu số lượng trả về bằng limit
            // Trong thực tế cần có tổng số sản phẩm từ server để biết có bao nhiêu trang
            nextPageBtn.disabled = result.data.length < productsPerPage;

            // Gắn sự kiện cho nút "Thêm vào giỏ" (sau khi sản phẩm đã được render)
            document.querySelectorAll('.btn-add-to-cart').forEach(button => {
                button.addEventListener('click', async (e) => {
                    const productId = e.target.dataset.productId;
                    const quantity = 1; // Mặc định thêm 1 sản phẩm

                    const response = await fetch('api/cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ product_id: productId, quantity: quantity })
                    });
                    const result = await response.json();
                    showToast(result.message, result.success ? 'success' : 'error');
                    if (result.success) {
                        updateCartCount(); // Cập nhật số lượng giỏ hàng trên header
                    }
                });
            });

        } catch (error) {
            console.error('Error loading products:', error);
            productsListContainer.innerHTML = '<p style="text-align: center; color: red;">Đã xảy ra lỗi khi tải sản phẩm.</p>';
        }
    };

    // --- Hàm tải danh mục và thương hiệu cho bộ lọc ---
    const loadFilters = async () => {
        try {
            // Tải danh mục
            const catResponse = await fetch('api/categories.php'); // Cần tạo api/categories.php
            const catResult = await catResponse.json();
            if (catResult.success) {
                catResult.data.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = escapeHTML(cat.name);
                    categoryFilter.appendChild(option);
                });
            }

            // Tải thương hiệu
            const brandResponse = await fetch('api/brands.php'); // Cần tạo api/brands.php
            const brandResult = await brandResponse.json();
            if (brandResult.success) {
                brandResult.data.forEach(brand => {
                    const option = document.createElement('option');
                    option.value = brand.id;
                    option.textContent = escapeHTML(brand.name);
                    brandFilter.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading filters:', error);
            showToast('Không thể tải bộ lọc.', 'error');
        }
    };

    // --- Gắn sự kiện ---
    applyFiltersBtn.addEventListener('click', () => {
        currentPage = 1; // Khi áp dụng bộ lọc mới, quay về trang 1
        loadProducts();
    });

    sortBySelect.addEventListener('change', () => {
        currentPage = 1; // Khi thay đổi sắp xếp, quay về trang 1
        loadProducts();
    });

    prevPageBtn.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            loadProducts();
        }
    });

    nextPageBtn.addEventListener('click', () => {
        currentPage++;
        loadProducts();
    });

    // --- Khởi tạo khi tải trang ---
    loadFilters(); // Tải bộ lọc trước
    loadProducts(); // Sau đó tải sản phẩm
});
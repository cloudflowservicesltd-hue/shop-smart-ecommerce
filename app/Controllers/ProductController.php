<?php

/**
 * Product Controller
 *
 * Handles product listing, detail, category filtering, and search pages.
 */
class ProductController extends BaseController
{
    /**
     * GET /products
     * Paginated product listing with filters (search, category, brand, price, sort).
     */
    public function index(): void
    {
        $page = (int)Request::query('page', 1);
        $search = Request::query('q', '');
        $category = Request::query('category', '');
        $brand = Request::query('brand', '');
        $sort = Request::query('sort', 'newest');
        $minPrice = Request::query('min_price', '');
        $maxPrice = Request::query('max_price', '');

        $where = "p.is_active = 1 AND (p.product_status IS NULL OR p.product_status IN ('active','out_of_stock_returning'))";
        $params = [];
        if ($search) { $where .= " AND (p.name LIKE ? OR p.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($category) { $where .= " AND c.slug = ?"; $params[] = $category; }
        if ($brand) { $where .= " AND b.slug = ?"; $params[] = $brand; }
        if ($minPrice !== '') { $where .= " AND IF(p.discount_price > 0 AND p.discount_price < p.price, p.discount_price, p.price) >= ?"; $params[] = (float)$minPrice; }
        if ($maxPrice !== '') { $where .= " AND IF(p.discount_price > 0 AND p.discount_price < p.price, p.discount_price, p.price) <= ?"; $params[] = (float)$maxPrice; }

        $orderBy = match($sort) { 'price_asc' => 'IF(p.discount_price > 0 AND p.discount_price < p.price, p.discount_price, p.price) ASC', 'price_desc' => 'IF(p.discount_price > 0 AND p.discount_price < p.price, p.discount_price, p.price) DESC', 'popular' => 'p.created_at DESC', 'rating' => '(SELECT AVG(rating) FROM reviews WHERE product_id = p.id) DESC', default => 'p.created_at DESC' };

        $table = "products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id";

        // Count with JOIN
        $countSql = "SELECT COUNT(DISTINCT p.id) as cnt FROM {$table} WHERE {$where}";
        $totalProducts = (int)Database::selectOne($countSql, $params)['cnt'];

        // Calculate pagination
        $perPage = 12;
        $lastPage = (int)ceil($totalProducts / $perPage);
        $offset = ($page - 1) * $perPage;

        // Fetch with explicit columns (no collision)
        $products = Database::select(
            "SELECT p.*, c.name as category_name, c.slug as category_slug, b.name as brand_name, b.slug as brand_slug,
                    (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image,
                    (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating,
                    (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN brands b ON p.brand_id = b.id
             WHERE {$where}
             ORDER BY {$orderBy}
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        $pagination = [
            'data' => $products,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $totalProducts,
            'last_page' => $lastPage,
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $totalProducts),
        ];

        $categories = Database::select("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id AND is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning'))) as product_count FROM categories c WHERE c.parent_id IS NULL AND c.is_active = 1 ORDER BY name");
        $brands = Database::select("SELECT b.*, (SELECT COUNT(*) FROM products WHERE brand_id = b.id AND is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning'))) as product_count FROM brands b WHERE b.is_active = 1 ORDER BY name");

        // Set selected category for sidebar highlighting
        $selectedCategory = 0;
        $currentCategory = null;
        if ($category) {
            $currentCategory = Database::selectOne("SELECT * FROM categories WHERE slug = ?", [$category]);
            $selectedCategory = $currentCategory['id'] ?? 0;
        }

        // Build active filters
        $activeFilters = [];
        $removeFilterUrls = [];
        $baseUrl = '/products';
        if ($search) {
            $activeFilters['search'] = 'Search: ' . $search;
            $rm = $_GET; unset($rm['q']); $removeFilterUrls['search'] = $baseUrl . '?' . http_build_query($rm);
        }
        if ($brand) {
            $activeFilters['brand'] = 'Brand: ' . $brand;
            $rm = $_GET; unset($rm['brand']); $removeFilterUrls['brand'] = $baseUrl . '?' . http_build_query($rm);
        }
        if ($minPrice !== '' || $maxPrice !== '') {
            $activeFilters['price'] = getCurrencySymbol() . ' ' . number_format((float)$minPrice) . ' - ' . getCurrencySymbol() . ' ' . number_format((float)$maxPrice);
            $rm = $_GET; unset($rm['min_price'], $rm['max_price']); $removeFilterUrls['price'] = $baseUrl . '?' . http_build_query($rm);
        }

        $storeName = getStoreName();
        $pageTitle = 'Products - ' . $storeName;
        ob_start();
        include ROOT_PATH . '/resources/views/customer/products.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }

    /**
     * GET /product/{slug}
     * Single product detail page with images, reviews, related products.
     */
    public function show(string $slug): void
    {
        $product = Database::selectOne("SELECT p.*, c.name as category_name, c.slug as category_slug, b.name as brand_name FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id WHERE p.slug = ? AND p.is_active = 1 AND (p.product_status IS NULL OR p.product_status IN ('active','out_of_stock_returning'))", [$slug]);
        if (!$product) { http_response_code(404); View::render('errors/404'); return; }

        $images = Database::select("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order", [$product['id']]);
        $reviews = Database::select("SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? AND r.is_approved = 1 ORDER BY r.created_at DESC", [$product['id']]);
        $related = Database::select("SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image FROM products p WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1 AND (p.product_status IS NULL OR p.product_status IN ('active','out_of_stock_returning')) ORDER BY RAND() LIMIT 4", [$product['category_id'], $product['id']]);

        $category = $product['category_id'] ? Database::selectOne("SELECT * FROM categories WHERE id = ?", [$product['category_id']]) : null;
        $brand = $product['brand_id'] ? Database::selectOne("SELECT * FROM brands WHERE id = ?", [$product['brand_id']]) : null;
        $ratingData = Database::selectOne("SELECT AVG(rating) as avg, COUNT(*) as cnt FROM reviews WHERE product_id = ? AND is_approved = 1", [$product['id']]);
        $avgRating = round($ratingData['avg'] ?? 0, 1);
        $reviewCount = (int)($ratingData['cnt'] ?? 0);
        $inWishlist = Auth::check() ? (bool)Database::selectOne("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?", [Auth::id(), $product['id']]) : false;
        $variants = Database::select("SELECT * FROM product_variants WHERE product_id = ? AND is_active = 1 ORDER BY sort_order, id", [$product['id']]);
        $product['variants'] = $variants;

        $storeName = getStoreName();
        $pageTitle = $product['name'] . ' - ' . $storeName;
        ob_start();
        include ROOT_PATH . '/resources/views/customer/product-detail.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }

    /**
     * GET /category/{slug}
     * Products filtered by a specific category.
     */
    public function category(string $slug): void
    {
        $currentCategory = Database::selectOne("SELECT * FROM categories WHERE slug = ? AND is_active = 1", [$slug]);
        if (!$currentCategory) { http_response_code(404); View::render('errors/404'); return; }

        $_GET['category'] = $slug;
        $page = (int)Request::query('page', 1);
        $brand = Request::query('brand', '');
        $minPrice = Request::query('min_price', '');
        $maxPrice = Request::query('max_price', '');

        $where = "p.is_active = 1 AND (p.product_status IS NULL OR p.product_status IN ('active','out_of_stock_returning')) AND c.slug = ?";
        $params = [$slug];
        if ($brand) { $where .= " AND b.slug = ?"; $params[] = $brand; }
        if ($minPrice !== '') { $where .= " AND IF(p.discount_price > 0 AND p.discount_price < p.price, p.discount_price, p.price) >= ?"; $params[] = (float)$minPrice; }
        if ($maxPrice !== '') { $where .= " AND IF(p.discount_price > 0 AND p.discount_price < p.price, p.discount_price, p.price) <= ?"; $params[] = (float)$maxPrice; }

        $table = "products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id";

        // Count with JOIN
        $countSql = "SELECT COUNT(DISTINCT p.id) as cnt FROM {$table} WHERE {$where}";
        $totalProducts = (int)Database::selectOne($countSql, $params)['cnt'];

        // Calculate pagination
        $perPage = 12;
        $lastPage = (int)ceil($totalProducts / $perPage);
        $offset = ($page - 1) * $perPage;

        // Fetch with explicit columns (no collision)
        $products = Database::select(
            "SELECT p.*, c.name as category_name, c.slug as category_slug, b.name as brand_name, b.slug as brand_slug,
                    (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image,
                    (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating,
                    (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN brands b ON p.brand_id = b.id
             WHERE {$where}
             ORDER BY p.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        $pagination = [
            'data' => $products,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $totalProducts,
            'last_page' => $lastPage,
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $totalProducts),
        ];

        // Categories with product counts
        $categories = Database::select("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id AND is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning'))) as product_count FROM categories c WHERE c.parent_id IS NULL AND c.is_active = 1 ORDER BY name");
        // Brands with product counts
        $brands = Database::select("SELECT b.*, (SELECT COUNT(*) FROM products WHERE brand_id = b.id AND is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning'))) as product_count FROM brands b WHERE b.is_active = 1 ORDER BY name");

        $selectedCategory = $currentCategory['id'];
        $activeFilters = [];
        $removeFilterUrls = [];
        if ($brand) {
            $activeFilters['brand'] = 'Brand: ' . $brand;
            $rm = $_GET; unset($rm['brand']); $removeFilterUrls['brand'] = '/category/' . $slug . '?' . http_build_query($rm);
        }
        if ($minPrice !== '' || $maxPrice !== '') {
            $label = 'Price: ' . getCurrencySymbol() . ' ' . number_format((float)$minPrice) . ' - ' . getCurrencySymbol() . ' ' . number_format((float)$maxPrice);
            $activeFilters['price'] = $label;
            $rm = $_GET; unset($rm['min_price'], $rm['max_price']); $removeFilterUrls['price'] = '/category/' . $slug . '?' . http_build_query($rm);
        }

        $storeName = getStoreName();
        $pageTitle = $currentCategory['name'] . ' - ' . $storeName;
        ob_start();
        include ROOT_PATH . '/resources/views/customer/products.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }

    /**
     * GET /categories
     * All top-level categories with product counts.
     */
    public function allCategories(): void
    {
        $categories = Database::select("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id AND is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning'))) as product_count FROM categories c WHERE c.parent_id IS NULL AND c.is_active = 1 ORDER BY sort_order");
        $storeName = getStoreName();
        $pageTitle = 'All Categories - ' . $storeName;
        ob_start();
        include ROOT_PATH . '/resources/views/customer/categories.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }

    /**
     * GET /search
     * Product search results.
     */
    public function search(): void
    {
        $_GET['q'] = Request::query('q', '');
        $page = (int)Request::query('page', 1);
        $q = Request::query('q', '');
        $paginated = Database::paginate("products", $page, 12, "is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning')) AND name LIKE ?", ["%$q%"], 'created_at DESC');
        foreach ($paginated['data'] as &$p) {
            $img = Database::selectOne("SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = 1", [$p['id']]);
            $p['image'] = $img['image_path'] ?? null;
        }
        $products = $paginated['data'];
        $pagination = $paginated;
        $totalProducts = $paginated['total'];
        $query = $q;

        // Search categories
        $searchCategories = [];
        if (!empty($q)) {
            $searchCategories = Database::select("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id AND is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning'))) as product_count FROM categories c WHERE c.is_active = 1 AND (c.name LIKE ? OR c.slug LIKE ?) ORDER BY c.name LIMIT 5", ["%$q%", "%$q%"]);
        }

        // Search brands
        $searchBrands = [];
        if (!empty($q)) {
            $searchBrands = Database::select("SELECT * FROM brands WHERE is_active = 1 AND name LIKE ? ORDER BY name LIMIT 5", ["%$q%"]);
        }

        $storeName = getStoreName();
        $pageTitle = "Search: $q - " . $storeName;
        ob_start();
        include ROOT_PATH . '/resources/views/customer/search.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }
}
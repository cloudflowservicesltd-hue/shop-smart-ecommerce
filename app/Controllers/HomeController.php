<?php

/**
 * Home Controller
 *
 * Handles the home / landing page for customers.
 */
class HomeController extends BaseController
{
    /**
     * GET /
     * Render the home page with featured products, new arrivals, and categories.
     */
    public function index(): void
    {
        $featuredProducts = Database::select("SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image, c.name as category_name, b.name as brand_name, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating, (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id WHERE p.is_featured = 1 AND p.is_active = 1 AND (p.product_status IS NULL OR p.product_status IN ('active','out_of_stock_returning')) ORDER BY p.created_at DESC LIMIT 8");
        $newProducts = Database::select("SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id WHERE p.is_active = 1 AND (p.product_status IS NULL OR p.product_status IN ('active','out_of_stock_returning')) ORDER BY p.created_at DESC LIMIT 12");
        $categories = Database::select("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id AND is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning'))) as product_count FROM categories c WHERE c.parent_id IS NULL AND c.is_active = 1 ORDER BY c.sort_order LIMIT 10");

        $storeName = getStoreName();
        $pageTitle = $storeName . ' - AI-Powered Ecommerce & POS';
        ob_start();
        include ROOT_PATH . '/resources/views/customer/home.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }
}
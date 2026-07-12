<?php

class AdminProductController extends BaseController
{
    public function index()
    {
        $breadcrumbs = [['Products', '/admin/products']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/products.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function create()
    {
        $breadcrumbs = [['Products', '/admin/products'], ['Add Product', '']];
        $product = null;
        ob_start();
        include ROOT_PATH . '/resources/views/admin/product-form.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function store()
    {
        $name = Request::post('name', '');
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name)) . '-' . time();
        $sku = trim(Request::post('sku', ''));
        if (empty($sku)) {
            $sku = 'SKU-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        }
        $barcode = trim(Request::post('barcode', ''));
        if (empty($barcode)) {
            $barcode = 'BC-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
        }
        $hasVariants = Request::post('has_variants') ? 1 : 0;
        $data = [
            'name' => $name, 'slug' => $slug,
            'short_description' => Request::post('short_description', ''),
            'description' => Request::post('description', ''),
            'category_id' => Request::post('category_id') ?: null,
            'brand_id' => Request::post('brand_id') ?: null,
            'price' => (float)Request::post('price', 0),
            'cost_price' => (float)Request::post('cost_price', 0),
            'discount_price' => Request::post('discount_price') ? (float)Request::post('discount_price') : null,
            'stock_quantity' => (int)Request::post('stock_quantity', 0),
            'low_stock_threshold' => (int)Request::post('low_stock_threshold', 10),
            'sku' => $sku,
            'barcode' => $barcode,
            'weight' => Request::post('weight') ? (float)Request::post('weight') : null,
            'supplier' => Request::post('supplier', ''),
            'product_status' => Request::post('product_status', 'active'),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'is_featured' => Request::post('is_featured') ? 1 : 0,
            'has_variants' => $hasVariants,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ];
        $productId = Database::insert('products', $data);

        // Save variants
        $this->saveVariants($productId);

        // Handle multiple image uploads
        if (isset($_FILES['images']) && $_FILES['images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            $fileCount = is_array($_FILES['images']['name']) ? count($_FILES['images']['name']) : 0;
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['images']['name'][$i],
                        'type' => $_FILES['images']['type'][$i],
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'error' => $_FILES['images']['error'][$i],
                        'size' => $_FILES['images']['size'][$i],
                    ];
                    $config = require ROOT_PATH . '/config/app.php';
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, $config['upload']['allowed_types']) && $file['size'] <= $config['upload']['max_size']) {
                        $uploadDir = ROOT_PATH . '/public/uploads/products/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                        $filename = uniqid() . '_' . time() . '.' . $ext;
                        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                            $isPrimary = ($i === 0) ? 1 : 0;
                            Database::insert('product_images', [
                                'product_id' => $productId,
                                'image_path' => '/uploads/products/' . $filename,
                                'is_primary' => $isPrimary,
                                'sort_order' => $i,
                                'created_at' => date('Y-m-d H:i:s')
                            ]);
                        }
                    }
                }
            }
        }

        Session::flash('success', 'Product created successfully');
        Redirect::to('/admin/products');
    }

    public function edit($id)
    {
        $product = Database::selectOne("SELECT * FROM products WHERE id = ?", [$id]);
        if (!$product) { http_response_code(404); echo 'Not found'; return; }
        $breadcrumbs = [['Products', '/admin/products'], ['Edit Product', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/product-form.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function update($id)
    {
        $name = Request::post('name', '');
        $sku = trim(Request::post('sku', ''));
        if (empty($sku)) {
            $sku = 'SKU-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        }
        $barcode = trim(Request::post('barcode', ''));
        if (empty($barcode)) {
            $barcode = 'BC-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
        }
        $hasVariants = Request::post('has_variants') ? 1 : 0;
        $data = [
            'name' => $name,
            'short_description' => Request::post('short_description', ''),
            'description' => Request::post('description', ''),
            'category_id' => Request::post('category_id') ?: null,
            'brand_id' => Request::post('brand_id') ?: null,
            'price' => (float)Request::post('price', 0),
            'cost_price' => (float)Request::post('cost_price', 0),
            'discount_price' => Request::post('discount_price') ? (float)Request::post('discount_price') : null,
            'stock_quantity' => (int)Request::post('stock_quantity', 0),
            'low_stock_threshold' => (int)Request::post('low_stock_threshold', 10),
            'sku' => $sku,
            'barcode' => $barcode,
            'weight' => Request::post('weight') ? (float)Request::post('weight') : null,
            'supplier' => Request::post('supplier', ''),
            'product_status' => Request::post('product_status', 'active'),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'is_featured' => Request::post('is_featured') ? 1 : 0,
            'has_variants' => $hasVariants,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        Database::update('products', $data, 'id = ?', [$id]);

        // Save variants (delete old, insert new)
        $this->saveVariants($id);

        $image = FileUpload::handle('image');
        if ($image) {
            Database::update('product_images', ['is_primary' => 0], 'product_id = ? AND is_primary = 1', [$id]);
            Database::insert('product_images', ['product_id' => $id, 'image_path' => $image, 'is_primary' => 1, 'created_at' => date('Y-m-d H:i:s')]);
        }

        // Handle multiple image uploads
        if (isset($_FILES['images']) && $_FILES['images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            $existingCount = Database::selectOne("SELECT COUNT(*) as cnt FROM product_images WHERE product_id = ?", [$id])['cnt'];
            $fileCount = is_array($_FILES['images']['name']) ? count($_FILES['images']['name']) : 0;
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['images']['name'][$i],
                        'type' => $_FILES['images']['type'][$i],
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'error' => $_FILES['images']['error'][$i],
                        'size' => $_FILES['images']['size'][$i],
                    ];
                    $config = require ROOT_PATH . '/config/app.php';
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, $config['upload']['allowed_types']) && $file['size'] <= $config['upload']['max_size']) {
                        $uploadDir = ROOT_PATH . '/public/uploads/products/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                        $filename = uniqid() . '_' . time() . '.' . $ext;
                        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                            // First image is primary only if no existing primary images
                            $isPrimary = ($i === 0 && $existingCount == 0) ? 1 : 0;
                            Database::insert('product_images', [
                                'product_id' => $id,
                                'image_path' => '/uploads/products/' . $filename,
                                'is_primary' => $isPrimary,
                                'sort_order' => $existingCount + $i,
                                'created_at' => date('Y-m-d H:i:s')
                            ]);
                        }
                    }
                }
            }
        }

        Session::flash('success', 'Product updated successfully');
        Redirect::to('/admin/products');
    }

    public function delete($id)
    {
        Database::delete('products', 'id = ?', [$id]);
        Session::flash('success', 'Product deleted');
        Redirect::to('/admin/products');
    }

    public function deleteImage($id, $imageId)
    {
        $img = Database::selectOne("SELECT * FROM product_images WHERE id = ? AND product_id = ?", [$imageId, $id]);
        if ($img) {
            $fullPath = ROOT_PATH . '/public' . $img['image_path'];
            if (file_exists($fullPath)) unlink($fullPath);
            Database::delete('product_images', 'id = ?', [$imageId]);
            // If deleted image was primary, set another as primary
            $another = Database::selectOne("SELECT id FROM product_images WHERE product_id = ? AND id != ? ORDER BY sort_order LIMIT 1", [$id, $imageId]);
            if ($another && $img['is_primary']) {
                Database::update('product_images', ['is_primary' => 1], 'id = ?', [$another['id']]);
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        return;
    }

    public function reviews($id)
    {
        $product = Database::selectOne("SELECT * FROM products WHERE id = ?", [$id]);
        if (!$product) { Redirect::to('/admin/products'); return; }
        $reviews = Database::select("SELECT r.*, u.name as user_name, u.email as user_email FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC", [$id]);
        $breadcrumbs = [['Products', '/admin/products'], [$product['name'], '/admin/products/' . $id . '/reviews'], ['Reviews', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/product-reviews.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function approveReview($id, $reviewId)
    {
        Database::update('reviews', ['is_approved' => 1], 'id = ?', [$reviewId]);
        Session::flash('success', 'Review approved');
        Redirect::to('/admin/products/' . $id . '/reviews');
    }

    public function rejectReview($id, $reviewId)
    {
        Database::update('reviews', ['is_approved' => 0], 'id = ?', [$reviewId]);
        Session::flash('success', 'Review rejected');
        Redirect::to('/admin/products/' . $id . '/reviews');
    }

    public function deleteReview($id, $reviewId)
    {
        Database::delete('reviews', 'id = ?', [$reviewId]);
        Session::flash('success', 'Review deleted');
        Redirect::to('/admin/products/' . $id . '/reviews');
    }

    public function duplicate($id)
    {
        $product = Database::selectOne("SELECT * FROM products WHERE id = ?", [$id]);
        if (!$product) { Session::flash('error', 'Product not found'); Redirect::to('/admin/products'); return; }

        $name = $product['name'] . ' (Copy)';
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name)) . '-' . time();

        // Copy product data (exclude id, slug, created_at, updated_at, sku, barcode)
        $sku = 'SKU-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        $barcode = 'BC-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));

        $data = [
            'name'              => $name,
            'slug'              => $slug,
            'short_description' => $product['short_description'] ?? '',
            'description'       => $product['description'] ?? '',
            'category_id'       => $product['category_id'],
            'brand_id'          => $product['brand_id'],
            'price'             => $product['price'],
            'cost_price'        => $product['cost_price'] ?? 0,
            'discount_price'    => $product['discount_price'],
            'stock_quantity'    => 0,
            'low_stock_threshold' => $product['low_stock_threshold'] ?? 10,
            'sku'               => $sku,
            'barcode'           => $barcode,
            'weight'            => $product['weight'],
            'supplier'          => $product['supplier'] ?? '',
            'product_status'    => 'draft',
            'is_active'         => 0,
            'is_featured'       => 0,
            'meta_title'        => $product['meta_title'] ?? '',
            'meta_description'  => $product['meta_description'] ?? '',
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ];

        $newId = Database::insert('products', $data);

        // Copy images
        $images = Database::select("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC", [$id]);
        foreach ($images as $img) {
            Database::insert('product_images', [
                'product_id'  => $newId,
                'image_path'  => $img['image_path'],
                'is_primary'  => $img['is_primary'],
                'sort_order'  => $img['sort_order'],
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        Session::flash('success', 'Product duplicated successfully. The copy is set to draft status.');
        Redirect::to('/admin/products/' . $newId . '/edit');
    }

    /**
     * Save product variants from POST data.
     * Deletes all existing variants for the product, then inserts new ones.
     */
    private function saveVariants(int $productId): void
    {
        // Always delete existing variants and re-insert (simpler than tracking adds/edits/deletes)
        // But first, collect existing variant images for cleanup (if replaced)
        $oldVariants = Database::select("SELECT id, image FROM product_variants WHERE product_id = ?", [$productId]);
        $oldImages = [];
        foreach ($oldVariants as $ov) {
            if (!empty($ov['image'])) $oldImages[$ov['id']] = $ov['image'];
        }
        Database::delete('product_variants', 'product_id = ?', [$productId]);

        $names = Request::post('variant_name', []);
        if (empty($names) || !is_array($names)) return;

        $skus = Request::post('variant_sku', []);
        $prices = Request::post('variant_price', []);
        $costPrices = Request::post('variant_cost_price', []);
        $stocks = Request::post('variant_stock', []);
        $existingImages = Request::post('variant_existing_image', []);
        $variantFiles = $_FILES['variant_image'] ?? null;

        $config = require ROOT_PATH . '/config/app.php';
        $uploadDir = ROOT_PATH . '/public/uploads/variants/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        foreach ($names as $i => $name) {
            $name = trim($name);
            if (empty($name)) continue;

            $sku = trim($skus[$i] ?? '');
            if (empty($sku)) {
                $sku = 'SKU-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            }
            $price = !empty($prices[$i]) ? (float)$prices[$i] : null;
            $costPrice = !empty($costPrices[$i]) ? (float)$costPrices[$i] : 0;
            $stock = (int)($stocks[$i] ?? 0);

            // Handle variant image upload
            $variantImage = null;
            if ($variantFiles && isset($variantFiles['name'][$i]) && $variantFiles['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $variantFiles['name'][$i],
                    'type' => $variantFiles['type'][$i],
                    'tmp_name' => $variantFiles['tmp_name'][$i],
                    'error' => $variantFiles['error'][$i],
                    'size' => $variantFiles['size'][$i],
                ];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $config['upload']['allowed_types']) && $file['size'] <= $config['upload']['max_size']) {
                    $filename = uniqid() . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                        $variantImage = '/uploads/variants/' . $filename;
                    }
                }
            } elseif (!empty($existingImages[$i])) {
                // Keep existing image if no new file uploaded
                $variantImage = $existingImages[$i];
            }

            Database::insert('product_variants', [
                'product_id' => $productId,
                'variant_name' => $name,
                'sku' => $sku,
                'price' => $price,
                'cost_price' => $costPrice,
                'stock_quantity' => $stock,
                'image' => $variantImage,
                'is_active' => 1,
                'sort_order' => $i,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Auto-sum variant stock into main product stock
        $totalStock = Database::selectOne("SELECT COALESCE(SUM(stock_quantity), 0) as total FROM product_variants WHERE product_id = ? AND is_active = 1", [$productId])['total'];
        Database::update('products', ['stock_quantity' => (int)$totalStock], 'id = ?', [$productId]);
    }
}
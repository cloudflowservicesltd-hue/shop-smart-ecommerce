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
            'sku' => Request::post('sku', ''),
            'barcode' => Request::post('barcode', ''),
            'weight' => Request::post('weight') ? (float)Request::post('weight') : null,
            'supplier' => Request::post('supplier', ''),
            'product_status' => Request::post('product_status', 'active'),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'is_featured' => Request::post('is_featured') ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ];
        $productId = Database::insert('products', $data);

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
            'sku' => Request::post('sku', ''),
            'barcode' => Request::post('barcode', ''),
            'weight' => Request::post('weight') ? (float)Request::post('weight') : null,
            'supplier' => Request::post('supplier', ''),
            'product_status' => Request::post('product_status', 'active'),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'is_featured' => Request::post('is_featured') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        Database::update('products', $data, 'id = ?', [$id]);

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
}
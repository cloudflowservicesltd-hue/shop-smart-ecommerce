<?php

require_once dirname(dirname(__DIR__)) . '/app/bootstrap.php';

echo "Seeding database...\n";

// Create Super Admin
$adminId = Database::insert('users', [
    'name' => 'Super Admin',
    'email' => 'admin@ecommerce.com',
    'password' => password_hash('admin123', PASSWORD_DEFAULT),
    'role' => 'super_admin',
    'phone' => '+254700000001',
    'is_active' => 1,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);

// Create Store Admin
Database::insert('users', [
    'name' => 'Store Manager',
    'email' => 'manager@ecommerce.com',
    'password' => password_hash('manager123', PASSWORD_DEFAULT),
    'role' => 'admin',
    'phone' => '+254700000002',
    'is_active' => 1,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);

// Create Cashier
Database::insert('users', [
    'name' => 'John Cashier',
    'email' => 'cashier@ecommerce.com',
    'password' => password_hash('cashier123', PASSWORD_DEFAULT),
    'role' => 'cashier',
    'phone' => '+254700000003',
    'is_active' => 1,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);

// Create test customer
Database::insert('users', [
    'name' => 'Jane Customer',
    'email' => 'jane@example.com',
    'password' => password_hash('customer123', PASSWORD_DEFAULT),
    'role' => 'customer',
    'phone' => '+254712345678',
    'is_active' => 1,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);

// Create Categories
$categories = [
    ['name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Phones, laptops, and gadgets', 'image' => '/uploads/categories/electronics.jpg', 'sort_order' => 1],
    ['name' => 'Clothing', 'slug' => 'clothing', 'description' => 'Fashion and apparel', 'image' => '/uploads/categories/clothing.jpg', 'sort_order' => 2],
    ['name' => 'Home & Kitchen', 'slug' => 'home-kitchen', 'description' => 'Home appliances and kitchen items', 'image' => '/uploads/categories/home-kitchen.jpg', 'sort_order' => 3],
    ['name' => 'Beauty & Health', 'slug' => 'beauty-health', 'description' => 'Cosmetics and health products', 'image' => '/uploads/categories/beauty-health.jpg', 'sort_order' => 4],
    ['name' => 'Sports & Outdoor', 'slug' => 'sports-outdoor', 'description' => 'Sports equipment and outdoor gear', 'image' => '/uploads/categories/sports-outdoor.jpg', 'sort_order' => 5],
    ['name' => 'Books & Stationery', 'slug' => 'books-stationery', 'description' => 'Books, notebooks, and office supplies', 'image' => '/uploads/categories/books-stationery.jpg', 'sort_order' => 6],
    ['name' => 'Toys & Games', 'slug' => 'toys-games', 'description' => 'Toys and gaming accessories', 'image' => '/uploads/categories/toys-games.jpg', 'sort_order' => 7],
    ['name' => 'Groceries', 'slug' => 'groceries', 'description' => 'Food and household items', 'image' => '/uploads/categories/groceries.jpg', 'sort_order' => 8],
];

$catIds = [];
foreach ($categories as $cat) {
    $cat['created_at'] = date('Y-m-d H:i:s');
    $cat['updated_at'] = date('Y-m-d H:i:s');
    $catIds[$cat['slug']] = Database::insert('categories', $cat);
}

// Sub-categories
$subcategories = [
    ['name' => 'Smartphones', 'slug' => 'smartphones', 'description' => 'Mobile phones', 'parent_id' => $catIds['electronics'], 'sort_order' => 1],
    ['name' => 'Laptops', 'slug' => 'laptops', 'description' => 'Laptop computers', 'parent_id' => $catIds['electronics'], 'sort_order' => 2],
    ['name' => 'Accessories', 'slug' => 'accessories', 'description' => 'Phone and laptop accessories', 'parent_id' => $catIds['electronics'], 'sort_order' => 3],
    ['name' => "Men's Wear", 'slug' => 'mens-wear', 'description' => "Men's clothing", 'parent_id' => $catIds['clothing'], 'sort_order' => 1],
    ['name' => "Women's Wear", 'slug' => 'womens-wear', 'description' => "Women's clothing", 'parent_id' => $catIds['clothing'], 'sort_order' => 2],
];

foreach ($subcategories as $cat) {
    $cat['created_at'] = date('Y-m-d H:i:s');
    $cat['updated_at'] = date('Y-m-d H:i:s');
    Database::insert('categories', $cat);
}

// Create Brands
$brands = [
    ['name' => 'Samsung', 'slug' => 'samsung', 'logo' => '/uploads/brands/samsung.jpg'],
    ['name' => 'Apple', 'slug' => 'apple', 'logo' => '/uploads/brands/apple.jpg'],
    ['name' => 'Nike', 'slug' => 'nike', 'logo' => '/uploads/brands/nike.jpg'],
    ['name' => 'Sony', 'slug' => 'sony', 'logo' => '/uploads/brands/sony.jpg'],
    ['name' => 'HP', 'slug' => 'hp', 'logo' => '/uploads/brands/hp.jpg'],
    ['name' => 'Lenovo', 'slug' => 'lenovo', 'logo' => '/uploads/brands/lenovo.jpg'],
    ['name' => 'Adidas', 'slug' => 'adidas', 'logo' => '/uploads/brands/adidas.jpg'],
    ['name' => 'LG', 'slug' => 'lg', 'logo' => '/uploads/brands/lg.jpg'],
];

$brandIds = [];
foreach ($brands as $brand) {
    $brand['created_at'] = date('Y-m-d H:i:s');
    $brand['updated_at'] = date('Y-m-d H:i:s');
    $brandIds[$brand['slug']] = Database::insert('brands', $brand);
}

// Create Products
$products = [
    ['name' => 'Samsung Galaxy S24 Ultra', 'slug' => 'samsung-galaxy-s24-ultra', 'short_description' => 'The ultimate Galaxy experience with AI features', 'description' => 'Experience the next level of mobile innovation with the Samsung Galaxy S24 Ultra. Featuring a stunning 6.8-inch Dynamic AMOLED display, powerful Snapdragon 8 Gen 3 processor, and an advanced AI camera system that captures professional-grade photos and videos.', 'sku' => 'SAM-S24U-256', 'barcode' => '8806090999999', 'category_id' => $catIds['electronics'], 'brand_id' => $brandIds['samsung'], 'price' => 164999, 'cost_price' => 140000, 'stock_quantity' => 25, 'weight' => 0.232, 'is_featured' => 1],
    ['name' => 'iPhone 15 Pro Max', 'slug' => 'iphone-15-pro-max', 'short_description' => 'Titanium design. A17 Pro chip.', 'description' => 'iPhone 15 Pro Max features a strong and light titanium design with the A17 Pro chip, a customizable Action button, and the most powerful iPhone camera system ever.', 'sku' => 'APL-15PM-256', 'barcode' => '0194253999999', 'category_id' => $catIds['electronics'], 'brand_id' => $brandIds['apple'], 'price' => 199999, 'cost_price' => 175000, 'stock_quantity' => 15, 'weight' => 0.221, 'is_featured' => 1],
    ['name' => 'Samsung Galaxy A54 5G', 'slug' => 'samsung-galaxy-a54-5g', 'short_description' => 'Awesome Galaxy experience at a great price', 'description' => 'Samsung Galaxy A54 5G brings flagship features to the mid-range segment with a 6.4-inch Super AMOLED display, 50MP triple camera, and 5000mAh battery.', 'sku' => 'SAM-A54-128', 'barcode' => '8806090888888', 'category_id' => $catIds['electronics'], 'brand_id' => $brandIds['samsung'], 'price' => 42999, 'cost_price' => 35000, 'stock_quantity' => 50, 'weight' => 0.202, 'is_featured' => 0],
    ['name' => 'HP Pavilion Laptop 15', 'slug' => 'hp-pavilion-15', 'short_description' => 'Powerful performance for work and play', 'description' => 'HP Pavilion 15 delivers reliable performance with Intel Core i5, 8GB RAM, 512GB SSD, and a 15.6-inch FHD display perfect for both work and entertainment.', 'sku' => 'HP-PAV15-I5', 'barcode' => '1958760333333', 'category_id' => $catIds['electronics'], 'brand_id' => $brandIds['hp'], 'price' => 89999, 'cost_price' => 72000, 'stock_quantity' => 20, 'weight' => 1.75, 'is_featured' => 1],
    ['name' => 'Sony WH-1000XM5 Headphones', 'slug' => 'sony-wh-1000xm5', 'short_description' => 'Industry-leading noise cancellation', 'description' => 'Experience unparalleled noise cancellation with the Sony WH-1000XM5. Premium comfort, exceptional sound quality, and up to 30 hours of battery life.', 'sku' => 'SNY-XM5-BLK', 'barcode' => '4905524977777', 'category_id' => $catIds['electronics'], 'brand_id' => $brandIds['sony'], 'price' => 34999, 'cost_price' => 28000, 'stock_quantity' => 35, 'weight' => 0.25, 'is_featured' => 1],
    ['name' => 'Nike Air Max 270', 'slug' => 'nike-air-max-270', 'short_description' => 'The lifestyle silhouette with big Air', 'description' => 'Nike Air Max 270 features the largest Max Air unit yet for a super soft ride that feels as impossible as it looks. Available in multiple colorways.', 'sku' => 'NK-AM270-42', 'barcode' => '0196236555555', 'category_id' => $catIds['clothing'], 'brand_id' => $brandIds['nike'], 'price' => 14999, 'cost_price' => 9000, 'stock_quantity' => 80, 'weight' => 0.35, 'is_featured' => 1],
    ['name' => 'Adidas Ultraboost 23', 'slug' => 'adidas-ultraboost-23', 'short_description' => 'Energy return for every stride', 'description' => 'The Adidas Ultraboost 23 delivers incredible energy return with BOOST technology, a Primeknit+ upper, and a Continental rubber outsole for superior grip.', 'sku' => 'AD-UB23-43', 'barcode' => '4065428666666', 'category_id' => $catIds['clothing'], 'brand_id' => $brandIds['adidas'], 'price' => 16999, 'cost_price' => 11000, 'stock_quantity' => 60, 'weight' => 0.32, 'is_featured' => 0],
    ['name' => 'Lenovo IdeaPad 3', 'slug' => 'lenovo-ideapad-3', 'short_description' => 'Everyday computing made easy', 'description' => 'Lenovo IdeaPad 3 is perfect for everyday tasks with its AMD Ryzen 5 processor, 8GB RAM, 256GB SSD, and 15.6-inch anti-glare display.', 'sku' => 'LNV-IP3-R5', 'barcode' => '1958760444444', 'category_id' => $catIds['electronics'], 'brand_id' => $brandIds['lenovo'], 'price' => 64999, 'cost_price' => 52000, 'stock_quantity' => 30, 'weight' => 1.6, 'is_featured' => 0],
    ['name' => 'LG 55" OLED TV', 'slug' => 'lg-55-oled-c3', 'short_description' => 'Infinite contrast with self-lit pixels', 'description' => 'LG OLED C3 delivers perfect blacks, infinite contrast, and over a billion colors with α9 Gen6 AI Processor 4K for an immersive viewing experience.', 'sku' => 'LG-OLED55-C3', 'barcode' => '8806091222222', 'category_id' => $catIds['electronics'], 'brand_id' => $brandIds['lg'], 'price' => 189999, 'cost_price' => 155000, 'stock_quantity' => 8, 'weight' => 18.5, 'is_featured' => 1],
    ['name' => 'Samsung 253L Refrigerator', 'slug' => 'samsung-253l-fridge', 'short_description' => 'Digital Inverter technology for energy efficiency', 'description' => 'Samsung 253L refrigerator with Digital Inverter Compressor, twin cooling plus technology, and a sleek design that fits any modern kitchen.', 'sku' => 'SAM-RF253-SS', 'barcode' => '8806091333333', 'category_id' => $catIds['home-kitchen'], 'brand_id' => $brandIds['samsung'], 'price' => 109999, 'cost_price' => 85000, 'stock_quantity' => 12, 'weight' => 65, 'is_featured' => 0],
    ['name' => 'Nike Dri-FIT T-Shirt', 'slug' => 'nike-dri-fit-tshirt', 'short_description' => 'Stay dry and comfortable', 'description' => 'Nike Dri-FIT technology moves sweat away from your body for quicker evaporation. Made with 100% recycled polyester fibers.', 'sku' => 'NK-DFT-M', 'barcode' => '0196236777777', 'category_id' => $catIds['clothing'], 'brand_id' => $brandIds['nike'], 'price' => 3999, 'cost_price' => 1800, 'stock_quantity' => 200, 'weight' => 0.15, 'is_featured' => 0],
    ['name' => 'Samsung Galaxy Buds FE', 'slug' => 'samsung-galaxy-buds-fe', 'short_description' => 'Premium sound, accessible price', 'description' => 'Samsung Galaxy Buds FE offer premium AKG sound, active noise cancellation, and up to 30 hours of battery life with the charging case.', 'sku' => 'SAM-GBFE-BK', 'barcode' => '8806091444444', 'category_id' => $catIds['electronics'], 'brand_id' => $brandIds['samsung'], 'price' => 8999, 'cost_price' => 5500, 'stock_quantity' => 100, 'weight' => 0.055, 'is_featured' => 1],
];

$productIds = [];
$productImages = [
    '/uploads/products/samsung-galaxy-s24-ultra.jpg',
    '/uploads/products/iphone-15-pro-max.jpg',
    '/uploads/products/samsung-galaxy-a54-5g.jpg',
    '/uploads/products/hp-pavilion-15.jpg',
    '/uploads/products/sony-wh-1000xm5.jpg',
    '/uploads/products/nike-air-max-270.jpg',
    '/uploads/products/adidas-ultraboost-23.jpg',
    '/uploads/products/lenovo-ideapad-3.jpg',
    '/uploads/products/lg-55-oled-c3.jpg',
    '/uploads/products/samsung-253l-fridge.jpg',
    '/uploads/products/nike-dri-fit-tshirt.jpg',
    '/uploads/products/samsung-galaxy-buds-fe.jpg',
];

foreach ($products as $i => $product) {
    $product['created_at'] = date('Y-m-d H:i:s');
    $product['updated_at'] = date('Y-m-d H:i:s');
    $pid = Database::insert('products', $product);
    $productIds[] = $pid;

    // Add primary product image
    Database::insert('product_images', [
        'product_id' => $pid,
        'image_path' => $productImages[$i],
        'is_primary' => 1,
        'sort_order' => 0,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

// Create sample orders
$statuses = ['pending', 'paid', 'processing', 'shipped', 'delivered'];
for ($i = 1; $i <= 15; $i++) {
    $orderNum = 'ORD-' . str_pad(1000 + $i, 6, '0', STR_PAD_LEFT);
    $status = $statuses[array_rand($statuses)];
    $total = rand(5000, 200000);
    Database::insert('orders', [
        'order_number' => $orderNum,
        'customer_id' => $i <= 10 ? 4 : null,
        'customer_name' => 'Customer ' . $i,
        'customer_email' => "customer{$i}@example.com",
        'customer_phone' => "+2547" . rand(10000000, 99999999),
        'status' => $status,
        'payment_method' => ['mpesa', 'card', 'cash'][array_rand(['mpesa', 'card', 'cash'])],
        'payment_status' => in_array($status, ['paid', 'processing', 'shipped', 'delivered']) ? 'paid' : 'pending',
        'subtotal' => $total,
        'tax' => $total * 0.16,
        'total' => $total + ($total * 0.16),
        'is_pos' => $i > 10 ? 1 : 0,
        'created_at' => date('Y-m-d H:i:s', strtotime("-{$i} days")),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
}

// Create reviews
for ($i = 1; $i <= 20; $i++) {
    $ratings = [3, 4, 5, 5, 5, 4, 4, 3, 5, 4];
    $titles = ['Great product!', 'Good value', 'Excellent quality', 'Highly recommend', 'Best purchase ever', 'Very satisfied', 'Worth the price', 'Could be better', 'Amazing!', 'Love it'];
    Database::insert('reviews', [
        'product_id' => $productIds[array_rand($productIds)],
        'user_id' => 4,
        'rating' => $ratings[array_rand($ratings)],
        'title' => $titles[array_rand($titles)],
        'review' => 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.',
        'is_approved' => 1,
        'created_at' => date('Y-m-d H:i:s', strtotime("-{$i} days")),
    ]);
}

// Create branches
Database::insert('branches', ['name' => 'Main Store - Nairobi CBD', 'address' => 'Kenyatta Avenue, Nairobi', 'phone' => '+254700000100', 'is_main' => 1, 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
Database::insert('branches', ['name' => 'Westlands Branch', 'address' => 'Westlands, Nairobi', 'phone' => '+254700000101', 'is_main' => 0, 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);

// Create suppliers
Database::insert('suppliers', ['name' => 'Samsung East Africa', 'contact_person' => 'James Mwangi', 'email' => 'orders@samsung-ea.com', 'phone' => '+254700000200', 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
Database::insert('suppliers', ['name' => 'Apple Authorized Distributor', 'contact_person' => 'Sarah Wanjiku', 'email' => 'supply@apple-dist.co.ke', 'phone' => '+254700000201', 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);

// Create sample settings
$settings = [
    ['store_name', 'ShopSmart Ecommerce', 'general'],
    ['store_tagline', 'AI-Powered Shopping Experience', 'general'],
    ['store_email', 'info@shopsmart.co.ke', 'general'],
    ['store_phone', '+254700000000', 'general'],
    ['store_address', 'Nairobi CBD, Kenya', 'general'],
    ['currency', 'KES', 'general'],
    ['tax_rate', '16', 'general'],
    ['mpesa_enabled', '1', 'payment'],
    ['stripe_enabled', '1', 'payment'],
    ['mpesa_passkey', '', 'payment'],
    ['stripe_key', '', 'payment'],
    ['facebook_connected', '0', 'social'],
    ['whatsapp_connected', '0', 'social'],
    ['smtp_host', '', 'email'],
    ['smtp_port', '587', 'email'],
    ['smtp_user', '', 'email'],
];

foreach ($settings as $setting) {
    Database::insert('settings', [
        'key' => $setting[0],
        'value' => $setting[1],
        'group_name' => $setting[2],
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
}

// Create sample marketing campaigns
$campaigns = [
    ['Holiday Season Sale', 'email', 'newsletter', 'subject' => '🎄 Holiday Deals - Up to 50% Off!', 'status' => 'sent', 'total_sent' => 1500, 'total_opened' => 890],
    ['New Arrivals Promo', 'whatsapp', 'promotion', 'status' => 'sent', 'total_sent' => 2300, 'total_opened' => 1800],
    ['Flash Sale Friday', 'facebook', 'advertisement', 'status' => 'active', 'total_sent' => 5000, 'total_opened' => 2100],
    ['Customer Appreciation', 'email', 'newsletter', 'subject' => 'Thank You for Being a Loyal Customer', 'status' => 'draft'],
];

foreach ($campaigns as $campaign) {
    Database::insert('marketing_campaigns', [
        'name' => $campaign[0],
        'platform' => $campaign[1],
        'type' => $campaign[2],
        'subject' => $campaign['subject'] ?? '',
        'status' => $campaign['status'],
        'total_sent' => $campaign['total_sent'] ?? 0,
        'total_opened' => $campaign['total_opened'] ?? 0,
        'created_by' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
}

echo "Database seeded successfully!\n";
echo "Admin login: admin@ecommerce.com / admin123\n";
echo "Manager login: manager@ecommerce.com / manager123\n";
echo "Cashier login: cashier@ecommerce.com / cashier123\n";
echo "Customer login: jane@example.com / customer123\n";
<?php
$product = $product ?? null;
$categories = Database::select("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY name");
$brands = Database::select("SELECT * FROM brands WHERE is_active = 1 ORDER BY name");
$isEdit = $product !== null;
$existingVariants = $isEdit ? Database::select("SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order, id", [$product['id']]) : [];
$hasVariants = ($product['has_variants'] ?? 0) == 1 || !empty($existingVariants);
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="font-heading font-semibold text-xl text-gray-900"><?= $isEdit ? 'Edit Product' : 'Add New Product' ?></h1>
        <a href="/admin/products" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
    </div>

    <form method="POST" action="<?= $isEdit ? '/admin/products/'.$product['id'].'/update' : '/admin/products/store' ?>" enctype="multipart/form-data" class="space-y-6">
        <?= csrf() ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Info -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-medium text-gray-900 mb-4">Basic Information</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                            <input type="text" name="name" value="<?= e($product['name'] ?? '') ?>" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Short Description</label>
                            <input type="text" name="short_description" value="<?= e($product['short_description'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Description</label>
                            <textarea name="description" rows="4" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"><?= e($product['description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6" id="pricingSection">
                    <h3 class="font-medium text-gray-900 mb-4">Pricing <span id="pricingHint" class="text-xs font-normal text-gray-400"></span></h3>
                    <div id="pricingFields" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Selling Price *</label>
                            <input type="number" name="price" step="0.01" value="<?= e($product['price'] ?? '') ?>" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cost Price</label>
                            <input type="number" name="cost_price" step="0.01" value="<?= e($product['cost_price'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Discount Price</label>
                            <input type="number" name="discount_price" step="0.01" value="<?= e($product['discount_price'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>
                    </div>
                    <p id="pricingVariantNote" class="hidden text-xs text-amber-600 mt-2"><i data-lucide="info" class="w-3 h-3 inline -mt-0.5"></i> When variants are enabled, this price is shown as "Starting from" on the storefront. Each variant can have its own price.</p>
                </div>

                <!-- Inventory -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6" id="inventorySection">
                    <h3 class="font-medium text-gray-900 mb-4">Inventory <span id="inventoryHint" class="text-xs font-normal text-gray-400"></span></h3>
                    <div id="inventoryFields" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity *</label>
                            <input type="number" name="stock_quantity" value="<?= e($product['stock_quantity'] ?? '0') ?>" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Low Stock Alert</label>
                            <input type="number" name="low_stock_threshold" value="<?= e($product['low_stock_threshold'] ?? '10') ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                            <input type="text" name="sku" value="<?= e($product['sku'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Barcode</label>
                            <input type="text" name="barcode" value="<?= e($product['barcode'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>
                    </div>
                    <p id="inventoryVariantNote" class="hidden text-xs text-amber-600 mt-2"><i data-lucide="info" class="w-3 h-3 inline -mt-0.5"></i> When variants are enabled, stock is tracked per variant. The main stock will auto-sum from variants.</p>
                </div>

                <!-- Product Variants -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6" id="variantsSection">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-medium text-gray-900">Product Variants</h3>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="has_variants" id="hasVariantsToggle" value="1" <?= $hasVariants ? 'checked' : '' ?> class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500" onchange="toggleVariants()">
                            <span class="text-sm text-gray-700">Enable Variants</span>
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 mb-4">Add variants like different sizes, colors, or weights — each with its own price and stock. If no variant price is set, the main product price is used.</p>

                    <div id="variantsContainer" class="<?= $hasVariants ? '' : 'hidden' ?>">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm" id="variantsTable">
                                <thead>
                                    <tr class="bg-gray-50 text-left">
                                        <th class="px-3 py-2 font-medium text-gray-600 rounded-tl-lg">Variant Name *</th>
                                        <th class="px-3 py-2 font-medium text-gray-600">Image</th>
                                        <th class="px-3 py-2 font-medium text-gray-600">SKU</th>
                                        <th class="px-3 py-2 font-medium text-gray-600">Price</th>
                                        <th class="px-3 py-2 font-medium text-gray-600">Cost Price</th>
                                        <th class="px-3 py-2 font-medium text-gray-600">Stock</th>
                                        <th class="px-3 py-2 font-medium text-gray-600 rounded-tr-lg w-10"></th>
                                    </tr>
                                </thead>
                                <tbody id="variantsBody">
                                </tbody>
                            </table>
                        </div>
                        <button type="button" onclick="addVariantRow()" class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-amber-600 hover:text-amber-700 transition-colors">
                            <i data-lucide="plus" class="w-4 h-4"></i> Add Variant
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="space-y-6">
                <!-- Status -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-medium text-gray-900 mb-4">Status</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Product Status</label>
                            <select name="product_status" id="productStatusSelect" onchange="updateStatusHint()" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                                <option value="active" <?= ($product['product_status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="draft" <?= ($product['product_status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="out_of_stock_returning" <?= ($product['product_status'] ?? '') === 'out_of_stock_returning' ? 'selected' : '' ?>>Out of Stock — Returning Soon</option>
                                <option value="discontinued" <?= ($product['product_status'] ?? '') === 'discontinued' ? 'selected' : '' ?>>Discontinued</option>
                                <option value="returned" <?= ($product['product_status'] ?? '') === 'returned' ? 'selected' : '' ?>>Returned</option>
                            </select>
                            <p id="statusHint" class="text-xs text-gray-400 mt-1">Product is live and available for purchase.</p>
                            <script>
                            function updateStatusHint() {
                                const hints = {
                                    'active': 'Product is live and available for purchase.',
                                    'draft': 'Product is saved but not visible to customers.',
                                    'out_of_stock_returning': 'Temporarily out of stock. Product page stays visible with "Returning Soon" badge. Customers can wishlist it.',
                                    'discontinued': 'Permanently removed from sale. Hidden from storefront.',
                                    'returned': 'Customer-returned product flagged for review.',
                                };
                                const colors = {
                                    'active': 'text-emerald-600',
                                    'draft': 'text-amber-600',
                                    'out_of_stock_returning': 'text-blue-600',
                                    'discontinued': 'text-gray-500',
                                    'returned': 'text-red-600',
                                };
                                const v = document.getElementById('productStatusSelect').value;
                                const el = document.getElementById('statusHint');
                                el.textContent = hints[v] || '';
                                el.className = 'text-xs mt-1 ' + (colors[v] || 'text-gray-400');
                            }
                            updateStatusHint();
                            </script>
                        </div>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" <?= ($product['is_active'] ?? 1) ? 'checked' : '' ?> class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                            <span class="text-sm text-gray-700">Published</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_featured" value="1" <?= ($product['is_featured'] ?? 0) ? 'checked' : '' ?> class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                            <span class="text-sm text-gray-700">Featured Product</span>
                        </label>
                    </div>
                </div>

                <!-- Organization -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-medium text-gray-900 mb-4">Organization</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select name="category_id" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                                <option value="">Select category</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= ($product['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                            <select name="brand_id" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                                <option value="">Select brand</option>
                                <?php foreach ($brands as $b): ?>
                                    <option value="<?= $b['id'] ?>" <?= ($product['brand_id'] ?? '') == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                            <input type="text" name="supplier" value="<?= e($product['supplier'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Weight (kg)</label>
                            <input type="number" name="weight" step="0.01" value="<?= e($product['weight'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>
                    </div>
                </div>

                <!-- Images -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-medium text-gray-900 mb-4">Product Images</h3>
                    <div class="border-2 border-dashed border-gray-200 rounded-lg p-6 text-center hover:border-amber-400 transition-colors">
                        <i data-lucide="upload" class="w-8 h-8 text-gray-400 mx-auto mb-2"></i>
                        <p class="text-sm text-gray-500">Click or drag to upload</p>
                        <input type="file" name="images[]" accept="image/*" multiple class="mt-2 text-sm">
                        <p class="text-xs text-gray-400 mt-2">Upload multiple images. First image will be the main product photo.</p>
                    </div>
                    <script>
                    document.querySelector('input[name="images[]"]').addEventListener('change', function(e) {
                        const container = document.getElementById('imagePreviewContainer');
                        if (!container) {
                            const div = document.createElement('div');
                            div.id = 'imagePreviewContainer';
                            div.className = 'mt-3 grid grid-cols-4 gap-2';
                            this.closest('.bg-white').querySelector('.border-dashed').after(div);
                        }
                        const previewContainer = document.getElementById('imagePreviewContainer');
                        previewContainer.innerHTML = '';
                        Array.from(this.files).forEach(file => {
                            if (!file.type.startsWith('image/')) return;
                            const reader = new FileReader();
                            reader.onload = function(ev) {
                                const img = document.createElement('div');
                                img.className = 'relative rounded-lg overflow-hidden border border-gray-200';
                                img.innerHTML = '<img src="' + ev.target.result + '" class="w-full h-20 object-cover"><button type="button" onclick="this.parentElement.remove()" class="absolute top-1 right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center">&times;</button>';
                                previewContainer.appendChild(img);
                            };
                            reader.readAsDataURL(file);
                        });
                    });
                    </script>
                    <?php if ($isEdit): 
                        $existingImages = Database::select("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order", [$product['id']]);
                        if (!empty($existingImages)):
                    ?>
                    <div class="mt-3 grid grid-cols-3 gap-2" id="existingImages">
                        <?php foreach ($existingImages as $ei): ?>
                        <div class="relative group">
                            <img src="<?= e($ei['image_path']) ?>" onclick="window.open(this.src, '_blank')" class="w-full h-20 object-cover rounded-lg border border-gray-200 cursor-pointer">
                            <?php if ($ei['is_primary']): ?><span class="absolute top-1 left-1 bg-amber-600 text-white text-[9px] px-1.5 py-0.5 rounded font-medium">Main</span><?php endif; ?>
                            <button type="button" onclick="deleteImage(<?= $product['id'] ?>, <?= $ei['id'] ?>, this)" class="absolute top-1 right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">&times;</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <script>
                    function deleteImage(productId, imageId, btn) {
                        if (!confirm('Delete this image?')) return;
                        const formData = new FormData();
                        formData.append('_token', document.querySelector('input[name="_token"]')?.value || '');
                        fetch('/admin/products/' + productId + '/delete-image/' + imageId, { method: 'POST', body: formData })
                            .then(r => r.json()).then(data => {
                                if (data.success) btn.closest('.relative').remove();
                            });
                    }
                    </script>
                    <?php endif; endif; ?>
                </div>

                <!-- Submit -->
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-amber-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">
                        <?= $isEdit ? 'Update Product' : 'Create Product' ?>
                    </button>
                    <a href="/admin/products" class="px-4 py-2.5 border border-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// ── Variant Management ──
var variantRowId = 0;

function toggleVariants() {
    var on = document.getElementById('hasVariantsToggle').checked;
    document.getElementById('variantsContainer').classList.toggle('hidden', !on);
    document.getElementById('pricingVariantNote').classList.toggle('hidden', !on);
    document.getElementById('inventoryVariantNote').classList.toggle('hidden', !on);
    document.getElementById('pricingHint').textContent = on ? '(starting from)' : '';
    document.getElementById('inventoryHint').textContent = on ? '(per variant)' : '';
    if (on && document.getElementById('variantsBody').children.length === 0) {
        addVariantRow();
    }
}

function addVariantRow(name, sku, price, costPrice, stock, id, image) {
    id = id || '';
    name = name || '';
    sku = sku || '';
    price = price || '';
    costPrice = costPrice || '';
    stock = stock || 0;
    image = image || '';
    variantRowId++;
    var rid = variantRowId;
    var tr = document.createElement('tr');
    tr.className = 'border-b border-gray-100 hover:bg-gray-50/50';
    tr.dataset.variantId = id;
    var imgHtml = '';
    if (image) {
        imgHtml = '<div class="variant-img-preview relative" id="vimg_'+rid+'">'
            + '<img src="'+image+'" class="w-10 h-10 rounded-lg object-cover border border-gray-200">'
            + '<button type="button" onclick="removeVariantImage('+rid+')" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white rounded-full text-[10px] flex items-center justify-center leading-none">&times;</button>'
            + '</div>';
    } else {
        imgHtml = '<div class="variant-img-preview" id="vimg_'+rid+'">'
            + '<label class="cursor-pointer inline-flex items-center justify-center w-10 h-10 rounded-lg border-2 border-dashed border-gray-300 hover:border-amber-400 text-gray-400 hover:text-amber-500 transition-colors">'
            + '<input type="file" name="variant_image[]" accept="image/*" class="hidden" onchange="previewVariantImage(this,'+rid+')">'
            + '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>'
            + '</label></div>';
    }
    tr.innerHTML = '<td class="px-3 py-2"><input type="text" name="variant_name[]" value="' + name.replace(/"/g, '&quot;') + '" placeholder="e.g. Small, Red, 500g" class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:outline-none focus:ring-1 focus:ring-amber-500 min-w-[120px]"></td>'
        + '<td class="px-3 py-2">' + imgHtml + '<input type="hidden" name="variant_existing_image[]" value="' + image + '"></td>'
        + '<td class="px-3 py-2"><input type="text" name="variant_sku[]" value="' + sku.replace(/"/g, '&quot;') + '" placeholder="Auto" class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:outline-none focus:ring-1 focus:ring-amber-500 min-w-[100px]"></td>'
        + '<td class="px-3 py-2"><input type="number" name="variant_price[]" value="' + price + '" step="0.01" placeholder="0.00" class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:outline-none focus:ring-1 focus:ring-amber-500 min-w-[90px]"></td>'
        + '<td class="px-3 py-2"><input type="number" name="variant_cost_price[]" value="' + costPrice + '" step="0.01" placeholder="0.00" class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:outline-none focus:ring-1 focus:ring-amber-500 min-w-[90px]"></td>'
        + '<td class="px-3 py-2"><input type="number" name="variant_stock[]" value="' + stock + '" min="0" class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:outline-none focus:ring-1 focus:ring-amber-500 min-w-[70px]"></td>'
        + '<td class="px-3 py-2"><button type="button" onclick="this.closest(\'tr\').remove()" class="p-1 text-gray-400 hover:text-red-500 rounded hover:bg-red-50 transition-colors"><i data-lucide="x" class="w-4 h-4"></i></button></td>';
    document.getElementById('variantsBody').appendChild(tr);
    lucide.createIcons();
}

function previewVariantImage(input, rid) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var container = document.getElementById('vimg_'+rid);
            container.innerHTML = '<img src="'+e.target.result+'" class="w-10 h-10 rounded-lg object-cover border border-gray-200">'
                + '<button type="button" onclick="removeVariantImage('+rid+')" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white rounded-full text-[10px] flex items-center justify-center leading-none">&times;</button>';
            container.classList.add('relative');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeVariantImage(rid) {
    var container = document.getElementById('vimg_'+rid);
    container.classList.remove('relative');
    container.innerHTML = '<label class="cursor-pointer inline-flex items-center justify-center w-10 h-10 rounded-lg border-2 border-dashed border-gray-300 hover:border-amber-400 text-gray-400 hover:text-amber-500 transition-colors">'
        + '<input type="file" name="variant_image[]" accept="image/*" class="hidden" onchange="previewVariantImage(this,'+rid+')">'
        + '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>'
        + '</label>';
    // Also clear the hidden field for existing image
    var hiddenInput = container.closest('td').querySelector('input[name="variant_existing_image[]"]');
    if (hiddenInput) hiddenInput.value = '';
}

// Load existing variants on edit
<?php if (!empty($existingVariants)): ?>
<?php foreach ($existingVariants as $v): ?>
addVariantRow("<?= e($v['variant_name']) ?>", "<?= e($v['sku'] ?? '') ?>", "<?= e($v['price'] ?? '') ?>", "<?= e($v['cost_price'] ?? '') ?>", "<?= (int)($v['stock_quantity'] ?? 0) ?>", "<?= $v['id'] ?>", "<?= e($v['image'] ?? '') ?>");
<?php endforeach; ?>
<?php endif; ?>

// Init toggle state
toggleVariants();
</script>
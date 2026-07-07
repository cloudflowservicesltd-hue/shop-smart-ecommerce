<?php
$publerConnected = false;
$accounts = [];
$products = [];
$publerKey = Database::selectOne("SELECT value FROM settings WHERE `key` = 'publer_api_key'")['value'] ?? '';

if (!empty($publerKey) && class_exists('PublerAPI')) {
    $publerConnected = true;
    $accounts = PublerAPI::getAccounts();
    $products = PublerAPI::getProductsForMarketing(50);
}
$storeName = Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_name'")['value'] ?? 'ShopSmart';
$siteUrl = Database::selectOne("SELECT value FROM settings WHERE `key` = 'site_url'")['value'] ?? '';
$currencySymbol = Database::selectOne("SELECT value FROM settings WHERE `key` = 'currency_symbol'")['value'] ?? 'KSh';
$breadcrumbs = [['Marketing', '/admin/marketing/social'], ['Product Publishing', '']];
?>

<style>
.product-card-select{transition:all .15s}
.product-card-select:hover{border-color:#d97706;background:#fffbeb}
.product-card-select.selected{border-color:#d97706;background:#fffbeb;box-shadow:0 0 0 2px rgba(217,119,6,.3)}
</style>

<div class="space-y-6">
    <!-- Breadcrumbs -->
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="/admin/marketing/social" class="hover:text-amber-600">Marketing</a>
        <i data-lucide="chevron-right" class="w-3 h-3"></i>
        <span class="text-gray-900 font-medium">Product Publishing</span>
    </div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <i data-lucide="megaphone" class="w-6 h-6 text-amber-600"></i> Product Publishing
            </h1>
            <p class="text-gray-500 text-sm mt-1">Create social media posts from your products and publish via Publer</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full <?= $publerConnected ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                <span class="w-1.5 h-1.5 rounded-full <?= $publerConnected ? 'bg-green-500' : 'bg-gray-400' ?>"></span>
                Publer <?= $publerConnected ? 'Connected' : 'Not Connected' ?>
            </span>
        </div>
    </div>

    <?php if (!$publerConnected): ?>
    <!-- Connect Publer -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-2">Connect Publer API</h3>
        <p class="text-sm text-gray-500 mb-4">Enter your Publer API key to start publishing products to social media. Get your key from <a href="https://publer.io/settings/api" target="_blank" class="text-amber-600 underline">Publer API Settings</a>.</p>
        <form method="POST" action="/marketing/social/connect-publer" class="flex gap-3 max-w-lg">
            <input type="text" name="publer_api_key" placeholder="Enter Publer API Key" value="<?= e($publerKey) ?>"
                   class="flex-1 px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
            <button type="submit" class="bg-amber-600 text-white font-medium px-6 py-2.5 rounded-lg hover:bg-amber-700 transition-colors text-sm">
                Connect
            </button>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($publerConnected): ?>

    <!-- Step 1: Select Products -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-1 flex items-center gap-2">
            <span class="w-6 h-6 bg-amber-100 text-amber-700 rounded-full flex items-center justify-center text-xs font-bold">1</span>
            Select Products
        </h3>
        <p class="text-sm text-gray-500 mb-4 ml-8">Choose products to create social media posts for</p>

        <!-- Search -->
        <div class="mb-4">
            <input type="text" id="productSearch" placeholder="Search products by name..."
                   class="w-full sm:w-80 px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
        </div>

        <div id="productGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-80 overflow-y-auto pr-1">
            <?php foreach ($products as $p): ?>
            <label class="product-card-select flex items-center gap-3 p-3 border border-gray-200 rounded-xl cursor-pointer" data-name="<?= e(strtolower($p['name'])) ?>">
                <input type="checkbox" name="selected_products[]" value="<?= $p['id'] ?>" class="w-4 h-4 text-amber-600 rounded border-gray-300 focus:ring-amber-500 product-checkbox">
                <img src="<?= e($p['image'] ?? '/uploads/no-image-sm.jpg') ?>" class="w-12 h-12 rounded-lg object-cover bg-gray-50 shrink-0">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate"><?= e($p['name']) ?></p>
                    <p class="text-xs text-gray-400"><?= e($p['category_name'] ?? '') ?> &middot; <?= $currencySymbol ?><?= number_format((float)($p['sale_price'] ?? $p['price']), 0) ?></p>
                </div>
            </label>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
            <p class="text-sm text-gray-400 col-span-3 text-center py-8">No active products found.</p>
            <?php endif; ?>
        </div>
        <div class="mt-3 flex items-center gap-3">
            <button onclick="toggleAllProducts()" class="text-xs text-amber-600 hover:text-amber-700 font-medium">Select All</button>
            <span id="selectedCount" class="text-xs text-gray-400">0 selected</span>
        </div>
    </div>

    <!-- Step 2: Compose Post -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-1 flex items-center gap-2">
            <span class="w-6 h-6 bg-amber-100 text-amber-700 rounded-full flex items-center justify-center text-xs font-bold">2</span>
            Compose Post
        </h3>
        <p class="text-sm text-gray-500 mb-4 ml-8">Customize the post content or use the auto-generated template</p>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Post Text</label>
                <textarea id="postText" rows="5" class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 resize-y" placeholder="Select products above to auto-generate post text..."></textarea>
                <div class="flex items-center gap-2 mt-1">
                    <button onclick="generatePostText()" class="text-xs text-amber-600 hover:text-amber-700 font-medium flex items-center gap-1">
                        <i data-lucide="sparkles" class="w-3 h-3"></i> Auto-Generate from Products
                    </button>
                    <span id="charCount" class="text-xs text-gray-400"></span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Product Link</label>
                <input type="text" id="postLink" value="<?= e($siteUrl) ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm" placeholder="https://...">
            </div>
        </div>
    </div>

    <!-- Step 3: Choose Platforms -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-1 flex items-center gap-2">
            <span class="w-6 h-6 bg-amber-100 text-amber-700 rounded-full flex items-center justify-center text-xs font-bold">3</span>
            Choose Platforms
        </h3>
        <p class="text-sm text-gray-500 mb-4 ml-8">Select social media accounts to publish to</p>

        <?php if (empty($accounts)): ?>
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
            <p class="text-sm text-amber-700"><i data-lucide="alert-circle" class="w-4 h-4 inline mr-1"></i> No social accounts found. Connect accounts in <a href="/admin/marketing/social" class="underline">Social Publishing</a> first.</p>
        </div>
        <?php else: ?>
        <div id="accountsList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <?php foreach ($accounts as $acc): ?>
            <?php
                $platform = $acc['platform'] ?? $acc['type'] ?? 'unknown';
                $name = $acc['name'] ?? $acc['username'] ?? $acc['platformName'] ?? ucfirst($platform);
                $accId = $acc['id'] ?? $acc['_id'] ?? '';
                $avatar = $acc['avatar'] ?? $acc['picture'] ?? $acc['profilePic'] ?? '';
            ?>
            <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl cursor-pointer hover:border-amber-300 hover:bg-amber-50/30 transition-colors">
                <input type="checkbox" name="platform_account" value="<?= e(json_encode(['id' => $accId, 'platform' => $platform, 'name' => $name])) ?>" class="w-4 h-4 text-amber-600 rounded border-gray-300 focus:ring-amber-500">
                <?php if ($avatar): ?>
                <img src="<?= e($avatar) ?>" class="w-8 h-8 rounded-full object-cover">
                <?php else: ?>
                <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-xs font-bold text-gray-500"><?= strtoupper(substr($name, 0, 1)) ?></div>
                <?php endif; ?>
                <div>
                    <p class="text-sm font-medium text-gray-900"><?= e($name) ?></p>
                    <p class="text-xs text-gray-400 capitalize"><?= e($platform) ?></p>
                </div>
            </label>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Publish Button -->
    <div class="flex items-center justify-between">
        <button onclick="previewPost()" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 font-medium px-6 py-3 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
            <i data-lucide="eye" class="w-4 h-4"></i> Preview
        </button>
        <button onclick="publishProducts()" id="publishBtn" class="inline-flex items-center gap-2 bg-amber-600 text-white font-semibold px-8 py-3.5 rounded-xl hover:bg-amber-700 transition-colors shadow-lg shadow-amber-600/20">
            <i data-lucide="send" class="w-4 h-4"></i> Publish Now
        </button>
    </div>

    <!-- Status -->
    <div id="publishStatus" class="hidden"></div>

    <!-- Preview Modal -->
    <div id="previewModal" class="hidden fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="document.getElementById('previewModal').classList.add('hidden')"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6">
                <button onclick="document.getElementById('previewModal').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
                <h3 class="font-semibold text-gray-900 mb-4">Post Preview</h3>
                <div id="previewContent" class="bg-gray-50 rounded-xl p-4 text-sm whitespace-pre-wrap text-gray-800 max-h-80 overflow-y-auto"></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Product search
document.getElementById('productSearch')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.product-card-select').forEach(card => {
        card.style.display = card.dataset.name.includes(q) ? '' : 'none';
    });
});

// Select all toggle
let allSelected = false;
function toggleAllProducts() {
    allSelected = !allSelected;
    document.querySelectorAll('.product-checkbox').forEach(cb => {
        cb.checked = allSelected;
        cb.closest('.product-card-select').classList.toggle('selected', allSelected);
    });
    updateSelectedCount();
}

// Update count
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('product-checkbox')) {
        e.target.closest('.product-card-select').classList.toggle('selected', e.target.checked);
        updateSelectedCount();
    }
});
function updateSelectedCount() {
    const count = document.querySelectorAll('.product-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = count + ' selected';
}

// Auto-generate post text
const productData = <?= json_encode($products ?? []) ?>;
const currencySym = '<?= e($currencySymbol) ?>';
const storeNm = '<?= e($storeName) ?>';
const siteUrlVal = '<?= e($siteUrl) ?>';

function generatePostText() {
    const checked = document.querySelectorAll('.product-checkbox:checked');
    if (checked.length === 0) { alert('Select at least one product'); return; }

    let text = '';
    checked.forEach((cb, i) => {
        const p = productData.find(x => String(x.id) === cb.value);
        if (!p) return;
        if (i > 0) text += '\n\n';
        const price = p.sale_price || p.price;
        text += '🔥 ' + p.name + '\n💰 ' + currencySym + ' ' + Number(price).toLocaleString();
        if (p.category_name) text += '\n📂 ' + p.category_name;
        text += '\n🛒 ' + siteUrlVal + '/product/' + (p.slug || p.id);
    });
    text += '\n\n#' + storeNm.replace(/\s/g, '') + ' #Sale #Deals #ShopNow';

    document.getElementById('postText').value = text;
    updateCharCount();
}

function updateCharCount() {
    const len = document.getElementById('postText').value.length;
    const el = document.getElementById('charCount');
    if (el) el.textContent = len + ' characters';
}
document.getElementById('postText')?.addEventListener('input', updateCharCount);

// Preview
function previewPost() {
    const text = document.getElementById('postText').value;
    if (!text.trim()) { alert('Write some post content first'); return; }
    document.getElementById('previewContent').textContent = text;
    document.getElementById('previewModal').classList.remove('hidden');
}

// Publish
async function publishProducts() {
    const text = document.getElementById('postText').value.trim();
    if (!text) { alert('Write post content'); return; }

    const accounts = [];
    document.querySelectorAll('input[name="platform_account"]:checked').forEach(cb => {
        try { accounts.push(JSON.parse(cb.value)); } catch(e) {}
    });
    if (accounts.length === 0) { alert('Select at least one social account'); return; }

    const selectedProducts = [];
    document.querySelectorAll('.product-checkbox:checked').forEach(cb => {
        const p = productData.find(x => String(x.id) === cb.value);
        if (p) selectedProducts.push(p);
    });

    const mediaUrls = selectedProducts.map(p => p.image).filter(Boolean);
    const platforms = accounts.map(a => a.platform);
    const accountIds = accounts.map(a => a.id);
    const link = document.getElementById('postLink').value;

    const btn = document.getElementById('publishBtn');
    const status = document.getElementById('publishStatus');
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Publishing...';
    status.classList.remove('hidden');
    status.innerHTML = '<div class="bg-amber-50 border border-amber-200 rounded-xl p-4"><div class="flex items-center gap-2 text-amber-700"><i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Sending to Publer...</div></div>';

    try {
        const resp = await fetch('/marketing/social/publish-product', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                text, media_urls: mediaUrls, platforms, account_ids: accountIds, link
            })
        });
        const data = await resp.json();

        if (data.success) {
            status.innerHTML = '<div class="bg-green-50 border border-green-200 rounded-xl p-4"><div class="flex items-center gap-2 text-green-700 font-medium"><i data-lucide="check-circle" class="w-5 h-5"></i> Published successfully!</div><p class="text-sm text-green-600 mt-1">' + (data.message || 'Post sent to ' + platforms.join(', ')) + '</p></div>';
        } else {
            status.innerHTML = '<div class="bg-red-50 border border-red-200 rounded-xl p-4"><div class="flex items-center gap-2 text-red-700 font-medium"><i data-lucide="alert-circle" class="w-5 h-5"></i> ' + (data.error || 'Failed to publish') + '</div></div>';
        }
    } catch (e) {
        status.innerHTML = '<div class="bg-red-50 border border-red-200 rounded-xl p-4"><div class="flex items-center gap-2 text-red-700"><i data-lucide="alert-circle" class="w-5 h-5"></i> ' + e.message + '</div></div>';
    }

    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="send" class="w-4 h-4"></i> Publish Now';
}
</script>
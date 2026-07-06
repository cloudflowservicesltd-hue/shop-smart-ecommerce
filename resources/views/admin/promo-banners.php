<?php
if (!isset($banners)) {
    $banners = Database::select("SELECT * FROM promo_banners ORDER BY position ASC, id DESC");
}
$editBanner = $editBanner ?? null;

$gradientOptions = [
    'from-amber-500 to-orange-600' => 'Amber → Orange',
    'from-orange-500 to-red-600' => 'Orange → Red',
    'from-teal-500 to-amber-600' => 'Teal → Emerald',
    'from-rose-500 to-pink-600' => 'Rose → Pink',
    'from-violet-500 to-purple-600' => 'Violet → Purple',
];

$iconOptions = [
    'zap' => 'Zap',
    'sparkles' => 'Sparkles',
    'gift' => 'Gift',
    'percent' => 'Percent',
    'tag' => 'Tag',
    'clock' => 'Clock',
    'star' => 'Star',
    'rocket' => 'Rocket',
];
?>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-heading font-semibold text-xl text-gray-900">Promo Banners</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage homepage promotional banners and hero sections.</p>
        </div>
        <button onclick="openFormModal()" class="inline-flex items-center gap-2 bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors shadow-sm">
            <i data-lucide="plus" class="w-4 h-4"></i> Add New Banner
        </button>
    </div>

    <!-- Banner Cards Grid -->
    <?php if (empty($banners)): ?>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-16 text-center">
        <div class="mx-auto w-16 h-16 bg-amber-50 rounded-2xl flex items-center justify-center mb-4">
            <i data-lucide="image" class="w-8 h-8 text-amber-400"></i>
        </div>
        <h3 class="font-medium text-gray-900 mb-1">No Banners Yet</h3>
        <p class="text-sm text-gray-500 mb-4">Create your first promotional banner to showcase deals on the storefront.</p>
        <button onclick="openFormModal()" class="inline-flex items-center gap-2 bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> Add New Banner
        </button>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <?php foreach ($banners as $banner): ?>
        <?php
            $gradient = $banner['bg_gradient'] ?? 'from-amber-500 to-orange-600';
            $icon = $banner['icon'] ?? 'zap';
            $isActive = ($banner['is_active'] ?? 0) == 1;
        ?>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-shadow group">
            <!-- Gradient Preview -->
            <div class="bg-gradient-to-r <?= e($gradient) ?> h-36 relative flex items-center px-6">
                <div class="text-white relative z-10">
                    <div class="flex items-center gap-2 mb-1.5">
                        <i data-lucide="<?= e($icon) ?>" class="w-5 h-5 opacity-90"></i>
                        <?php if ($isActive): ?>
                        <span class="text-[10px] font-semibold uppercase tracking-wider bg-white/25 backdrop-blur-sm px-2 py-0.5 rounded-full">Active</span>
                        <?php else: ?>
                        <span class="text-[10px] font-semibold uppercase tracking-wider bg-black/20 backdrop-blur-sm px-2 py-0.5 rounded-full">Inactive</span>
                        <?php endif; ?>
                    </div>
                    <h3 class="text-lg font-semibold leading-tight drop-shadow-sm"><?= e($banner['title']) ?></h3>
                    <?php if ($banner['subtitle']): ?>
                    <p class="text-sm text-white/85 mt-1 max-w-xs truncate"><?= e($banner['subtitle']) ?></p>
                    <?php endif; ?>
                    <?php if ($banner['cta_text']): ?>
                    <span class="inline-block mt-3 text-xs font-semibold bg-white text-gray-900 px-3 py-1 rounded-full shadow-sm"><?= e($banner['cta_text']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="absolute right-4 bottom-4 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button onclick='openEditModal(<?= json_encode($banner, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="p-2 bg-white/20 hover:bg-white/40 backdrop-blur-sm rounded-lg text-white transition-colors" title="Edit">
                        <i data-lucide="pencil" class="w-4 h-4"></i>
                    </button>
                    <button onclick="openDeleteModal(<?= $banner['id'] ?>, '<?= e(addslashes($banner['title'])) ?>')" class="p-2 bg-white/20 hover:bg-red-500/80 backdrop-blur-sm rounded-lg text-white transition-colors" title="Delete">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
                <!-- Position Badge -->
                <span class="absolute top-3 right-3 bg-black/20 backdrop-blur-sm text-white text-[11px] font-mono font-medium px-2 py-0.5 rounded-md">#<?= (int)$banner['position'] ?></span>
            </div>
            <!-- Card Footer Info -->
            <div class="px-4 py-3 flex items-center justify-between border-t border-gray-50">
                <div class="flex items-center gap-3 text-xs text-gray-500">
                    <span class="inline-flex items-center gap-1">
                        <i data-lucide="palette" class="w-3.5 h-3.5"></i>
                        <?= e($gradientOptions[$gradient] ?? $gradient) ?>
                    </span>
                    <?php if ($banner['cta_link']): ?>
                    <span class="inline-flex items-center gap-1 truncate max-w-[160px]" title="<?= e($banner['cta_link']) ?>">
                        <i data-lucide="link" class="w-3.5 h-3.5 shrink-0"></i>
                        <?= e($banner['cta_link']) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-medium <?= $isActive ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Add/Edit Modal -->
    <div id="formModal" class="hidden fixed inset-0 z-50 flex items-start justify-center pt-[5vh] p-4">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeFormModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white rounded-t-2xl border-b border-gray-100 px-6 py-4 flex items-center justify-between z-10">
                <h3 id="formModalTitle" class="font-heading font-semibold text-lg text-gray-900">Add New Banner</h3>
                <button onclick="closeFormModal()" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="POST" id="bannerForm" class="p-6 space-y-5">
                <input type="hidden" name="id" id="formId">
                <?= csrf() ?>

                <!-- Live Preview -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1.5 uppercase tracking-wider">Preview</label>
                    <div id="livePreview" class="bg-gradient-to-r from-amber-500 to-orange-600 h-32 rounded-xl flex items-center px-5 overflow-hidden">
                        <div class="text-white relative z-10">
                            <div class="flex items-center gap-2 mb-1">
                                <i data-lucide="zap" class="w-4 h-4 opacity-90" id="previewIcon"></i>
                                <span class="text-[10px] font-semibold uppercase tracking-wider bg-white/25 px-2 py-0.5 rounded-full">Active</span>
                            </div>
                            <h4 id="previewTitle" class="text-base font-semibold leading-tight drop-shadow-sm">Banner Title</h4>
                            <p id="previewSubtitle" class="text-sm text-white/85 mt-0.5">Subtitle text here</p>
                            <span id="previewCta" class="inline-block mt-2 text-xs font-semibold bg-white text-gray-900 px-3 py-1 rounded-full shadow-sm">CTA Button</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Title -->
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" id="formTitle" placeholder="e.g. Flash Sale - Up to 50% Off" required oninput="updatePreview()" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>

                    <!-- Subtitle -->
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
                        <textarea name="subtitle" id="formSubtitle" rows="2" placeholder="A short description for the banner" oninput="updatePreview()" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 resize-none"></textarea>
                    </div>

                    <!-- CTA Text -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CTA Text</label>
                        <input type="text" name="cta_text" id="formCtaText" placeholder="e.g. Shop Now" oninput="updatePreview()" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>

                    <!-- CTA Link -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CTA Link</label>
                        <input type="text" name="cta_link" id="formCtaLink" placeholder="/collections/sale" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>

                    <!-- Gradient -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Background Gradient</label>
                        <select name="bg_gradient" id="formGradient" onchange="updatePreview()" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-white">
                            <?php foreach ($gradientOptions as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= (isset($editBanner) && $editBanner['bg_gradient'] === $value) ? 'selected' : (!$editBanner && $value === 'from-amber-500 to-orange-600' ? 'selected' : '') ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Icon -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Icon</label>
                        <select name="icon" id="formIcon" onchange="updatePreview()" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-white">
                            <?php foreach ($iconOptions as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= (isset($editBanner) && $editBanner['icon'] === $value) ? 'selected' : (!$editBanner && $value === 'zap' ? 'selected' : '') ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Position -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                        <input type="number" name="position" id="formPosition" min="0" value="<?= isset($editBanner) ? (int)$editBanner['position'] : 0 ?>" placeholder="0" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        <p class="text-xs text-gray-400 mt-1">Lower numbers appear first.</p>
                    </div>

                    <!-- Active -->
                    <div class="flex items-end pb-1">
                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                            <input type="checkbox" name="is_active" value="1" id="formIsActive" <?= (isset($editBanner) && $editBanner['is_active'] == 1) || !isset($editBanner) ? 'checked' : '' ?> class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500" onchange="updatePreview()">
                            <span class="text-sm text-gray-700">Active</span>
                        </label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeFormModal()" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Cancel</button>
                    <button type="submit" id="formSubmitBtn" class="px-5 py-2.5 text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition-colors shadow-sm">
                        Save Banner
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-sm p-6 space-y-4 text-center">
            <div class="mx-auto w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <i data-lucide="alert-triangle" class="w-6 h-6 text-red-600"></i>
            </div>
            <h3 class="font-heading font-semibold text-lg text-gray-900">Delete Banner</h3>
            <p class="text-sm text-gray-500">Are you sure you want to delete <strong id="deleteBannerName" class="text-gray-900"></strong>? This action cannot be undone.</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="id" id="deleteId">
                <?= csrf() ?>
                <div class="flex items-center justify-center gap-3 pt-2">
                    <button type="button" onclick="closeDeleteModal()" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Cancel</button>
                    <button type="submit" class="px-4 py-2.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updatePreview() {
    var preview = document.getElementById('livePreview');
    var title = document.getElementById('formTitle').value || 'Banner Title';
    var subtitle = document.getElementById('formSubtitle').value || 'Subtitle text here';
    var ctaText = document.getElementById('formCtaText').value || 'CTA Button';
    var gradient = document.getElementById('formGradient').value;
    var icon = document.getElementById('formIcon').value;
    var isActive = document.getElementById('formIsActive').checked;

    preview.className = 'bg-gradient-to-r ' + gradient + ' h-32 rounded-xl flex items-center px-5 overflow-hidden';
    document.getElementById('previewTitle').textContent = title;
    document.getElementById('previewSubtitle').textContent = subtitle;
    document.getElementById('previewCta').textContent = ctaText;
    document.getElementById('previewCta').style.display = ctaText.trim() ? 'inline-block' : 'none';

    var iconEl = document.getElementById('previewIcon');
    iconEl.setAttribute('data-lucide', icon);
    lucide.createIcons();

    var statusBadge = preview.querySelector('span');
    if (statusBadge) {
        statusBadge.textContent = isActive ? 'Active' : 'Inactive';
        statusBadge.className = 'text-[10px] font-semibold uppercase tracking-wider ' + (isActive ? 'bg-white/25' : 'bg-black/20') + ' px-2 py-0.5 rounded-full';
    }
}

function openFormModal() {
    document.getElementById('formId').value = '';
    document.getElementById('formTitle').value = '';
    document.getElementById('formSubtitle').value = '';
    document.getElementById('formCtaText').value = '';
    document.getElementById('formCtaLink').value = '';
    document.getElementById('formGradient').value = 'from-amber-500 to-orange-600';
    document.getElementById('formIcon').value = 'zap';
    document.getElementById('formPosition').value = '0';
    document.getElementById('formIsActive').checked = true;
    document.getElementById('formModalTitle').textContent = 'Add New Banner';
    document.getElementById('formSubmitBtn').textContent = 'Save Banner';
    document.getElementById('bannerForm').action = '/admin/promo-banners/store';
    updatePreview();
    document.getElementById('formModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    lucide.createIcons();
}

function openEditModal(banner) {
    document.getElementById('formId').value = banner.id;
    document.getElementById('formTitle').value = banner.title || '';
    document.getElementById('formSubtitle').value = banner.subtitle || '';
    document.getElementById('formCtaText').value = banner.cta_text || '';
    document.getElementById('formCtaLink').value = banner.cta_link || '';
    document.getElementById('formGradient').value = banner.bg_gradient || 'from-amber-500 to-orange-600';
    document.getElementById('formIcon').value = banner.icon || 'zap';
    document.getElementById('formPosition').value = banner.position || 0;
    document.getElementById('formIsActive').checked = banner.is_active == 1;
    document.getElementById('formModalTitle').textContent = 'Edit Banner';
    document.getElementById('formSubmitBtn').textContent = 'Update Banner';
    document.getElementById('bannerForm').action = '/admin/promo-banners/' + banner.id + '/update';
    updatePreview();
    document.getElementById('formModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    lucide.createIcons();
}

function closeFormModal() {
    document.getElementById('formModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function openDeleteModal(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteBannerName').textContent = name;
    document.getElementById('deleteForm').action = '/admin/promo-banners/' + id + '/delete';
    document.getElementById('deleteModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    lucide.createIcons();
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.body.style.overflow = '';
}

<?php if (isset($editBanner) && $editBanner): ?>
document.addEventListener('DOMContentLoaded', function() {
    openEditModal(<?= json_encode($editBanner, JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
});
<?php endif; ?>

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeFormModal();
        closeDeleteModal();
    }
});
</script>
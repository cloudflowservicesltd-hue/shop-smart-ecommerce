<?php if (empty($slides)): $slides = Database::select("SELECT * FROM hero_slides ORDER BY sort_order ASC"); endif; ?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-heading font-semibold text-xl text-gray-900">Hero Slides</h1>
            <p class="text-sm text-gray-500 mt-1">Manage the homepage hero carousel slides</p>
        </div>
        <button onclick="openSlideForm()" class="inline-flex items-center gap-2 bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Slide
        </button>
    </div>

    <!-- Empty State -->
    <?php if (empty($slides)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <i data-lucide="image" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
        <h3 class="font-medium text-gray-900 mb-1">No Hero Slides</h3>
        <p class="text-sm text-gray-500 mb-4">Create your first hero slide to display on the homepage.</p>
        <button onclick="openSlideForm()" class="inline-flex items-center gap-2 bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-700">
            <i data-lucide="plus" class="w-4 h-4"></i> Add First Slide
        </button>
    </div>
    <?php else: ?>
    <!-- Slides List -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Preview</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">Subtitle</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 uppercase">Order</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($slides as $slide): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <?php if (!empty($slide['image_url'])): ?>
                            <img src="<?= e($slide['image_url']) ?>" alt="" class="w-16 h-10 rounded-lg object-cover border border-gray-200">
                            <?php else: ?>
                            <div class="w-16 h-10 rounded-lg bg-gradient-to-r <?= e($slide['bg_gradient'] ?? 'from-amber-700 to-orange-800') ?>"></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900 text-sm"><?= e($slide['title']) ?></p>
                            <p class="text-xs text-gray-400 mt-0.5 truncate max-w-[200px]"><?= e($slide['cta_text'] ?? 'Shop Now') ?> &rarr; <?= e($slide['cta_link'] ?? '/products') ?></p>
                        </td>
                        <td class="px-4 py-3 hidden sm:table-cell">
                            <p class="text-sm text-gray-600 truncate max-w-[200px]"><?= e($slide['subtitle'] ?? '-') ?></p>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <?php if ($slide['sort_order'] > 1): ?>
                                <a href="?action=move_up&id=<?= $slide['id'] ?>" class="p-1 text-gray-400 hover:text-amber-600 transition-colors"><i data-lucide="chevron-up" class="w-4 h-4"></i></a>
                                <?php endif; ?>
                                <span class="text-sm font-medium text-gray-700 w-6">#<?= $slide['sort_order'] ?></span>
                                <a href="?action=move_down&id=<?= $slide['id'] ?>" class="p-1 text-gray-400 hover:text-amber-600 transition-colors"><i data-lucide="chevron-down" class="w-4 h-4"></i></a>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($slide['is_active']): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Active
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span> Inactive
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button onclick="openSlideForm(this)" data-slide='<?= htmlspecialchars(json_encode($slide), ENT_QUOTES, 'UTF-8') ?>' class="p-1.5 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Edit">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>
                                <button onclick="confirmDelete(<?= $slide['id'] ?>, '<?= e(addslashes($slide['title'])) ?>')" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Modal -->
<div id="slideModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeSlideForm()"></div>
    <div class="absolute inset-y-0 right-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h2 id="slideModalTitle" class="font-heading font-semibold text-lg text-gray-900">Add Hero Slide</h2>
            <button onclick="closeSlideForm()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
            <form id="slideForm" method="POST" action="/admin/hero-slides<?= !empty($editSlide) ? '/edit/' . $editSlide['id'] : '' ?>" enctype="multipart/form-data" class="space-y-5">
                <?= csrf() ?>
                <input type="hidden" name="id" id="slideId" value="<?= $editSlide['id'] ?? '' ?>">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="slideTitle" required value="<?= e($editSlide['title'] ?? '') ?>" 
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle / Badge Text</label>
                    <input type="text" name="subtitle" id="slideSubtitle" value="<?= e($editSlide['subtitle'] ?? '') ?>" 
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                           placeholder="e.g. Latest Gadgets & Devices">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="slideDescription" rows="3" 
                              class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                              placeholder="Brief description shown on the slide"><?= e($editSlide['description'] ?? '') ?></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CTA Button Text</label>
                        <input type="text" name="cta_text" id="slideCtaText" value="<?= e($editSlide['cta_text'] ?? 'Shop Now') ?>" 
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CTA Link</label>
                        <input type="text" name="cta_link" id="slideCtaLink" value="<?= e($editSlide['cta_link'] ?? '/products') ?>" 
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Slide Image</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-4 text-center hover:border-amber-400 transition-colors relative" id="imageDropZone">
                        <input type="file" name="image" id="slideImageFile" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="handleImagePreview(this)">
                        <div id="imagePreviewArea" class="space-y-2">
                            <?php if (!empty($editSlide['image_url'])): ?>
                            <img src="<?= e($editSlide['image_url']) ?>" alt="" class="mx-auto max-h-24 rounded-lg object-cover" id="currentImagePreview">
                            <p class="text-xs text-amber-600 font-medium">Current image loaded — upload new to replace</p>
                            <?php else: ?>
                            <i data-lucide="upload-cloud" class="w-8 h-8 text-gray-400 mx-auto"></i>
                            <p class="text-sm text-gray-500">Click or drag to upload an image</p>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">JPG, PNG, WebP — max 2MB</p>
                    </div>
                    <input type="hidden" name="image_url" id="slideImageUrl" value="">
                    <div class="mt-2">
                        <label class="block text-xs text-gray-500 mb-1">Or paste image URL instead:</label>
                        <input type="text" id="slideImageUrlFallback" placeholder="https://example.com/image.jpg" 
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                               oninput="document.getElementById('slideImageUrl').value = this.value">
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Background Gradient</label>
                        <select name="bg_gradient" id="slideBgGradient" 
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                            <option value="from-amber-700 via-orange-800 to-red-900" <?= ($editSlide['bg_gradient'] ?? '') === 'from-amber-700 via-orange-800 to-red-900' ? 'selected' : '' ?>>Amber - Red</option>
                            <option value="from-slate-800 via-slate-900 to-gray-950" <?= ($editSlide['bg_gradient'] ?? '') === 'from-slate-800 via-slate-900 to-gray-950' ? 'selected' : '' ?>>Dark Slate</option>
                            <option value="from-teal-700 via-amber-800 to-green-900" <?= ($editSlide['bg_gradient'] ?? '') === 'from-teal-700 via-amber-800 to-green-900' ? 'selected' : '' ?>>Teal - Green</option>
                            <option value="from-rose-700 via-pink-800 to-fuchsia-900" <?= ($editSlide['bg_gradient'] ?? '') === 'from-rose-700 via-pink-800 to-fuchsia-900' ? 'selected' : '' ?>>Rose - Fuchsia</option>
                            <option value="from-violet-700 via-purple-800 to-indigo-900" <?= ($editSlide['bg_gradient'] ?? '') === 'from-violet-700 via-purple-800 to-indigo-900' ? 'selected' : '' ?>>Violet - Indigo</option>
                            <option value="from-cyan-700 via-sky-800 to-blue-900" <?= ($editSlide['bg_gradient'] ?? '') === 'from-cyan-700 via-sky-800 to-blue-900' ? 'selected' : '' ?>>Cyan - Blue</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Text Position</label>
                        <select name="text_position" id="slideTextPosition" 
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                            <option value="left" <?= ($editSlide['text_position'] ?? 'left') === 'left' ? 'selected' : '' ?>>Left</option>
                            <option value="center" <?= ($editSlide['text_position'] ?? '') === 'center' ? 'selected' : '' ?>>Center</option>
                            <option value="right" <?= ($editSlide['text_position'] ?? '') === 'right' ? 'selected' : '' ?>>Right</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Overlay</label>
                        <select name="overlay" id="slideOverlay" 
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                            <option value="dark" <?= ($editSlide['overlay'] ?? 'dark') === 'dark' ? 'selected' : '' ?>>Dark</option>
                            <option value="light" <?= ($editSlide['overlay'] ?? '') === 'light' ? 'selected' : '' ?>>Light</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                        <input type="number" name="sort_order" id="slideSortOrder" value="<?= $editSlide['sort_order'] ?? (count($slides) + 1) ?>" min="1" 
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                    </div>
                    <div class="flex items-end pb-1">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" <?= (empty($editSlide) || $editSlide['is_active']) ? 'checked' : '' ?> 
                                   class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                            <span class="text-sm font-medium text-gray-700">Active</span>
                        </label>
                    </div>
                </div>

                <!-- Gradient Preview -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Preview</label>
                    <div class="rounded-xl overflow-hidden border border-gray-200">
                        <div id="slidePreview" class="bg-gradient-to-r <?= e($editSlide['bg_gradient'] ?? 'from-amber-700 via-orange-800 to-red-900') ?> h-40 flex items-center justify-center relative">
                            <div class="text-center text-white p-4" id="slidePreviewContent">
                                <p class="text-xs text-white/70 mb-1" id="previewSubtitle"><?= e($editSlide['subtitle'] ?? 'Subtitle') ?></p>
                                <p class="font-heading font-bold text-xl" id="previewTitle"><?= e($editSlide['title'] ?? 'Slide Title') ?></p>
                                <span class="inline-block mt-3 bg-white/20 text-sm px-4 py-1.5 rounded-lg" id="previewCta"><?= e($editSlide['cta_text'] ?? 'Shop Now') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3 bg-gray-50">
            <button onclick="closeSlideForm()" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
            <button onclick="document.getElementById('slideForm').submit()" class="px-4 py-2.5 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition-colors">
                <span id="slideSubmitText"><?= !empty($editSlide) ? 'Update Slide' : 'Create Slide' ?></span>
            </button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 z-[70] hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl max-w-sm w-full mx-4 p-6">
        <div class="text-center">
            <div class="w-14 h-14 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="alert-triangle" class="w-7 h-7 text-red-500"></i>
            </div>
            <h3 class="font-heading font-semibold text-lg text-gray-900 mb-2">Delete Slide?</h3>
            <p class="text-sm text-gray-500 mb-6">Are you sure you want to delete "<span id="deleteSlideName" class="font-medium text-gray-700"></span>"? This action cannot be undone.</p>
            <div class="flex gap-3">
                <button onclick="closeDeleteModal()" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                <form id="deleteForm" method="POST" action="/admin/hero-slides/delete" class="flex-1">
                    <?= csrf() ?>
                    <input type="hidden" name="id" id="deleteSlideId" value="">
                    <button type="submit" class="w-full px-4 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
lucide.createIcons();

function openSlideForm(btnOrData) {
    const modal = document.getElementById('slideModal');
    const title = document.getElementById('slideModalTitle');
    const submitText = document.getElementById('slideSubmitText');
    const form = document.getElementById('slideForm');

    // If called with a button element (from edit click), parse data from data-slide attribute
    var data = null;
    if (btnOrData && btnOrData.nodeType === 1) {
        try { data = JSON.parse(btnOrData.getAttribute('data-slide')); } catch(e) { data = null; }
    } else {
        data = btnOrData;
    }

    if (data) {
        title.textContent = 'Edit Hero Slide';
        submitText.textContent = 'Update Slide';
        document.getElementById('slideId').value = data.id;
        document.getElementById('slideTitle').value = data.title || '';
        document.getElementById('slideSubtitle').value = data.subtitle || '';
        document.getElementById('slideDescription').value = data.description || '';
        document.getElementById('slideCtaText').value = data.cta_text || 'Shop Now';
        document.getElementById('slideCtaLink').value = data.cta_link || '/products';
        document.getElementById('slideImageUrl').value = data.image_url || '';
        document.getElementById('slideImageUrlFallback').value = data.image_url || '';
        document.getElementById('slideBgGradient').value = data.bg_gradient || 'from-amber-700 via-orange-800 to-red-900';
        document.getElementById('slideTextPosition').value = data.text_position || 'left';
        document.getElementById('slideOverlay').value = data.overlay || 'dark';
        document.getElementById('slideSortOrder').value = data.sort_order || 1;
        form.querySelector('[name=is_active]').checked = data.is_active == 1;
        form.action = '/admin/hero-slides/edit/' + data.id;
        // Reset file input so old file selection doesn't carry over
        document.getElementById('slideImageFile').value = '';
        // Show current image preview
        var previewArea = document.getElementById('imagePreviewArea');
        if (data.image_url) {
            previewArea.innerHTML = '<img src="'+data.image_url+'" alt="" class="mx-auto max-h-24 rounded-lg object-cover"><p class="text-xs text-amber-600 font-medium">Current image loaded — upload new to replace</p>';
        } else {
            previewArea.innerHTML = '<i data-lucide="upload-cloud" class="w-8 h-8 text-gray-400 mx-auto"></i><p class="text-sm text-gray-500">Click or drag to upload an image</p>';
            lucide.createIcons();
        }
        updatePreview();
    } else {
        title.textContent = 'Add Hero Slide';
        submitText.textContent = 'Create Slide';
        form.reset();
        document.getElementById('slideId').value = '';
        document.getElementById('slideCtaText').value = 'Shop Now';
        document.getElementById('slideCtaLink').value = '/products';
        document.getElementById('slideImageUrl').value = '';
        document.getElementById('slideImageUrlFallback').value = '';
        form.querySelector('[name=is_active]').checked = true;
        form.action = '/admin/hero-slides';
        var previewArea = document.getElementById('imagePreviewArea');
        previewArea.innerHTML = '<i data-lucide="upload-cloud" class="w-8 h-8 text-gray-400 mx-auto"></i><p class="text-sm text-gray-500">Click or drag to upload an image</p>';
        lucide.createIcons();
        updatePreview();
    }
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    lucide.createIcons();
}

function closeSlideForm() {
    document.getElementById('slideModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function confirmDelete(id, name) {
    document.getElementById('deleteSlideId').value = id;
    document.getElementById('deleteSlideName').textContent = name;
    const modal = document.getElementById('deleteModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function updatePreview() {
    const preview = document.getElementById('slidePreview');
    const gradient = document.getElementById('slideBgGradient').value;
    preview.className = 'bg-gradient-to-r ' + gradient + ' h-40 flex items-center justify-center relative';
    document.getElementById('previewTitle').textContent = document.getElementById('slideTitle').value || 'Slide Title';
    document.getElementById('previewSubtitle').textContent = document.getElementById('slideSubtitle').value || 'Subtitle';
    document.getElementById('previewCta').textContent = document.getElementById('slideCtaText').value || 'Shop Now';
}

// Live preview updates
['slideBgGradient', 'slideTitle', 'slideSubtitle', 'slideCtaText'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', updatePreview);
    document.getElementById(id)?.addEventListener('change', updatePreview);
});

// Image upload preview
function handleImagePreview(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var previewArea = document.getElementById('imagePreviewArea');
            previewArea.innerHTML = '<img src="'+e.target.result+'" alt="" class="mx-auto max-h-24 rounded-lg object-cover"><p class="text-xs text-green-600 font-medium">New image selected — will be uploaded on save</p>';
        };
        reader.readAsDataURL(input.files[0]);
        document.getElementById('slideImageUrlFallback').value = '';
    }
}

// ESC key
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeSlideForm(); closeDeleteModal(); } });

<?php if (!empty($editSlide)): ?>
openSlideForm(<?= json_encode($editSlide) ?>);
<?php endif; ?>
</script>
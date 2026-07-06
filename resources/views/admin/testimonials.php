<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-heading font-semibold text-xl text-gray-900">Customer Testimonials</h1>
            <p class="text-sm text-gray-500 mt-1">Manage reviews and testimonials displayed on the homepage</p>
        </div>
        <button onclick="document.getElementById('addForm').classList.toggle('hidden'); document.getElementById('editForm').classList.add('hidden');" class="inline-flex items-center gap-2 bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Testimonial
        </button>
    </div>

    <!-- Stats -->
    <?php
    $totalReviews = count($testimonials);
    $avgRating = 0;
    $fiveStar = 0;
    foreach ($testimonials as $t) {
        $avgRating += $t['rating'];
        if ($t['rating'] === 5) $fiveStar++;
    }
    $avgRating = $totalReviews > 0 ? round($avgRating / $totalReviews, 1) : 0;
    ?>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500 mb-1">Total Reviews</p>
            <p class="text-2xl font-bold text-gray-900"><?= $totalReviews ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500 mb-1">Average Rating</p>
            <p class="text-2xl font-bold text-amber-600"><?= $avgRating ?> <span class="text-sm text-gray-400">/ 5</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500 mb-1">5-Star Reviews</p>
            <p class="text-2xl font-bold text-green-600"><?= $fiveStar ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500 mb-1">Featured</p>
            <p class="text-2xl font-bold text-blue-600"><?= count(array_filter($testimonials, fn($t) => $t['is_featured'])) ?></p>
        </div>
    </div>

    <!-- Add Form -->
    <div id="addForm" class="hidden bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-medium text-gray-900 mb-4">Add New Testimonial</h3>
        <form method="POST" action="/admin/testimonials/store" class="space-y-4">
            <?= csrf() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Author Name <span class="text-red-400">*</span></label>
                    <input type="text" name="author_name" placeholder="e.g. John Doe" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Author Title</label>
                    <input type="text" name="author_title" placeholder="e.g. Verified Buyer" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Photo URL</label>
                    <input type="url" name="author_photo" placeholder="https://example.com/photo.jpg" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Rating <span class="text-red-400">*</span></label>
                    <select name="rating" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                        <option value="5">★★★★★ (5 Stars)</option>
                        <option value="4">★★★★☆ (4 Stars)</option>
                        <option value="3">★★★☆☆ (3 Stars)</option>
                        <option value="2">★★☆☆☆ (2 Stars)</option>
                        <option value="1">★☆☆☆☆ (1 Star)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Source</label>
                    <select name="source" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                        <option value="google">Google</option>
                        <option value="facebook">Facebook</option>
                        <option value="website">Website</option>
                        <option value="email">Email</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="0" min="0" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Review Text <span class="text-red-400">*</span></label>
                <textarea name="review_text" rows="3" required placeholder="Write the customer review here..." class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none"></textarea>
            </div>
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-4">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="is_featured" value="1" class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                        <span class="text-sm text-gray-700">Featured</span>
                    </label>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="document.getElementById('addForm').classList.add('hidden')" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">Cancel</button>
                    <button type="submit" class="bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">Save Testimonial</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Edit Form -->
    <div id="editForm" class="hidden bg-white rounded-xl border border-amber-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-medium text-gray-900">Edit Testimonial</h3>
            <button onclick="document.getElementById('editForm').classList.add('hidden')" class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form id="editTestimonialForm" method="POST" class="space-y-4">
            <input type="hidden" name="id" id="editId">
            <?= csrf() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Author Name <span class="text-red-400">*</span></label>
                    <input type="text" name="author_name" id="editAuthorName" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Author Title</label>
                    <input type="text" name="author_title" id="editAuthorTitle" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Photo URL</label>
                    <input type="url" name="author_photo" id="editAuthorPhoto" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Rating</label>
                    <select name="rating" id="editRating" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                        <option value="5">★★★★★ (5 Stars)</option>
                        <option value="4">★★★★☆ (4 Stars)</option>
                        <option value="3">★★★☆☆ (3 Stars)</option>
                        <option value="2">★★☆☆☆ (2 Stars)</option>
                        <option value="1">★☆☆☆☆ (1 Star)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Source</label>
                    <select name="source" id="editSource" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                        <option value="google">Google</option>
                        <option value="facebook">Facebook</option>
                        <option value="website">Website</option>
                        <option value="email">Email</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" id="editSortOrder" min="0" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Review Text</label>
                <textarea name="review_text" id="editReviewText" rows="3" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none"></textarea>
            </div>
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-4">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="is_active" id="editIsActive" value="1" class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="is_featured" id="editIsFeatured" value="1" class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                        <span class="text-sm text-gray-700">Featured</span>
                    </label>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="document.getElementById('editForm').classList.add('hidden')" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">Cancel</button>
                    <button type="submit" class="bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">Update Testimonial</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Testimonials Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <?php if (empty($testimonials)): ?>
        <div class="px-4 py-12 text-center text-gray-400">
            <i data-lucide="message-square-quote" class="w-10 h-10 mx-auto mb-2 opacity-50"></i>
            <p class="text-sm">No testimonials yet. Click "Add Testimonial" to create one.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Author</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Rating</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600 hidden lg:table-cell">Review</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">Source</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($testimonials as $t): ?>
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <?php if (!empty($t['author_photo'])): ?>
                                    <img src="<?= e($t['author_photo']) ?>" alt="<?= e($t['author_name']) ?>" class="w-9 h-9 rounded-full object-cover border border-gray-100">
                                <?php else: ?>
                                    <div class="w-9 h-9 bg-amber-50 rounded-full flex items-center justify-center text-amber-600 font-bold text-sm shrink-0"><?= strtoupper(mb_substr($t['author_name'], 0, 1)) ?></div>
                                <?php endif; ?>
                                <div>
                                    <p class="font-medium text-gray-900 text-sm"><?= e($t['author_name']) ?></p>
                                    <?php if (!empty($t['author_title'])): ?>
                                        <p class="text-xs text-gray-400"><?= e($t['author_title']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($t['is_featured']): ?>
                                        <span class="inline-block text-[10px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full font-medium mt-0.5">Featured</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            <span class="text-amber-400 text-sm"><?= str_repeat('★', $t['rating']) ?><span class="text-gray-200"><?= str_repeat('★', 5 - $t['rating']) ?></span></span>
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell max-w-xs">
                            <p class="text-gray-600 text-xs leading-relaxed line-clamp-2"><?= e(mb_substr($t['review_text'], 0, 100)) ?><?= mb_strlen($t['review_text']) > 100 ? '...' : '' ?></p>
                        </td>
                        <td class="px-4 py-3 hidden sm:table-cell">
                            <span class="text-xs font-medium px-2 py-1 rounded-full <?= match($t['source']) {
                                'google' => 'bg-blue-50 text-blue-700',
                                'facebook' => 'bg-indigo-50 text-indigo-700',
                                'website' => 'bg-green-50 text-green-700',
                                'email' => 'bg-purple-50 text-purple-700',
                                default => 'bg-gray-50 text-gray-600',
                            } ?>"><?= e(ucfirst($t['source'])) ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $t['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>"><?= $t['is_active'] ? 'Active' : 'Inactive' ?></span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button onclick="openEditForm(
                                    <?= $t['id'] ?>,
                                    '<?= e(addslashes($t['author_name'])) ?>',
                                    '<?= e(addslashes($t['author_title'] ?? '')) ?>',
                                    '<?= e(addslashes($t['author_photo'] ?? '')) ?>',
                                    <?= (int)$t['rating'] ?>,
                                    '<?= e($t['source'] ?? 'google') ?>',
                                    '<?= e(addslashes($t['review_text'])) ?>',
                                    <?= (int)$t['is_active'] ?>,
                                    <?= (int)$t['is_featured'] ?>,
                                    <?= (int)($t['sort_order'] ?? 0) ?>
                                )" class="p-1.5 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Edit">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>
                                <form method="POST" action="/admin/testimonials/<?= $t['id'] ?>/delete" onsubmit="return confirm('Delete this testimonial?')">
                                    <?= csrf() ?>
                                    <button type="submit" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function openEditForm(id, authorName, authorTitle, authorPhoto, rating, source, reviewText, isActive, isFeatured, sortOrder) {
    document.getElementById('editId').value = id;
    document.getElementById('editAuthorName').value = authorName;
    document.getElementById('editAuthorTitle').value = authorTitle;
    document.getElementById('editAuthorPhoto').value = authorPhoto;
    document.getElementById('editRating').value = rating;
    document.getElementById('editSource').value = source;
    document.getElementById('editReviewText').value = reviewText;
    document.getElementById('editIsActive').checked = isActive === 1;
    document.getElementById('editIsFeatured').checked = isFeatured === 1;
    document.getElementById('editSortOrder').value = sortOrder;
    document.getElementById('editTestimonialForm').action = '/admin/testimonials/' + id + '/update';
    document.getElementById('addForm').classList.add('hidden');
    document.getElementById('editForm').classList.remove('hidden');
    document.getElementById('editForm').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    lucide.createIcons();
}
</script>
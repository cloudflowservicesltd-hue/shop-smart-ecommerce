<!-- Breadcrumbs -->
<div class="bg-gray-50 border-b border-gray-100">
    <div class="w-full px-4 sm:px-6 xl:px-0 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            <a href="/" class="hover:text-amber-600 transition-colors">Home</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900 font-medium">Blog</span>
        </nav>
    </div>
</div>

<div class="w-full px-4 sm:px-6 xl:px-0 py-10">
    <!-- Page Header -->
    <div class="text-center mb-10">
        <h1 class="font-heading text-3xl font-bold text-gray-900 mb-2">Our Blog</h1>
        <p class="text-gray-500 text-sm max-w-lg mx-auto">Stay up to date with the latest news, tips, and stories from our team.</p>
    </div>

    <?php if (empty($posts['data'])): ?>
    <div class="text-center py-16 text-gray-400">
        <i data-lucide="file-text" class="w-12 h-12 mx-auto mb-3 opacity-50"></i>
        <p class="text-sm">No blog posts yet. Check back soon!</p>
    </div>
    <?php else: ?>
    <!-- Blog Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($posts['data'] as $p): ?>
        <article class="bg-white border border-gray-200 rounded-2xl overflow-hidden hover:shadow-lg transition-shadow group">
            <?php if ($p['featured_image']): ?>
            <a href="/blog/<?= e($p['slug']) ?>" class="block aspect-[16/10] overflow-hidden">
                <img src="<?= e($p['featured_image']) ?>" alt="<?= e($p['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
            </a>
            <?php else: ?>
            <a href="/blog/<?= e($p['slug']) ?>" class="block aspect-[16/10] bg-gray-100 flex items-center justify-center">
                <i data-lucide="image" class="w-10 h-10 text-gray-300"></i>
            </a>
            <?php endif; ?>

            <div class="p-5">
                <div class="flex items-center gap-2 text-xs text-gray-400 mb-2">
                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                    <time><?= date('M d, Y', strtotime($p['created_at'])) ?></time>
                </div>

                <h2 class="font-heading font-semibold text-gray-900 mb-2 line-clamp-2">
                    <a href="/blog/<?= e($p['slug']) ?>" class="hover:text-amber-600 transition-colors"><?= e($p['title']) ?></a>
                </h2>

                <?php if ($p['excerpt']): ?>
                <p class="text-sm text-gray-500 line-clamp-2 mb-4"><?= e($p['excerpt']) ?></p>
                <?php endif; ?>

                <a href="/blog/<?= e($p['slug']) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-amber-600 hover:text-amber-700 transition-colors">
                    Read More
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if (!empty($posts['last_page']) && $posts['last_page'] > 1): ?>
    <div class="mt-10 flex items-center justify-center">
        <nav class="flex items-center gap-1">
            <?php if ($posts['current_page'] > 1): ?>
            <a href="?page=<?= $posts['current_page'] - 1 ?>" class="px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition-colors">&laquo; Prev</a>
            <?php endif; ?>
            <?php for ($pg = 1; $pg <= $posts['last_page']; $pg++): ?>
            <a href="?page=<?= $pg ?>" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $pg == $posts['current_page'] ? 'bg-amber-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>"><?= $pg ?></a>
            <?php endfor; ?>
            <?php if ($posts['current_page'] < $posts['last_page']): ?>
            <a href="?page=<?= $posts['current_page'] + 1 ?>" class="px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition-colors">Next &raquo;</a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
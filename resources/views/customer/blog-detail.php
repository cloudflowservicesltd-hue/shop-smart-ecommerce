<!-- Breadcrumbs -->
<div class="bg-gray-50 border-b border-gray-100">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            <a href="/" class="hover:text-amber-600 transition-colors">Home</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <a href="/blog" class="hover:text-amber-600 transition-colors">Blog</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900 font-medium truncate max-w-[200px]"><?= e($post['title']) ?></span>
        </nav>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 py-10">
    <!-- Back to Blog -->
    <a href="/blog" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-amber-600 transition-colors mb-6">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Blog
    </a>

    <!-- Article -->
    <article class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <?php if ($post['featured_image']): ?>
        <img src="<?= e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>" class="w-full aspect-[16/9] object-cover">
        <?php endif; ?>

        <div class="p-6 sm:p-10">
            <h1 class="font-heading text-2xl sm:text-3xl font-bold text-gray-900 mb-4"><?= e($post['title']) ?></h1>

            <!-- Meta -->
            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 mb-8 pb-8 border-b border-gray-100">
                <?php if ($post['author_name']): ?>
                <div class="flex items-center gap-1.5">
                    <i data-lucide="user" class="w-4 h-4"></i>
                    <span><?= e($post['author_name']) ?></span>
                </div>
                <?php endif; ?>
                <div class="flex items-center gap-1.5">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                    <time><?= date('F d, Y', strtotime($post['created_at'])) ?></time>
                </div>
                <div class="flex items-center gap-1.5">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                    <span><?= (int)$post['views'] ?> views</span>
                </div>
            </div>

            <!-- Content -->
            <div class="prose prose-gray max-w-none text-gray-700 leading-relaxed text-sm whitespace-pre-wrap"><?= e($post['content']) ?></div>
        </div>
    </article>

    <!-- Back to Blog (bottom) -->
    <div class="mt-8 text-center">
        <a href="/blog" class="inline-flex items-center gap-2 text-sm font-medium text-amber-600 hover:text-amber-700 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Blog
        </a>
    </div>
</div>
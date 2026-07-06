<?php
$totalReviews = count($reviews);
$approvedCount = count(array_filter($reviews, fn($r) => (bool)$r['is_approved']));
$pendingCount = $totalReviews - $approvedCount;
$avgRating = $totalReviews > 0 ? round(array_sum(array_column($reviews, 'rating')) / $totalReviews, 1) : 0;

$avatarColors = [
    'bg-amber-100 text-amber-700',
    'bg-blue-100 text-blue-700',
    'bg-emerald-100 text-emerald-700',
    'bg-violet-100 text-violet-700',
    'bg-rose-100 text-rose-700',
    'bg-cyan-100 text-cyan-700',
    'bg-orange-100 text-orange-700',
    'bg-indigo-100 text-indigo-700',
];

$statusBadge = [
    1 => 'bg-emerald-100 text-emerald-700',
    0 => 'bg-yellow-100 text-yellow-700',
];
$statusLabel = [
    1 => 'Approved',
    0 => 'Pending',
];
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="/admin/products" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-amber-600 transition-colors mb-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Back to Products
            </a>
            <h1 class="font-heading font-semibold text-xl text-gray-900">Reviews for <?= e($product['name']) ?></h1>
            <p class="text-sm text-gray-500 mt-1"><?= e($product['slug']) ?> &middot; <?= formatMoney($product['price']) ?></p>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Reviews -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="message-square" class="w-5 h-5 text-amber-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Total Reviews</p>
                    <p class="text-xl font-bold text-gray-900"><?= number_format($totalReviews) ?></p>
                </div>
            </div>
        </div>

        <!-- Average Rating -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="star" class="w-5 h-5 text-amber-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Avg. Rating</p>
                    <p class="text-xl font-bold text-gray-900"><?= $avgRating ?></p>
                </div>
            </div>
        </div>

        <!-- Approved -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Approved</p>
                    <p class="text-xl font-bold text-gray-900"><?= number_format($approvedCount) ?></p>
                </div>
            </div>
        </div>

        <!-- Pending -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="clock" class="w-5 h-5 text-yellow-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Pending</p>
                    <p class="text-xl font-bold text-gray-900"><?= number_format($pendingCount) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews List -->
    <?php if (empty($reviews)): ?>
    <!-- Empty State -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
        <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <i data-lucide="message-square-off" class="w-8 h-8 text-gray-400"></i>
        </div>
        <h3 class="font-heading font-semibold text-gray-900 mb-1">No Reviews Yet</h3>
        <p class="text-sm text-gray-500">This product hasn't received any customer reviews.</p>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($reviews as $index => $review):
            $colorClass = $avatarColors[$index % count($avatarColors)];
            $initial = strtoupper(substr($review['user_name'], 0, 1));
            $rating = (int)$review['rating'];
            $isApproved = (bool)$review['is_approved'];
        ?>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 sm:p-6">
            <div class="flex flex-col lg:flex-row lg:items-start gap-4">

                <!-- Left: Avatar + User Info -->
                <div class="flex items-start gap-3 min-w-0 shrink-0">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold shrink-0 <?= $colorClass ?>">
                        <?= e($initial) ?>
                    </div>
                    <div class="min-w-0">
                        <p class="font-medium text-gray-900 truncate"><?= e($review['user_name']) ?></p>
                        <p class="text-xs text-gray-500 truncate"><?= e($review['user_email']) ?></p>
                    </div>
                </div>

                <!-- Middle: Review Content (flex-1) -->
                <div class="flex-1 min-w-0">
                    <!-- Stars + Status -->
                    <div class="flex flex-wrap items-center gap-2 mb-1.5">
                        <div class="flex items-center gap-0.5">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i data-lucide="star" class="w-4 h-4 <?= $i <= $rating ? 'fill-amber-400 text-amber-400' : 'fill-gray-200 text-gray-200' ?>"></i>
                            <?php endfor; ?>
                            <span class="ml-1.5 text-sm font-medium text-gray-700"><?= number_format($rating, 1) ?></span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $statusBadge[(int)$review['is_approved']] ?? 'bg-yellow-100 text-yellow-700' ?>">
                            <?= $statusLabel[(int)$review['is_approved']] ?? 'Pending' ?>
                        </span>
                    </div>

                    <!-- Title -->
                    <?php if (!empty($review['title'])): ?>
                        <h3 class="font-semibold text-gray-900 mb-1"><?= e($review['title']) ?></h3>
                    <?php endif; ?>

                    <!-- Review Text -->
                    <?php if (!empty($review['review'])): ?>
                        <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line"><?= e($review['review']) ?></p>
                    <?php endif; ?>

                    <!-- Date -->
                    <p class="text-xs text-gray-400 mt-2 flex items-center gap-1">
                        <i data-lucide="calendar" class="w-3 h-3"></i>
                        <?= formatDate($review['created_at']) ?>
                    </p>
                </div>

                <!-- Right: Actions -->
                <div class="flex items-center gap-2 shrink-0 lg:pt-1">
                    <?php if (!$isApproved): ?>
                    <form method="POST" action="/admin/products/<?= (int)$product['id'] ?>/reviews/<?= (int)$review['id'] ?>/approve" class="inline">
                        <?= csrf() ?>
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium bg-amber-600 text-white hover:bg-amber-700 transition-colors">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i> Approve
                        </button>
                    </form>
                    <?php else: ?>
                    <form method="POST" action="/admin/products/<?= (int)$product['id'] ?>/reviews/<?= (int)$review['id'] ?>/reject" class="inline">
                        <?= csrf() ?>
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium bg-yellow-100 text-yellow-700 hover:bg-yellow-200 transition-colors">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i> Reject
                        </button>
                    </form>
                    <?php endif; ?>

                    <form method="POST" action="/admin/products/<?= (int)$product['id'] ?>/reviews/<?= (int)$review['id'] ?>/delete" class="inline" onsubmit="return confirm('Are you sure you want to delete this review? This action cannot be undone.');">
                        <?= csrf() ?>
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium bg-red-50 text-red-600 hover:bg-red-100 transition-colors">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
                        </button>
                    </form>
                </div>

            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
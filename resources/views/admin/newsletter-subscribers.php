<?php
$search = Request::query('search', '');
$where = "1=1";
$params = [];
if ($search) {
    $where .= " AND email LIKE ?";
    $params[] = "%$search%";
}
$paginated = Database::paginate('newsletter_subscribers', (int)Request::query('page', 1), 20, $where, $params, 'created_at DESC');
$subscribers = $paginated['data'];
$totalActive = Database::count('newsletter_subscribers', 'is_active = 1');
$totalAll = Database::count('newsletter_subscribers');
$todayCount = Database::count('newsletter_subscribers', "date(created_at) = date('now')");
$mailConfigured = class_exists('Mailer') && Mailer::isConfigured();
?>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-heading font-semibold text-xl text-gray-900">Newsletter Subscribers</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage your email subscribers and send campaigns.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin/newsletter/compose" class="inline-flex items-center gap-2 bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors shadow-sm">
                <i data-lucide="send" class="w-4 h-4"></i> Compose Email
            </a>
            <a href="/admin/newsletter/settings" class="inline-flex items-center gap-2 border border-gray-200 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
                <i data-lucide="settings" class="w-4 h-4"></i> Mail Settings
            </a>
        </div>
    </div>

    <?php if (!$mailConfigured): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 mt-0.5 shrink-0"></i>
        <div>
            <p class="text-sm font-medium text-amber-800">SMTP not configured</p>
            <p class="text-sm text-amber-700 mt-0.5">Email sending requires SMTP settings. <a href="/admin/newsletter/settings" class="underline font-medium">Configure mail settings</a> before sending campaigns.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center">
                    <i data-lucide="users" class="w-5 h-5 text-amber-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($totalAll) ?></p>
                    <p class="text-xs text-gray-500">Total Subscribers</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center">
                    <i data-lucide="user-check" class="w-5 h-5 text-green-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($totalActive) ?></p>
                    <p class="text-xs text-gray-500">Active Subscribers</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                    <i data-lucide="calendar" class="w-5 h-5 text-blue-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($todayCount) ?></p>
                    <p class="text-xs text-gray-500">New Today</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Export -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <form method="GET" class="flex-1">
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by email..." 
                       class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
            </div>
        </form>
        <form method="POST" action="/admin/newsletter/export">
            <?= csrf() ?>
            <button type="submit" class="inline-flex items-center gap-2 border border-gray-200 text-gray-700 px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors">
                <i data-lucide="download" class="w-4 h-4"></i> Export CSV
            </button>
        </form>
    </div>

    <!-- Subscribers Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <?php if (empty($subscribers)): ?>
        <div class="px-4 py-16 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i data-lucide="mail-x" class="w-8 h-8 text-gray-300"></i>
            </div>
            <h3 class="font-medium text-gray-900 mb-1">No subscribers yet</h3>
            <p class="text-sm text-gray-500">Subscribers will appear here when users sign up via the newsletter form.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">
                            <input type="checkbox" id="selectAll" class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500" onchange="toggleSelectAll(this)">
                        </th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Email</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">Subscribed</th>
                        <th class="text-center px-4 py-3 font-medium text-gray-600">Status</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($subscribers as $sub): ?>
                    <tr class="hover:bg-gray-50/50 transition-colors" data-email="<?= e($sub['email']) ?>">
                        <td class="px-4 py-3">
                            <input type="checkbox" name="selected[]" value="<?= e($sub['email']) ?>" class="subscriber-check w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-amber-50 rounded-full flex items-center justify-center shrink-0">
                                    <i data-lucide="mail" class="w-4 h-4 text-amber-600"></i>
                                </div>
                                <span class="font-medium text-gray-900"><?= e($sub['email']) ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500 hidden sm:table-cell">
                            <span class="text-xs"><?= date('M d, Y', strtotime($sub['created_at'])) ?></span>
                            <span class="text-xs text-gray-400 ml-1"><?= date('g:i A', strtotime($sub['created_at'])) ?></span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button onclick="toggleStatus(<?= $sub['id'] ?>, this)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium transition-colors cursor-pointer <?= $sub['is_active'] ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= $sub['is_active'] ? 'bg-green-500' : 'bg-gray-400' ?>"></span>
                                <?= $sub['is_active'] ? 'Active' : 'Inactive' ?>
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="/admin/newsletter/subscribers/delete" class="inline" onsubmit="return confirm('Delete this subscriber?')">
                                <?= csrf() ?>
                                <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                                <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Bulk Actions Bar -->
        <div id="bulkBar" class="hidden border-t border-gray-100 bg-gray-50 px-4 py-3 flex items-center justify-between">
            <span class="text-sm text-gray-600"><span id="selectedCount">0</span> selected</span>
            <div class="flex items-center gap-2">
                <button onclick="composeToSelected()" class="inline-flex items-center gap-1.5 bg-amber-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-amber-700 transition-colors">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i> Send Email
                </button>
                <button onclick="deleteSelected()" class="inline-flex items-center gap-1.5 border border-red-200 text-red-600 px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-red-50 transition-colors">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
                </button>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($paginated['last_page'] > 1): ?>
        <div class="border-t border-gray-100 px-4 py-3 flex items-center justify-center">
            <nav class="flex items-center gap-1">
                <?php if ($paginated['current_page'] > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $paginated['current_page'] - 1])) ?>" class="px-3 py-1.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100">&laquo;</a>
                <?php endif; ?>
                <?php for ($p = max(1, $paginated['current_page'] - 2); $p <= min($paginated['last_page'], $paginated['current_page'] + 2); $p++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium <?= $p == $paginated['current_page'] ? 'bg-amber-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>"><?= $p ?></a>
                <?php endfor; ?>
                <?php if ($paginated['current_page'] < $paginated['last_page']): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $paginated['current_page'] + 1])) ?>" class="px-3 py-1.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100">&raquo;</a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Selected Form -->
<form id="deleteSelectedForm" method="POST" action="/admin/newsletter/subscribers/bulk-delete" class="hidden">
    <?= csrf() ?>
    <input type="hidden" name="ids" id="deleteIds">
</form>

<script>
lucide.createIcons();

function toggleSelectAll(cb) {
    document.querySelectorAll('.subscriber-check').forEach(c => c.checked = cb.checked);
    updateBulkBar();
}

function updateBulkBar() {
    const checked = document.querySelectorAll('.subscriber-check:checked');
    const bar = document.getElementById('bulkBar');
    const count = document.getElementById('selectedCount');
    if (checked.length > 0) {
        bar.classList.remove('hidden');
        count.textContent = checked.length;
    } else {
        bar.classList.add('hidden');
    }
}

document.querySelectorAll('.subscriber-check').forEach(cb => {
    cb.addEventListener('change', updateBulkBar);
});

function toggleStatus(id, btn) {
    const fd = new FormData();
    fd.append('_token', '<?= csrf_token() ?>');
    fd.append('id', id);
    fetch('/admin/newsletter/subscribers/toggle', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            const isActive = d.active;
            btn.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium transition-colors cursor-pointer ' + (isActive ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-gray-100 text-gray-500 hover:bg-gray-200');
            btn.innerHTML = '<span class="w-1.5 h-1.5 rounded-full ' + (isActive ? 'bg-green-500' : 'bg-gray-400') + '"></span> ' + (isActive ? 'Active' : 'Inactive');
        }
    });
}

function composeToSelected() {
    const emails = Array.from(document.querySelectorAll('.subscriber-check:checked')).map(c => c.value).join(',');
    window.location.href = '/admin/newsletter/compose?to=' + encodeURIComponent(emails);
}

function deleteSelected() {
    if (!confirm('Delete ' + document.querySelectorAll('.subscriber-check:checked').length + ' subscriber(s)?')) return;
    const ids = Array.from(document.querySelectorAll('.subscriber-check:checked')).map(c => {
        return c.closest('tr').querySelector('input[name="id"]').value;
    });
    document.getElementById('deleteIds').value = ids.join(',');
    document.getElementById('deleteSelectedForm').submit();
}
</script>
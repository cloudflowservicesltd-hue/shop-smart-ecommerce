<?php
if (!Auth::check() || !Auth::hasRole(['admin', 'super_admin', 'cashier'])) {
    Session::flash('error', 'Access denied.');
    Redirect::to('/');
    exit;
}
$storeName = 'ShopSmart';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Held Sales - POS | <?= e($storeName) ?></title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','system-ui','sans-serif'],heading:['Poppins','sans-serif']}}}}</script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        body{font-family:'Inter',system-ui,sans-serif}
        h1,h2,h3,.font-heading{font-family:'Poppins',sans-serif}
        .scrollbar-thin::-webkit-scrollbar{width:5px}
        .scrollbar-thin::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:3px}
        .hold-card{animation:fadeSlideIn .25s ease both}
        .hold-card:nth-child(1){animation-delay:.03s}
        .hold-card:nth-child(2){animation-delay:.06s}
        .hold-card:nth-child(3){animation-delay:.09s}
        .hold-card:nth-child(4){animation-delay:.12s}
        .hold-card:nth-child(5){animation-delay:.15s}
        .hold-card:nth-child(6){animation-delay:.18s}
        .hold-card:nth-child(7){animation-delay:.21s}
        .hold-card:nth-child(8){animation-delay:.24s}
        @keyframes fadeSlideIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
        @keyframes spin{to{transform:rotate(360deg)}}
        .spinner{animation:spin .6s linear infinite}
        .btn-action{transition:all .15s ease}
        .btn-action:active{transform:scale(.96)}
    </style>
</head>
<body class="bg-gray-100 h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-gray-900 text-white px-4 py-2.5 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
            <a href="/pos" class="flex items-center justify-center w-8 h-8 rounded-lg bg-gray-800 hover:bg-gray-700 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-sm font-heading font-bold">Held Sales</h1>
                    <span id="holdsBadge" class="hidden px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-600 text-white">0</span>
                </div>
                <p class="text-[10px] text-gray-400"><?= date('l, M d, Y') ?></p>
            </div>
        </div>
        <button onclick="loadHeldSales()" class="p-2 text-gray-400 hover:text-white rounded-lg hover:bg-gray-800 transition-colors" title="Refresh">
            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
        </button>
    </header>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto scrollbar-thin">
        <!-- Loading State -->
        <div id="loadingState" class="flex items-center justify-center py-20">
            <div class="text-center">
                <div class="w-10 h-10 border-3 border-gray-200 border-t-emerald-600 rounded-full spinner mx-auto mb-3" style="border-width:3px"></div>
                <p class="text-sm text-gray-500">Loading held sales...</p>
            </div>
        </div>

        <!-- Error State -->
        <div id="errorState" class="hidden flex items-center justify-center py-20">
            <div class="text-center max-w-sm mx-auto px-4">
                <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="alert-circle" class="w-8 h-8 text-red-400"></i>
                </div>
                <h3 class="font-heading font-semibold text-gray-900 mb-1">Failed to Load</h3>
                <p id="errorMessage" class="text-sm text-gray-500 mb-4">Something went wrong. Please try again.</p>
                <button onclick="loadHeldSales()" class="btn-action inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    Try Again
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="hidden flex items-center justify-center py-20">
            <div class="text-center max-w-sm mx-auto px-4">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-5">
                    <i data-lucide="pause-circle" class="w-10 h-10 text-gray-300"></i>
                </div>
                <h3 class="font-heading font-semibold text-gray-900 text-lg mb-2">No Held Sales</h3>
                <p class="text-sm text-gray-500 mb-6">There are currently no sales on hold. Pause an active sale from the POS terminal to see it here.</p>
                <a href="/pos" class="btn-action inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                    Back to POS
                </a>
            </div>
        </div>

        <!-- Holds Grid -->
        <div id="holdsGrid" class="hidden p-4 grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        </div>
    </main>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" onclick="closeDeleteModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6" style="animation:fadeSlideIn .2s ease both">
            <div class="w-14 h-14 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="trash-2" class="w-7 h-7 text-red-500"></i>
            </div>
            <h3 class="font-heading font-semibold text-gray-900 text-center text-lg mb-2">Delete Held Sale</h3>
            <p class="text-sm text-gray-500 text-center mb-6">Are you sure you want to permanently delete this held sale? This action cannot be undone.</p>
            <div class="flex gap-3">
                <button onclick="closeDeleteModal()" class="btn-action flex-1 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors">Cancel</button>
                <button onclick="confirmDelete()" id="confirmDeleteBtn" class="btn-action flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2">
                    <span>Delete</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="hidden fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium text-white max-w-sm" style="animation:fadeSlideIn .2s ease both">
        <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
        <span id="toastMessage"></span>
    </div>

    <script>
        let heldSales = [];
        let deleteTargetId = null;

        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            loadHeldSales();
        });

        async function loadHeldSales() {
            const loadingEl = document.getElementById('loadingState');
            const errorEl = document.getElementById('errorState');
            const emptyEl = document.getElementById('emptyState');
            const gridEl = document.getElementById('holdsGrid');
            const badge = document.getElementById('holdsBadge');

            loadingEl.classList.remove('hidden');
            errorEl.classList.add('hidden');
            emptyEl.classList.add('hidden');
            gridEl.classList.add('hidden');

            try {
                const res = await fetch('/api/pos/holds', {
                    headers: { 'Accept': 'application/json' }
                });

                if (!res.ok) throw new Error('Server responded with ' + res.status);

                const text = await res.text();
                let json;
                try { json = JSON.parse(text); } catch(e) { throw new Error('Invalid server response'); }
                heldSales = json.data || json.holds || json || [];

                loadingEl.classList.add('hidden');

                if (!Array.isArray(heldSales) || heldSales.length === 0) {
                    emptyEl.classList.remove('hidden');
                    badge.classList.add('hidden');
                    lucide.createIcons();
                    return;
                }

                badge.textContent = heldSales.length;
                badge.classList.remove('hidden');
                renderHolds(heldSales);
                gridEl.classList.remove('hidden');
                lucide.createIcons();
            } catch (err) {
                loadingEl.classList.add('hidden');
                document.getElementById('errorMessage').textContent = err.message || 'Something went wrong. Please try again.';
                errorEl.classList.remove('hidden');
                lucide.createIcons();
            }
        }

        function formatMoney(amount) {
            const num = parseFloat(amount) || 0;
            return num.toLocaleString('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 });
        }

        function timeAgo(dateStr) {
            const now = new Date();
            const then = new Date(dateStr);
            const diffMs = now - then;
            const diffMin = Math.floor(diffMs / 60000);
            const diffHr = Math.floor(diffMs / 3600000);
            const diffDay = Math.floor(diffMs / 86400000);

            if (diffMin < 1) return 'Just now';
            if (diffMin < 60) return diffMin + ' min ago';
            if (diffHr < 24) return diffHr + 'h ' + (diffMin % 60) + 'm ago';
            if (diffDay < 7) return diffDay + 'd ago';
            return then.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }

        function formatTime(dateStr) {
            const d = new Date(dateStr);
            return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        }

        function renderHolds(sales) {
            const grid = document.getElementById('holdsGrid');
            grid.innerHTML = sales.map((sale, index) => {
                const customerName = sale.customer_name || sale.customer || 'Walk-in';
                const itemsCount = sale.items_count || (sale.items ? sale.items.length : 0);
                const subtotal = sale.subtotal || sale.sub_total || 0;
                const tax = sale.tax || sale.tax_amount || 0;
                const total = sale.total || sale.grand_total || 0;
                const notes = sale.notes || '';
                const cashierName = sale.cashier_name || sale.cashier || 'Cashier';
                const heldAt = sale.held_at || sale.created_at || sale.updated_at || '';
                const id = sale.id;

                return `
                <div class="hold-card bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:border-gray-200 transition-all" style="animation-delay:${index * 0.03}s">
                    <div class="p-4">
                        <!-- Card Header -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-9 h-9 rounded-lg ${customerName === 'Walk-in' ? 'bg-gray-100' : 'bg-emerald-50'} flex items-center justify-center shrink-0">
                                    <i data-lucide="${customerName === 'Walk-in' ? 'user' : 'user-check'}" class="w-4 h-4 ${customerName === 'Walk-in' ? 'text-gray-400' : 'text-emerald-600'}"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 truncate">${escapeHtml(customerName)}</p>
                                    <p class="text-[11px] text-gray-400">${cashierName ? 'by ' + escapeHtml(cashierName) : ''}</p>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-lg font-bold text-gray-900">${formatMoney(total)}</p>
                            </div>
                        </div>

                        <!-- Items & Time -->
                        <div class="flex items-center gap-4 mb-3 text-xs text-gray-500">
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="package" class="w-3.5 h-3.5 text-gray-400"></i>
                                <span>${itemsCount} item${itemsCount !== 1 ? 's' : ''}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="clock" class="w-3.5 h-3.5 text-gray-400"></i>
                                <span title="${heldAt ? formatTime(heldAt) : ''}">${heldAt ? timeAgo(heldAt) : '—'}</span>
                            </div>
                        </div>

                        <!-- Notes -->
                        ${notes ? `
                        <div class="mb-3 px-3 py-2 bg-amber-50 border border-amber-100 rounded-lg">
                            <div class="flex items-start gap-2">
                                <i data-lucide="sticky-note" class="w-3.5 h-3.5 text-amber-500 shrink-0 mt-0.5"></i>
                                <p class="text-xs text-amber-800 leading-relaxed">${escapeHtml(notes)}</p>
                            </div>
                        </div>
                        ` : ''}

                        <!-- Breakdown -->
                        <div class="flex items-center justify-between py-2 border-t border-gray-50 text-xs text-gray-500 mb-3">
                            <span>Subtotal</span>
                            <span class="font-medium text-gray-700">${formatMoney(subtotal)}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                            <span>Tax</span>
                            <span class="font-medium text-gray-700">${formatMoney(tax)}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-t border-gray-100 text-sm">
                            <span class="font-semibold text-gray-900">Total</span>
                            <span class="font-bold text-emerald-600">${formatMoney(total)}</span>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2 mt-4">
                            <button onclick="restoreHold(${id})" class="btn-action flex-1 flex items-center justify-center gap-1.5 px-3 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition-colors">
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                Restore to Cart
                            </button>
                            <button onclick="openDeleteModal(${id})" class="btn-action flex items-center justify-center px-3 py-2.5 bg-gray-100 hover:bg-red-50 hover:text-red-600 text-gray-400 text-xs font-medium rounded-lg transition-colors border border-gray-200 hover:border-red-200">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>
                </div>
                `;
            }).join('');
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        async function restoreHold(id) {
            const btn = event.currentTarget;
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full spinner"></div><span>Restoring...</span>';

            try {
                const res = await fetch(`/api/pos/holds/restore/${id}`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' }
                });

                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    throw new Error(errData.error || errData.message || 'Restore failed');
                }

                showToast('Sale restored to cart', 'success');
                setTimeout(() => { window.location.href = '/pos'; }, 400);
            } catch (err) {
                showToast(err.message || 'Failed to restore sale', 'error');
                btn.disabled = false;
                btn.innerHTML = originalHTML;
                lucide.createIcons();
            }
        }

        function openDeleteModal(id) {
            deleteTargetId = id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            deleteTargetId = null;
            const btn = document.getElementById('confirmDeleteBtn');
            btn.disabled = false;
            btn.innerHTML = '<span>Delete</span>';
        }

        async function confirmDelete() {
            if (deleteTargetId === null) return;

            const btn = document.getElementById('confirmDeleteBtn');
            btn.disabled = true;
            btn.innerHTML = '<div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full spinner"></div><span>Deleting...</span>';

            try {
                const res = await fetch(`/api/pos/holds/${deleteTargetId}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json' }
                });

                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    throw new Error(errData.error || errData.message || 'Delete failed');
                }

                closeDeleteModal();
                showToast('Held sale deleted', 'success');
                heldSales = heldSales.filter(s => s.id !== deleteTargetId);
                deleteTargetId = null;

                const gridEl = document.getElementById('holdsGrid');
                const emptyEl = document.getElementById('emptyState');
                const badge = document.getElementById('holdsBadge');

                if (heldSales.length === 0) {
                    gridEl.classList.add('hidden');
                    emptyEl.classList.remove('hidden');
                    badge.classList.add('hidden');
                    lucide.createIcons();
                } else {
                    badge.textContent = heldSales.length;
                    renderHolds(heldSales);
                    lucide.createIcons();
                }
            } catch (err) {
                showToast(err.message || 'Failed to delete', 'error');
                btn.disabled = false;
                btn.innerHTML = '<span>Delete</span>';
            }
        }

        let toastTimer = null;
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const msgEl = document.getElementById('toastMessage');

            toast.className = 'fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium text-white max-w-sm';
            toast.style.animation = 'fadeSlideIn .2s ease both';

            if (type === 'success') {
                toast.classList.add('bg-emerald-600');
            } else if (type === 'error') {
                toast.classList.add('bg-red-600');
            }

            msgEl.textContent = message;
            toast.classList.remove('hidden');

            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => {
                toast.classList.add('hidden');
            }, 3000);
        }
    </script>
</body>
</html>
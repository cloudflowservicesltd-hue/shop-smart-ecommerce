<?php $campaigns = Database::select("SELECT * FROM marketing_campaigns WHERE platform = 'email' ORDER BY created_at DESC"); ?>
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="font-heading font-semibold text-xl text-gray-900 flex items-center gap-2"><i data-lucide="mail" class="w-5 h-5 text-amber-600"></i> Email Campaigns</h1>
        <button onclick="document.getElementById('createForm').classList.toggle('hidden')" class="inline-flex items-center gap-2 bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700">
            <i data-lucide="plus" class="w-4 h-4"></i> New Campaign
        </button>
    </div>

    <div id="createForm" class="hidden bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-medium mb-4">Create Email Campaign</h3>
        <form method="POST" action="/admin/marketing/email/store" class="space-y-4">
            <?= csrf() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Campaign Name</label><input type="text" name="name" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Subject Line</label><input type="text" name="subject" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Audience</label>
                <select name="audience" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm">
                    <option>All Subscribers</option><option>New Customers</option><option>VIP Customers</option><option>Abandoned Cart</option>
                </select>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Email Content</label><textarea name="content" rows="6" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500" placeholder="Write your email content..."></textarea></div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Schedule (optional)</label><input type="datetime-local" name="schedule_date" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm"></div>
                <div class="flex items-end"><button type="submit" class="w-full bg-amber-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 flex items-center justify-center gap-2"><i data-lucide="send" class="w-4 h-4"></i> Save & Send</button></div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr><th class="text-left px-4 py-3 font-medium text-gray-600">Campaign</th><th class="text-left px-4 py-3 font-medium text-gray-600">Subject</th><th class="text-left px-4 py-3 font-medium text-gray-600">Sent</th><th class="text-left px-4 py-3 font-medium text-gray-600">Opened</th><th class="text-left px-4 py-3 font-medium text-gray-600">Status</th></tr></thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($campaigns as $c): ?>
                <tr class="hover:bg-gray-50/50">
                    <td class="px-4 py-3 font-medium"><?= e($c['name']) ?></td>
                    <td class="px-4 py-3 text-gray-600"><?= e($c['subject'] ?? '-') ?></td>
                    <td class="px-4 py-3"><?= number_format($c['total_sent']) ?></td>
                    <td class="px-4 py-3"><?= number_format($c['total_opened']) ?></td>
                    <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $c['status'] === 'sent' ? 'bg-amber-100 text-amber-700' : ($c['status'] === 'active' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') ?>"><?= ucfirst($c['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($campaigns)): ?>
                    <tr><td colspan="5" class="px-4 py-12 text-center text-gray-500">No email campaigns</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
if (Auth::isSuperAdmin()) {
    $users = Database::select("SELECT * FROM users ORDER BY created_at DESC");
} else {
    $users = Database::select("SELECT * FROM users WHERE role != 'super_admin' ORDER BY created_at DESC");
}
$roleColors = ['super_admin' => 'bg-red-100 text-red-700', 'admin' => 'bg-amber-100 text-amber-700', 'cashier' => 'bg-amber-100 text-amber-700', 'customer' => 'bg-blue-100 text-blue-700'];
$isSuper = Auth::isSuperAdmin();
?>
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="font-heading font-semibold text-xl text-gray-900">Users & Roles</h1>
        <button onclick="document.getElementById('addForm').classList.toggle('hidden')" class="inline-flex items-center gap-2 bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700">
            <i data-lucide="plus" class="w-4 h-4"></i> Add User
        </button>
    </div>

    <div id="addForm" class="hidden bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-medium text-gray-900 mb-4">Create New User</h3>
        <form method="POST" action="/admin/users/store" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <?= csrf() ?>
            <input type="text" name="name" placeholder="Full Name" required class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
            <input type="email" name="email" placeholder="Email" required class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
            <input type="password" name="password" placeholder="Password" required class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
            <select name="role" class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                <option value="customer">Customer</option>
                <option value="cashier">Cashier</option>
                <option value="admin">Admin</option>
                <?php if ($isSuper): ?>
                <option value="super_admin">Super Admin</option>
                <?php endif; ?>
            </select>
            <button type="submit" class="bg-amber-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700">Create User</button>
        </form>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" onclick="closeEditModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-heading font-semibold text-lg text-gray-900">Edit User</h3>
                <button onclick="closeEditModal()" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="POST" id="editForm" class="space-y-4">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="user_id" id="editUserId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" id="editName" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="editEmail" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-gray-400 font-normal">(leave blank to keep unchanged)</span></label>
                    <input type="password" name="password" id="editPassword" placeholder="New password" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="role" id="editRole" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="customer">Customer</option>
                        <option value="cashier">Cashier</option>
                        <option value="admin">Admin</option>
                        <option value="super_admin" id="editSuperAdminOption">Super Admin</option>
                    </select>
                </div>
                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" id="editActive" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-amber-500 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                        <span class="ms-3 text-sm font-medium text-gray-700">Active</span>
                    </label>
                </div>
                <!-- Menu Permissions (only for admin/manager, only super_admin can set) -->
                <div id="menuPermsSection" class="hidden">
                    <div class="border border-gray-200 rounded-xl p-4">
                        <p class="text-sm font-semibold text-gray-800 mb-1">Menu Permissions</p>
                        <p class="text-xs text-gray-400 mb-3">Grant access to specific admin menus for this manager.</p>
                        <div class="grid grid-cols-2 gap-2">
                            <?php
                            $menuPermsList = [
                                'pages' => 'CMS Pages',
                                'settings' => 'Settings',
                                'api-integrations' => 'API Integrations',
                                'commissions' => 'Commissions',
                                'shipping' => 'Shipping Fees',
                                'social-media' => 'Social Media',
                                'seo' => 'SEO & Meta',
                                'sitemap' => 'Sitemap',
                                'cities' => 'Cities',
                                'payments' => 'Payments',
                                'marketing' => 'Marketing',
                            ];
                            foreach ($menuPermsList as $slug => $label):
                            ?>
                            <label class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                                <input type="checkbox" name="menu_perms[]" value="<?= $slug ?>" class="menu-perm-check w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                                <span class="text-xs font-medium text-gray-700"><?= $label ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
                    <button type="submit" class="px-4 py-2.5 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr><th class="text-left px-4 py-3 font-medium text-gray-600">User</th><th class="text-left px-4 py-3 font-medium text-gray-600">Role</th><th class="text-left px-4 py-3 font-medium text-gray-600">Status</th><th class="text-left px-4 py-3 font-medium text-gray-600">Last Login</th><th class="text-right px-4 py-3 font-medium text-gray-600">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($users as $u): ?>
                <tr class="hover:bg-gray-50/50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center text-amber-700 text-sm font-bold"><?= strtoupper(substr($u['name'], 0, 1)) ?></div>
                            <div><p class="font-medium"><?= e($u['name']) ?></p><p class="text-xs text-gray-500"><?= e($u['email']) ?></p></div>
                        </div>
                    </td>
                    <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $roleColors[$u['role']] ?? '' ?>"><?= str_replace('_', ' ', ucfirst($u['role'])) ?></span></td>
                    <td class="px-4 py-3"><span class="w-2 h-2 rounded-full inline-block mr-1.5 <?= $u['is_active'] ? 'bg-amber-500' : 'bg-gray-300' ?>"></span><?= $u['is_active'] ? 'Active' : 'Inactive' ?></td>
                    <td class="px-4 py-3 text-xs text-gray-500"><?= $u['last_login'] ? timeAgo($u['last_login']) : 'Never' ?></td>
                    <td class="px-4 py-3 text-right space-x-1">
                        <button onclick="openEditModal(<?= $u['id'] ?>, '<?= e(addslashes($u['name'])) ?>', '<?= e(addslashes($u['email'])) ?>', '<?= $u['role'] ?>', <?= $u['is_active'] ? 1 : 0 ?>, '<?= e(addslashes($u['menu_permissions'] ?? '')) ?>')" class="p-1.5 text-gray-400 hover:text-amber-600 rounded-lg inline-flex" title="Edit">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                        </button>
                        <?php if ($u['id'] !== Auth::id()): ?>
                        <form method="POST" action="/admin/users/<?= $u['id'] ?>/delete" onsubmit="return confirm('Delete this user?')" class="inline">
                            <?= csrf() ?><button class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const isSuper = <?= $isSuper ? 'true' : 'false' ?>;

function openEditModal(id, name, email, role, isActive, menuPerms) {
    document.getElementById('editUserId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editEmail').value = email;
    document.getElementById('editRole').value = role;
    document.getElementById('editActive').checked = isActive === 1;
    document.getElementById('editForm').action = '/admin/users/' + id + '/update';

    // Hide super_admin option if current user is not super admin
    const superOpt = document.getElementById('editSuperAdminOption');
    if (superOpt) superOpt.style.display = isSuper ? '' : 'none';

    // Show menu permissions for admin role only
    const permsSection = document.getElementById('menuPermsSection');
    if (permsSection) {
        permsSection.classList.toggle('hidden', role !== 'admin' || !isSuper);
    }

    // Set menu permission checkboxes
    try {
        var perms = menuPerms ? JSON.parse(menuPerms) : [];
        document.querySelectorAll('.menu-perm-check').forEach(function(cb) {
            cb.checked = perms.indexOf(cb.value) !== -1;
        });
    } catch(e) {
        document.querySelectorAll('.menu-perm-check').forEach(function(cb) { cb.checked = false; });
    }

    document.getElementById('editModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    lucide.createIcons();
}

// Toggle menu perms visibility when role changes
document.getElementById('editRole').addEventListener('change', function() {
    var permsSection = document.getElementById('menuPermsSection');
    if (permsSection) {
        permsSection.classList.toggle('hidden', this.value !== 'admin' || !isSuper);
    }
});

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.body.style.overflow = '';
}
</script>
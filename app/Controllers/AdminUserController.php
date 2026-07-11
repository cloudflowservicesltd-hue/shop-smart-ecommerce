<?php

class AdminUserController extends BaseController
{
    public function index()
    {
        $breadcrumbs = [['Users & Roles', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/users.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function store()
    {
        Database::insert('users', [
            'name' => Request::post('name', ''), 'email' => Request::post('email', ''),
            'password' => password_hash(Request::post('password', ''), PASSWORD_DEFAULT),
            'role' => Request::post('role', 'customer'),
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'User created');
        Redirect::to('/admin/users');
    }

    public function delete($id)
    {
        Database::delete('users', 'id = ?', [$id]);
        Session::flash('success', 'User deleted');
        Redirect::to('/admin/users');
    }

    public function update($id)
    {
        $data = [
            'name' => Request::post('name', ''),
            'email' => Request::post('email', ''),
            'role' => Request::post('role', 'customer'),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (!Auth::isSuperAdmin() && $data['role'] === 'super_admin') {
            $data['role'] = 'admin';
        }
        // Save menu permissions (JSON array) for admin/manager users
        $menuPerms = Request::post('menu_perms', []);
        if (is_array($menuPerms)) {
            $menuPermsJson = json_encode($menuPerms);
        } else {
            $menuPermsJson = '[]';
        }
        $data['menu_permissions'] = $menuPermsJson;
        $password = Request::post('password', '');
        if ($password) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        Database::update('users', $data, 'id = ?', [$id]);
        Session::flash('success', 'User updated');
        Redirect::to('/admin/users');
    }
}
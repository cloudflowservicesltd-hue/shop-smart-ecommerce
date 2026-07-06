<?php

class Auth
{
    public static function check(): bool
    {
        return Session::has('user_id');
    }

    public static function user(): ?array
    {
        if (!self::check()) return null;
        return Database::selectOne("SELECT * FROM users WHERE id = ?", [Session::get('user_id')]);
    }

    public static function id(): ?int
    {
        return Session::get('user_id');
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = Database::selectOne("SELECT * FROM users WHERE email = ?", [$email]);

        if ($user && password_verify($password, $user['password'])) {
            Session::set('user_id', $user['id']);
            Session::set('user_name', $user['name']);
            Session::set('user_role', $user['role']);
            Session::set('user_email', $user['email']);
            Database::update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
            return true;
        }

        return false;
    }

    public static function register(array $data): int
    {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['role'] = $data['role'] ?? 'customer';
        $data['created_at'] = date('Y-m-d H:i:s');

        $id = Database::insert('users', $data);

        Session::set('user_id', $id);
        Session::set('user_name', $data['name']);
        Session::set('user_role', $data['role']);
        Session::set('user_email', $data['email']);

        return $id;
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function hasRole(string|array $roles): bool
    {
        $userRole = Session::get('user_role', '');
        if (is_string($roles)) $roles = [$roles];
        return in_array($userRole, $roles);
    }

    public static function isAdmin(): bool
    {
        return self::hasRole(['super_admin', 'admin']);
    }

    public static function isSuperAdmin(): bool
    {
        return self::hasRole('super_admin');
    }

    public static function isCashier(): bool
    {
        return self::hasRole('cashier');
    }

    public static function isCustomer(): bool
    {
        return self::hasRole('customer');
    }
}
<?php

/**
 * Auth Controller
 *
 * Handles authentication routes: login, register, forgot/reset password, logout.
 */
class AuthController extends BaseController
{
    /**
     * Show login page.
     * Route: GET /login
     */
    public function showLogin(): void
    {
        if (Auth::check()) Redirect::to('/');
        include ROOT_PATH . '/resources/views/auth/login.php';
    }

    /**
     * Process login attempt.
     * Route: POST /login
     */
    public function login(): void
    {
        $email = Request::post('email', '');
        $password = Request::post('password', '');

        // Remember session_id before login (for cart merge)
        $sessionId = session_id();

        if (Auth::attempt($email, $password)) {
            // Merge guest cart items into user cart
            if (Auth::check()) {
                $userId = Auth::id();
                $guestItems = Database::select("SELECT * FROM cart WHERE session_id = ? AND (user_id IS NULL OR user_id = 0)", [$sessionId]);
                foreach ($guestItems as $item) {
                    $existing = Database::selectOne("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?", [$userId, $item['product_id']]);
                    if ($existing) {
                        Database::update('cart', ['quantity' => $existing['quantity'] + $item['quantity'], 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$existing['id']]);
                        Database::delete('cart', 'id = ?', [$item['id']]);
                    } else {
                        Database::update('cart', ['user_id' => $userId, 'session_id' => null, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$item['id']]);
                    }
                }
            }

            $user = Auth::user();
            if (in_array($user['role'], ['super_admin', 'admin'])) Redirect::to('/admin');
            elseif ($user['role'] === 'cashier') Redirect::to('/pos');
            else Redirect::to('/');
        }
        Session::flash('error', 'Invalid email or password');
        Redirect::to('/login');
    }

    /**
     * Show registration page.
     * Route: GET /register
     */
    public function showRegister(): void
    {
        if (Auth::check()) Redirect::to('/');
        include ROOT_PATH . '/resources/views/auth/register.php';
    }

    /**
     * Process registration.
     * Route: POST /register
     */
    public function register(): void
    {
        $name = Request::post('name', '');
        $email = Request::post('email', '');
        $password = Request::post('password', '');
        $confirm = Request::post('password_confirmation', '');
        $referralCodeInput = strtoupper(trim(Request::post('referral_code', '')));
        if ($password !== $confirm) { Session::flash('error', 'Passwords do not match'); Redirect::to('/register'); }
        $existing = Database::selectOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) { Session::flash('error', 'Email already registered'); Redirect::to('/register'); }

        // Validate referral code if provided
        $validRefCode = '';
        if (!empty($referralCodeInput)) {
            $refCheck = Database::selectOne("SELECT * FROM referrals WHERE referral_code = ?", [$referralCodeInput]);
            if (!$refCheck) { Session::flash('error', 'Invalid referral code. Please check and try again.'); Redirect::to('/register'); }
            $validRefCode = $referralCodeInput;
        } elseif (isset($_COOKIE['referral_code'])) {
            $validRefCode = $_COOKIE['referral_code'];
        }

        // Remember session_id before register (for cart merge)
        $sessionId = session_id();

        Auth::register(['name' => $name, 'email' => $email, 'password' => $password, 'phone' => Request::post('phone', '')]);

        // Generate referral code for new user
        if (Auth::check()) {
            $userId = Auth::id();
            $myReferralCode = 'REF' . strtoupper(substr(md5($userId . time()), 0, 8));
            try { Database::insert('referrals', ['referrer_id' => $userId, 'referral_code' => $myReferralCode, 'created_at' => date('Y-m-d H:i:s')]); } catch(\Throwable $e) {}

            // If user registered with a referral code, store it as a cookie for order tracking
            if (!empty($validRefCode)) {
                setcookie('referral_code', $validRefCode, time() + 86400 * 90, '/');
            }
        }

        // Merge guest cart items into newly registered user cart
        if (Auth::check()) {
            $userId = Auth::id();
            $guestItems = Database::select("SELECT * FROM cart WHERE session_id = ? AND (user_id IS NULL OR user_id = 0)", [$sessionId]);
            foreach ($guestItems as $item) {
                $existingCart = Database::selectOne("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?", [$userId, $item['product_id']]);
                if ($existingCart) {
                    Database::update('cart', ['quantity' => $existingCart['quantity'] + $item['quantity'], 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$existingCart['id']]);
                    Database::delete('cart', 'id = ?', [$item['id']]);
                } else {
                    Database::update('cart', ['user_id' => $userId, 'session_id' => null, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$item['id']]);
                }
            }
        }

        // Send welcome email (non-blocking)
        try {
            if (class_exists('Mailer') && Mailer::isConfigured()) {
                $storeName = Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_name'")['value'] ?? 'ShopSmart';
                $name = $name; $loginUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/login';
                ob_start();
                include ROOT_PATH . '/resources/views/emails/welcome.php';
                $emailBody = ob_get_clean();
                Mailer::send($email, "Welcome to {$storeName}!", $emailBody);
            }
        } catch (\Throwable $e) {
            error_log('[EMAIL] Welcome email failed: ' . $e->getMessage());
        }

        Redirect::to('/');
    }

    /**
     * Show forgot password page.
     * Route: GET /forgot-password
     */
    public function showForgotPassword(): void
    {
        if (Auth::check()) Redirect::to('/');
        ob_start();
        include ROOT_PATH . '/resources/views/auth/forgot-password.php';
        $content = ob_get_clean();
        echo $content;
    }

    /**
     * Send password reset link.
     * Route: POST /forgot-password
     */
    public function sendResetLink(): void
    {
        $email = trim(Request::post('email', ''));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Please enter a valid email address');
            Redirect::to('/forgot-password');
        }

        $user = Database::selectOne("SELECT id, name, email FROM users WHERE email = ?", [$email]);
        if ($user) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            Database::delete('password_resets', 'email = ?', [$email]);
            Database::insert('password_resets', ['email' => $email, 'token' => $token, 'created_at' => date('Y-m-d H:i:s')]);

            // Send reset email
            try {
                if (class_exists('Mailer') && Mailer::isConfigured()) {
                    $storeName = Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_name'")['value'] ?? 'ShopSmart';
                    $resetUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/reset-password?token=' . $token;
                    ob_start();
                    $name = $user['name'];
                    include ROOT_PATH . '/resources/views/emails/reset-password.php';
                    $emailBody = ob_get_clean();
                    Mailer::send($email, "Reset your {$storeName} password", $emailBody);
                }
            } catch (\Throwable $e) {
                error_log('[EMAIL] Reset email failed: ' . $e->getMessage());
            }
        }

        // Always show success (prevent email enumeration)
        Session::flash('success', 'If an account exists with that email, a reset link has been sent.');
        Redirect::to('/forgot-password');
    }

    /**
     * Show reset password page.
     * Route: GET /reset-password
     */
    public function showResetPassword($token): void
    {
        if (Auth::check()) Redirect::to('/');
        ob_start();
        include ROOT_PATH . '/resources/views/auth/reset-password.php';
        $content = ob_get_clean();
        echo $content;
    }

    /**
     * Process password reset.
     * Route: POST /reset-password
     */
    public function resetPassword($token): void
    {
        $token = Request::post('token', Request::query('token', ''));
        $password = Request::post('password', '');
        $confirm = Request::post('password_confirmation', '');

        if ($password !== $confirm) { Session::flash('error', 'Passwords do not match'); Redirect::to('/reset-password?token=' . urlencode($token)); }
        if (strlen($password) < 6) { Session::flash('error', 'Password must be at least 6 characters'); Redirect::to('/reset-password?token=' . urlencode($token)); }

        $reset = Database::selectOne("SELECT * FROM password_resets WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)", [$token]);
        if (!$reset) { Session::flash('error', 'Invalid or expired reset link. Please request a new one.'); Redirect::to('/forgot-password'); }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        Database::update('users', ['password' => $hashed, 'updated_at' => date('Y-m-d H:i:s')], 'email = ?', [$reset['email']]);
        Database::delete('password_resets', 'email = ?', [$reset['email']]);

        // Send password changed notification
        try {
            if (class_exists('Mailer') && Mailer::isConfigured()) {
                $user = Database::selectOne("SELECT name, email FROM users WHERE email = ?", [$reset['email']]);
                if ($user) {
                    $storeName = Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_name'")['value'] ?? 'ShopSmart';
                    ob_start();
                    $name = $user['name'];
                    include ROOT_PATH . '/resources/views/emails/password-changed.php';
                    $emailBody = ob_get_clean();
                    Mailer::send($user['email'], "Your {$storeName} password has been changed", $emailBody);
                }
            }
        } catch (\Throwable $e) {
            error_log('[EMAIL] Password changed notification failed: ' . $e->getMessage());
        }

        Session::flash('success', 'Password reset successfully. Please login with your new password.');
        Redirect::to('/login');
    }

    /**
     * Log out the current user.
     * Route: GET /logout
     */
    public function logout(): void
    {
        Auth::logout();
        Redirect::to('/');
    }
}
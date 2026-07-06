<?php

/**
 * Base Controller
 *
 * Provides shared helper methods used across all API controllers.
 * These were previously global functions in routes/web.php.
 */
class BaseController
{
    /**
     * Log a message to the POS debug log file.
     */
    protected function posLog(string $msg, array $ctx = []): void
    {
        $ts = date('Y-m-d H:i:s');
        $line = "[$ts] $msg";
        if ($ctx) $line .= " | " . json_encode($ctx, JSON_UNESCAPED_SLASHES);
        $line .= "\n";
        @file_put_contents(ROOT_PATH . '/storage/logs/pos-debug.log', $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Return JSON and exit safely (cleans all output buffers first).
     */
    protected function posJson(array $data, int $code = 200): void
    {
        while (ob_get_level()) ob_end_clean();
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Safe JSON parse of request body (works for both FormData and JSON).
     */
    protected function posInput(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            if (!is_array($data)) {
                $this->posLog('INVALID_JSON_BODY', ['content_type' => $contentType, 'raw_first_200' => substr($raw, 0, 200)]);
                return [];
            }
            return $data;
        }
        return $_POST;
    }

    /**
     * Safely insert with column fallback - try full insert, on column error try without optional columns.
     */
    protected function posSafeInsert(string $table, array $requiredCols, array $optionalCols = []): int
    {
        try {
            $allCols = array_merge($requiredCols, $optionalCols);
            return Database::insert($table, $allCols);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'column') || str_contains($msg, 'field') || str_contains($msg, 'unknown column')) {
                $this->posLog("FALLBACK_INSERT_NO_OPTIONALS $table", ['error' => $msg, 'skipped_cols' => array_keys($optionalCols)]);
                $id = Database::insert($table, $requiredCols);
                foreach ($optionalCols as $col => $val) {
                    try {
                        Database::update($table, [$col => $val], 'id = ?', [$id]);
                    } catch (\Throwable $colErr) {
                        // Column doesn't exist - skip it silently
                    }
                }
                return $id;
            }
            throw $e;
        }
    }

    /**
     * Get a setting value from the database.
     */
    protected function getSetting(string $key): string
    {
        try {
            $row = Database::selectOne("SELECT value FROM settings WHERE `key` = ?", [$key]);
            return $row ? $row['value'] : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Get a setting value, or insert and return a default.
     */
    protected function ensureSetting(string $key, string $default): string
    {
        $val = $this->getSetting($key);
        if (empty($val)) {
            try {
                Database::insert('settings', [
                    'key' => $key, 'value' => $default, 'group_name' => 'payment',
                    'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {}
            return $default;
        }
        return $val;
    }

    /**
     * Build the site URL from settings or server vars.
     */
    protected function getSiteUrl(): string
    {
        $siteUrl = $this->getSetting('site_url');
        if (empty($siteUrl)) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $siteUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        }
        return rtrim($siteUrl, '/');
    }

    /**
     * Require authentication or return JSON error.
     */
    protected function requireAuth(): bool
    {
        if (!Auth::check()) {
            $this->posJson(['success' => false, 'error' => 'Not authenticated'], 401);
            return false;
        }
        return true;
    }

    /**
     * Process referral commission when an order is paid.
     */
    protected function processReferralCommission(int $orderId): void
    {
        try {
            $order = Database::selectOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
            if (!$order || empty($order['referral_code']) || empty($order['customer_id'])) return;
            $referralCode = $order['referral_code'];
            $ref = Database::selectOne("SELECT * FROM referrals WHERE referral_code = ? AND referred_id IS NULL", [$referralCode]);
            if (!$ref || $ref['referrer_id'] == $order['customer_id']) return;

            $existing = Database::selectOne("SELECT id FROM referrals WHERE order_id = ? AND referred_id = ?", [$orderId, $order['customer_id']]);
            if ($existing) return;

            $referralEnabled = Database::selectOne("SELECT value FROM settings WHERE `key` = 'referral_enabled'")['value'] ?? '1';
            if ($referralEnabled !== '1') return;

            $commissionRate = (float)(Database::selectOne("SELECT value FROM settings WHERE `key` = 'referral_commission_rate'")['value'] ?? 5);
            $commission = (float)$order['total'] * ($commissionRate / 100);

            Database::update('referrals', [
                'referred_id' => $order['customer_id'],
                'status' => 'completed',
                'order_id' => $orderId,
                'order_total' => (float)$order['total'],
                'commission_amount' => $commission,
                'completed_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$ref['id']]);

            try {
                Database::insert('commissions', [
                    'user_id' => $ref['referrer_id'],
                    'order_id' => $orderId,
                    'order_number' => $order['order_number'] ?? '',
                    'amount' => $commission,
                    'percentage' => $commissionRate,
                    'order_total' => (float)$order['total'],
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {}
        } catch (\Throwable $e) {
            @file_put_contents(ROOT_PATH . '/storage/logs/referral.log', date('Y-m-d H:i:s') . " | ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }

    /**
     * Normalize a Kenyan phone number to 254XXXXXXXXX format.
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) $phone = '254' . substr($phone, 1);
        elseif (str_starts_with($phone, '+')) $phone = substr($phone, 1);
        return $phone;
    }
}
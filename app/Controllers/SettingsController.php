<?php

/**
 * Settings Controller
 *
 * Provides public site settings from the database.
 * Used by frontend to get store name, currency symbol, shipping threshold, etc.
 */
class SettingsController extends BaseController
{
    /**
     * Return public-facing site settings as JSON.
     * Called by frontend JS and layouts to get dynamic settings.
     */
    public function publicSettings(): void
    {
        header('Content-Type: application/json');

        try {
            $rows = Database::select("SELECT `key`, `value` FROM settings");
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row['key']] = $row['value'];
            }
        } catch (\Throwable $e) {
            $settings = [];
        }

        $this->posJson([
            'success'       => true,
            'store_name'         => $settings['store_name'] ?? 'ShopSmart',
            'currency_symbol'    => $settings['currency_symbol'] ?? 'KSh',
            'currency'           => $settings['currency'] ?? 'KES',
            'tax_rate'           => (float)($settings['tax_rate'] ?? 16),
            'shipping_threshold' => (float)($settings['shipping_threshold'] ?? 5000),
            'shipping_banner_text' => $settings['shipping_banner_text'] ?? '',
            'store_email'        => $settings['store_email'] ?? '',
            'store_phone'        => $settings['store_phone'] ?? '',
            'store_address'      => $settings['store_address'] ?? '',
            'store_tagline'      => $settings['store_tagline'] ?? '',
            'site_logo'          => $settings['site_logo'] ?? '',
            'site_favicon'       => $settings['site_favicon'] ?? '',
            'primary_color'      => $settings['primary_color'] ?? '#d97706',
            'primary_hover_color' => $settings['primary_hover_color'] ?? '#b45309',
            'header_bg_color'    => $settings['header_bg_color'] ?? '#ffffff',
            'footer_bg_color'    => $settings['footer_bg_color'] ?? '#111827',
            'social_facebook'    => $settings['social_facebook'] ?? '',
            'social_twitter'     => $settings['social_twitter'] ?? '',
            'social_instagram'   => $settings['social_instagram'] ?? '',
            'social_youtube'     => $settings['social_youtube'] ?? '',
            'social_tiktok'      => $settings['social_tiktok'] ?? '',
        ]);
    }
}
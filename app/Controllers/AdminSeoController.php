<?php

class AdminSeoController extends BaseController
{
    /**
     * GET /admin/seo
     * SEO management page — meta tags, open graph, robots.txt, sitemap.
     */
    public function index(): void
    {
        $settings = [];
        foreach (Database::select("SELECT * FROM settings") as $s) {
            $settings[$s['key']] = $s['value'];
        }
        ob_start();
        include ROOT_PATH . '/resources/views/admin/seo.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    /**
     * POST /admin/seo/update
     * Save SEO settings.
     */
    public function update(): void
    {
        $fields = [
            'seo_title', 'seo_description', 'seo_keywords',
            'og_title', 'og_description', 'og_image',
            'twitter_card', 'robots_txt', 'canonical_url',
            'seo_facebook_pixel', 'seo_google_analytics', 'seo_google_tag',
        ];

        foreach ($fields as $f) {
            $val = Request::post($f, '');
            $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$f]);
            if ($existing) {
                Database::update('settings', ['value' => $val], 'id = ?', [$existing['id']]);
            } else {
                Database::insert('settings', ['`key`' => $f, 'value' => $val]);
            }
        }

        Session::set('success', 'SEO settings saved successfully');
        Redirect::to('/admin/seo');
    }

    /**
     * GET /admin/sitemap
     * Sitemap editor page.
     */
    public function sitemap(): void
    {
        $sitemapPath = ROOT_PATH . '/public/sitemap.xml';
        $sitemapContent = '';
        if (file_exists($sitemapPath)) {
            $sitemapContent = file_get_contents($sitemapPath);
        }

        ob_start();
        include ROOT_PATH . '/resources/views/admin/sitemap.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    /**
     * POST /admin/sitemap/save
     * Save sitemap.xml content and optionally auto-generate from database.
     */
    public function saveSitemap(): void
    {
        $sitemapPath = ROOT_PATH . '/public/sitemap.xml';

        if (Request::post('auto_generate') === '1') {
            $sitemapContent = $this->generateSitemap();
        } else {
            $sitemapContent = Request::post('sitemap_content', '');
        }

        file_put_contents($sitemapPath, $sitemapContent);
        Session::set('success', 'Sitemap saved successfully');
        Redirect::to('/admin/sitemap');
    }

    /**
     * GET /admin/sitemap/generate
     * Generate sitemap from database products, categories, pages and return as JSON.
     */
    public function generateSitemapPreview(): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'sitemap' => $this->generateSitemap()]);
    }

    /**
     * Generate sitemap XML from database content.
     */
    private function generateSitemap(): string
    {
        $siteUrl = Database::selectOne("SELECT value FROM settings WHERE `key` = 'site_url'")['value'] ?? '';
        if (empty($siteUrl)) {
            $siteUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        }
        $siteUrl = rtrim($siteUrl, '/');
        $today = date('Y-m-d');

        $urls = [];
        $priorities = ['/' => '1.0'];
        $changefreqs = ['/' => 'daily'];

        // Static pages
        $staticPages = [
            '/' => 'daily', '/products' => 'daily', '/categories' => 'weekly',
            '/account' => 'monthly', '/cart' => 'monthly',
        ];
        foreach ($staticPages as $page => $freq) {
            $urls[] = ['loc' => $siteUrl . $page, 'changefreq' => $freq, 'priority' => $page === '/' ? '1.0' : '0.8'];
        }

        // Categories
        $categories = Database::select("SELECT slug, updated_at FROM categories WHERE is_active = 1");
        foreach ($categories as $cat) {
            $urls[] = [
                'loc' => $siteUrl . '/category/' . $cat['slug'],
                'changefreq' => 'weekly',
                'priority' => '0.7',
                'lastmod' => $cat['updated_at'] ?? $today,
            ];
        }

        // Products
        $products = Database::select("SELECT slug, updated_at FROM products WHERE status = 'active'");
        foreach ($products as $p) {
            $urls[] = [
                'loc' => $siteUrl . '/product/' . $p['slug'],
                'changefreq' => 'weekly',
                'priority' => '0.6',
                'lastmod' => $p['updated_at'] ?? $today,
            ];
        }

        // Brands
        $brands = Database::select("SELECT slug FROM brands WHERE is_active = 1");
        foreach ($brands as $b) {
            $urls[] = [
                'loc' => $siteUrl . '/brand/' . $b['slug'],
                'changefreq' => 'weekly',
                'priority' => '0.5',
            ];
        }

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($u['loc']) . "</loc>\n";
            if (!empty($u['lastmod'])) {
                $xml .= "    <lastmod>" . date('Y-m-d', strtotime($u['lastmod'])) . "</lastmod>\n";
            }
            $xml .= "    <changefreq>" . $u['changefreq'] . "</changefreq>\n";
            $xml .= "    <priority>" . $u['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= "</urlset>";

        return $xml;
    }
}
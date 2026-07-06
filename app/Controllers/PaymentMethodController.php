<?php



class PaymentMethodController extends BaseController
{
    /**
     * List all custom payment methods (admin only).
     * GET
     */
    public function index(): void
    {
        try {
            header('Content-Type: application/json');
            if (!Auth::check() || !Auth::isAdmin()) {
                http_response_code(403);
                $this->posJson(['error' => 'Access denied']);
                return;
            }
            $methods = Database::select("SELECT * FROM custom_payment_methods ORDER BY sort_order ASC, id ASC");
            $this->posJson(['success' => true, 'methods' => $methods]);
        } catch (\Throwable $e) {
            http_response_code(500);
            $this->posJson(['error' => $e->getMessage()]);
        }
    }

    /**
     * Create a new custom payment method (admin only).
     * POST
     */
    public function store(): void
    {
        try {
            header('Content-Type: application/json');
            if (!Auth::check() || !Auth::isAdmin()) {
                http_response_code(403);
                $this->posJson(['error' => 'Access denied']);
                return;
            }
            $name = trim($_POST['name'] ?? '');
            $icon = trim($_POST['icon'] ?? 'credit-card');
            $color = trim($_POST['color'] ?? 'gray');
            if (empty($name)) {
                http_response_code(400);
                $this->posJson(['error' => 'Name is required']);
                return;
            }
            $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
            $slug = trim($slug, '-');
            if (empty($slug)) {
                $slug = 'method-' . time();
            }
            $exists = Database::selectOne("SELECT id FROM custom_payment_methods WHERE slug = ?", [$slug]);
            if ($exists) {
                $slug .= '-' . time();
            }
            $maxSort = Database::selectOne("SELECT COALESCE(MAX(sort_order), 0) + 1 as next FROM custom_payment_methods")['next'] ?? 1;
            Database::insert('custom_payment_methods', [
                'name' => $name,
                'slug' => $slug,
                'icon' => $icon,
                'color' => $color,
                'is_active' => 1,
                'sort_order' => (int)$maxSort,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->posJson(['success' => true, 'message' => 'Payment method added']);
        } catch (\Throwable $e) {
            http_response_code(500);
            $this->posJson(['error' => $e->getMessage()]);
        }
    }

    /**
     * Update an existing custom payment method (admin only).
     * PUT/POST
     */
    public function update(int $id): void
    {
        try {
            header('Content-Type: application/json');
            if (!Auth::check() || !Auth::isAdmin()) {
                http_response_code(403);
                $this->posJson(['error' => 'Access denied']);
                return;
            }
            $name = trim($_POST['name'] ?? '');
            $icon = trim($_POST['icon'] ?? '');
            $color = trim($_POST['color'] ?? '');
            $isActive = $_POST['is_active'] ?? null;

            $update = ['updated_at' => date('Y-m-d H:i:s')];
            if ($name !== '') {
                $update['name'] = $name;
            }
            if ($icon !== '') {
                $update['icon'] = $icon;
            }
            if ($color !== '') {
                $update['color'] = $color;
            }
            if ($isActive !== null) {
                $update['is_active'] = ($isActive === '1' || $isActive === 'true') ? 1 : 0;
            }

            Database::update('custom_payment_methods', $update, 'id = ?', [$id]);
            $this->posJson(['success' => true, 'message' => 'Updated']);
        } catch (\Throwable $e) {
            http_response_code(500);
            $this->posJson(['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete a custom payment method (admin only).
     * DELETE
     */
    public function delete(int $id): void
    {
        try {
            header('Content-Type: application/json');
            if (!Auth::check() || !Auth::isAdmin()) {
                http_response_code(403);
                $this->posJson(['error' => 'Access denied']);
                return;
            }
            Database::delete('custom_payment_methods', 'id = ?', [$id]);
            $this->posJson(['success' => true, 'message' => 'Deleted']);
        } catch (\Throwable $e) {
            http_response_code(500);
            $this->posJson(['error' => $e->getMessage()]);
        }
    }
}
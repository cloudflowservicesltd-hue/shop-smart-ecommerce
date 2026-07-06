<?php

class AdminApiIntegrationController extends BaseController
{
    public function index(): void
    {
        $breadcrumbs = [['API Integrations', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/api-integrations.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }
}
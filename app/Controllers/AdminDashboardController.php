<?php

class AdminDashboardController extends BaseController
{
    public function index()
    {
        $pageTitle = 'Dashboard - Admin';
        $breadcrumbs = [];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/dashboard.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }
}
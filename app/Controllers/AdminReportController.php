<?php

class AdminReportController extends BaseController
{
    public function reports(): void
    {
        $breadcrumbs = [['Reports', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/reports.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function analytics(): void
    {
        $breadcrumbs = [['Analytics', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/analytics.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }
}
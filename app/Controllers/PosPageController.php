<?php

/**
 * POS Page Controller
 *
 * Handles POS terminal page views (not API routes).
 * API routes are in PosController.
 */
class PosPageController extends BaseController
{
    public function terminal(): void
    {
        if (!Auth::check() || !Auth::hasRole(['admin', 'super_admin', 'cashier'])) {
            Session::flash('error', 'Access denied.');
            Redirect::to('/');
        }
        Session::set('pos_cart', Session::get('pos_cart', []));
        include ROOT_PATH . '/resources/views/pos/terminal.php';
    }

    public function holds(): void
    {
        if (!Auth::check() || !Auth::hasRole(['admin', 'super_admin', 'cashier'])) {
            Session::flash('error', 'Access denied.');
            Redirect::to('/');
        }
        include ROOT_PATH . '/resources/views/pos/holds.php';
    }

    public function cashier(): void
    {
        if (!Auth::check() || !Auth::hasRole(['admin', 'super_admin', 'cashier'])) {
            Session::flash('error', 'Access denied.');
            Redirect::to('/');
        }
        $pageTitle = 'Cashier Dashboard';
        ob_start();
        include ROOT_PATH . '/resources/views/cashier/dashboard.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }
}
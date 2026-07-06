<?php
/**
 * Root entry point.
 *
 * 1. Auto-runs any pending database migrations (database/install.php).
 * 2. Redirects to the public/ directory which is the real document root.
 *
 * To add a new table or modify the schema:
 *   - Create a new .php file in database/migrations/ (e.g. add_my_table.php)
 *   - Use CREATE TABLE IF NOT EXISTS so it's idempotent
 *   - It will be auto-detected and executed on the next request
 *   - Already-run migrations are tracked and skipped
 */

// ─── Auto-migrate: run any pending database migrations ──────────────────────
require_once __DIR__ . '/database/install.php';

// ─── Redirect to the public directory ───────────────────────────────────────
header('Location: /public/index.php' . (isset($_SERVER['REQUEST_URI']) ? str_replace('/index.php', '', $_SERVER['REQUEST_URI']) : '/'));
exit;
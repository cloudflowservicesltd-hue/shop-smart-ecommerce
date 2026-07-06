<?php
/**
 * Auto-Migration Installer
 *
 * Scans database/migrations/ and runs any pending migration files.
 * Tracks completed migrations in a `migrations` table.
 *
 * Usage:
 *   - Standalone:  php database/install.php
 *   - Auto-run:    Included by index.php on every request (only runs pending)
 *
 * When adding a new migration, simply drop a new .php file into
 * database/migrations/ — it will be auto-detected and executed.
 */

// ─── Bootstrap (only when running standalone, not from public/index.php) ───
$isCli = (php_sapi_name() === 'cli');
$quiet  = $isCli && in_array('--quiet', $argv ?: []);

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
    require_once ROOT_PATH . '/config/app.php';
    require_once ROOT_PATH . '/app/Core/Database.php';
}

// ─── Connect (gracefully fail if DB is not reachable) ─────────────────────
try {
    $db = Database::getConnection();
} catch (\PDOException $e) {
    error_log("[MIGRATION] Database connection failed: " . $e->getMessage());
    return;
}

// ─── Create migrations tracking table (MySQL) ───────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS `migrations` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `file`       VARCHAR(255) NOT NULL UNIQUE,
    `batch`      INT NOT NULL DEFAULT 1,
    `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ─── Scan migrations directory ──────────────────────────────────────────────
$migrationsDir = ROOT_PATH . '/database/migrations';

if (!is_dir($migrationsDir)) {
    @mkdir($migrationsDir, 0755, true);
    return;
}

$files = glob($migrationsDir . '/*.php');

if (empty($files)) {
    return;
}

// Sort files alphabetically for deterministic order
sort($files);

// ─── Determine pending migrations ───────────────────────────────────────────
$alreadyRun = $db->query("SELECT `file` FROM `migrations`")->fetchAll(PDO::FETCH_COLUMN, 0);

$pending = [];
foreach ($files as $file) {
    $basename = basename($file);
    // Skip the install.php loader itself (if someone copies it there)
    if ($basename === 'install.php') {
        continue;
    }
    if (!in_array($basename, $alreadyRun)) {
        $pending[] = ['path' => $file, 'name' => $basename];
    }
}

if (empty($pending)) {
    return;
}

// ─── Run pending migrations ─────────────────────────────────────────────────
$batch = (int) $db->query("SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`")->fetchColumn();

foreach ($pending as $migration) {
    $startTime = microtime(true);

    // Each migration file must be self-contained:
    //   - Define ROOT_PATH if not already set
    //   - Require the Database class
    //   - Use CREATE TABLE IF NOT EXISTS (idempotent)
    // Suppress "Constant already defined" warnings from migrations that don't guard properly
    $previousHandler = set_error_handler(function() { return true; });
    ob_start();
    try {
        require $migration['path'];
        $output = ob_get_clean();
        $success = true;
    } catch (\Throwable $e) {
        ob_end_clean();
        set_error_handler($previousHandler);
        $success = false;
        $output  = $e->getMessage();

        if (!$quiet) {
            $logMsg = sprintf(
                "[MIGRATION ERROR] %s — %s\n",
                $migration['name'],
                $output
            );
            // Write to error log instead of echoing
            error_log($logMsg);

            if ($isCli) {
                fwrite(STDERR, $logMsg);
            }
        }

        // Stop on first failure to avoid partial state
        break;
    }

    // Restore error handler
    set_error_handler($previousHandler);

    // Record successful migration
    $stmt = $db->prepare("INSERT INTO `migrations` (`file`, `batch`, `executed_at`) VALUES (?, ?, NOW())");
    $stmt->execute([$migration['name'], $batch]);

    if (!$quiet) {
        $elapsed = round((microtime(true) - $startTime) * 1000, 1);
        $logMsg = sprintf("[MIGRATION] %s (%.1fms)\n", $migration['name'], $elapsed);
        error_log($logMsg);

        if ($isCli) {
            echo $logMsg;
        }
    }
}

// ─── CLI summary ────────────────────────────────────────────────────────────
if ($isCli && !$quiet) {
    $total = count($pending);
    $ran   = $success ? $total : array_search($migration['name'], array_column($pending, 'name')) + 1;
    $failed = $total - $ran;

    echo "\n--- Migration Summary ---\n";
    echo "Pending:  {$total}\n";
    echo "Ran:      {$ran}\n";
    echo "Skipped:  " . (count($alreadyRun)) . " (previously run)\n";
    echo "Failed:   {$failed}\n";

    // Show current migration status
    $all = $db->query("SELECT `file`, `executed_at` FROM `migrations` ORDER BY id")->fetchAll();
    echo "\nAll migrations (" . count($all) . "):\n";
    foreach ($all as $row) {
        echo "  ✓ {$row['file']}  ({$row['executed_at']})\n";
    }
}
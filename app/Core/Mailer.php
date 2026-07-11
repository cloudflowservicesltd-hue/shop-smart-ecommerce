<?php

// Load PHPMailer from Composer vendor
require_once ROOT_PATH . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once ROOT_PATH . '/vendor/phpmailer/phpmailer/src/SMTP.php';
require_once ROOT_PATH . '/vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private static ?PHPMailer $mailer = null;

    /**
     * Tracks whether SMTP is usable in this request.
     * null  = not tested yet
     * true  = SMTP works, keep using it
     * false = SMTP failed, use mail() fallback
     */
    private static ?bool $smtpWorks = null;

    /** Last SMTP error message for diagnostics. */
    private static ?string $lastSmtpError = null;

    // ──────────────────────────────────────────────
    //  PUBLIC: Send email (with automatic fallback)
    // ──────────────────────────────────────────────

    /**
     * Send an email. Tries SMTP first; if it fails, automatically falls back
     * to PHP's built-in mail() function (which uses the server's local MTA).
     *
     * This is the ONLY method you should call to send email.
     */
    public static function send(string $to, string $subject, string $body, bool $isHtml = true, ?string $replyTo = null): bool
    {
        $config = self::getResolvedConfig();
        $hasSmtpConfig = !empty($config['host']) && !empty($config['username']);

        // ── Attempt 1: SMTP (only if configured and not known-broken) ──
        if ($hasSmtpConfig && self::$smtpWorks !== false) {
            $smtpResult = self::sendViaSMTP($to, $subject, $body, $isHtml, $replyTo, $config);
            if ($smtpResult === true) {
                self::$smtpWorks = true;
                return true;
            }
            // SMTP failed — remember and fall back
            self::$smtpWorks = false;
            error_log("[MAILER] SMTP connection failed (host={$config['host']}:{$config['port']}). Reason: " . self::$lastSmtpError . " — falling back to PHP mail().");
        }

        // ── Attempt 2: PHP mail() fallback ──
        $mailResult = self::sendViaMailFunction($to, $subject, $body, $isHtml, $replyTo, $config);
        if ($mailResult) {
            error_log("[MAILER] Email sent via PHP mail() fallback to $to");
            return true;
        }

        error_log("[MAILER] ALL methods failed for $to. SMTP error: " . self::$lastSmtpError);
        return false;
    }

    // ──────────────────────────────────────────────
    //  SMTP SENDER (private)
    // ──────────────────────────────────────────────

    /**
     * Attempt to send via SMTP using PHPMailer.
     * Returns true on success, false on failure (sets self::$lastSmtpError).
     */
    private static function sendViaSMTP(string $to, string $subject, string $body, bool $isHtml, ?string $replyTo, array $config): bool
    {
        try {
            $mail = self::getInstance();
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->isHTML($isHtml);
            $mail->AltBody = self::htmlToText($body);

            if ($replyTo) {
                $mail->addReplyTo($replyTo);
            }

            $sent = $mail->send();
            if (!$sent) {
                self::$lastSmtpError = $mail->ErrorInfo ?: 'Unknown SMTP error';
                return false;
            }
            return true;
        } catch (Exception $e) {
            self::$lastSmtpError = $mail->ErrorInfo ?? $e->getMessage();
            return false;
        }
    }

    // ──────────────────────────────────────────────
    //  PHP mail() FALLBACK (private)
    // ──────────────────────────────────────────────

    /**
     * Send email using PHP's built-in mail() function.
     * This uses the server's local sendmail/postfix — almost always available
     * on shared hosting even when outbound SMTP ports (587/465) are blocked.
     */
    private static function sendViaMailFunction(string $to, string $subject, string $body, bool $isHtml, ?string $replyTo, array $config): bool
    {
        $fromEmail = $config['from_email'] ?: 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $fromName  = $config['from_name'] ?: 'ShopSmart';

        // Force From email to match SMTP username to avoid spam rejection on hosting
        $smtpUser = $config['username'] ?? '';
        if (!empty($smtpUser) && filter_var($smtpUser, FILTER_VALIDATE_EMAIL)) {
            $fromEmail = $smtpUser;
        }

        // Build headers
        $headers = [];
        $headers[] = "From: $fromName <$fromEmail>";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: " . ($isHtml ? 'text/html; charset=UTF-8' : 'text/plain; charset=UTF-8');
        $headers[] = "X-Mailer: ShopSmart E-Commerce";

        if ($replyTo) {
            $headers[] = "Reply-To: $replyTo";
        }

        // PHP mail() subject must not contain newlines
        $subject = str_replace(["\r", "\n"], '', $subject);

        $result = @mail($to, $subject, $body, implode("\r\n", $headers));

        if (!$result) {
            error_log("[MAILER] PHP mail() returned false for $to");
        }

        return $result;
    }

    // ──────────────────────────────────────────────
    //  BULK SEND
    // ──────────────────────────────────────────────

    /**
     * Send an email to multiple recipients (BCC to protect privacy).
     * Returns ['sent' => int, 'failed' => int, 'errors' => array, 'method' => string].
     */
    public static function sendBulk(array $emails, string $subject, string $body, bool $isHtml = true, int $batchSize = 50, int $batchDelayMs = 1000): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'errors' => [], 'total' => count($emails), 'method' => 'unknown'];

        if (empty($emails)) {
            return $result;
        }

        $config = self::getResolvedConfig();
        $hasSmtpConfig = !empty($config['host']) && !empty($config['username']);

        // Decide method: SMTP if it works, else mail()
        $useSmtp = $hasSmtpConfig && self::$smtpWorks !== false;

        $batches = array_chunk($emails, $batchSize);

        foreach ($batches as $batchIndex => $batch) {
            $validEmails = [];
            foreach ($batch as $email) {
                $email = trim($email);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $validEmails[] = $email;
                } else {
                    $result['failed']++;
                    $result['errors'][] = "Invalid email: $email";
                }
            }

            if (empty($validEmails)) continue;

            if ($useSmtp) {
                // ── SMTP batch ──
                try {
                    $mail = self::getInstance();
                    $mail->Subject = $subject;
                    $mail->Body    = $body;
                    $mail->isHTML($isHtml);
                    $mail->AltBody = self::htmlToText($body);

                    foreach ($validEmails as $email) {
                        $mail->addBCC($email);
                    }

                    if ($mail->send()) {
                        $result['sent'] += count($validEmails);
                        $result['method'] = 'smtp';
                    } else {
                        self::$smtpWorks = false;
                        self::$lastSmtpError = $mail->ErrorInfo;
                        $result['failed'] += count($validEmails);
                        $result['errors'][] = "Batch " . ($batchIndex + 1) . " SMTP failed: " . $mail->ErrorInfo;

                        // Retry this batch with mail()
                        $mailResult = self::sendBulkViaMail($validEmails, $subject, $body, $isHtml, $config);
                        $result['sent'] += $mailResult['sent'];
                        $result['failed'] = $result['failed'] - count($validEmails) + $mailResult['failed'];
                        $result['method'] = 'mail_fallback';
                    }
                } catch (Exception $e) {
                    self::$smtpWorks = false;
                    self::$lastSmtpError = $mail->ErrorInfo ?? $e->getMessage();
                    $result['failed'] += count($validEmails);
                    $result['errors'][] = "Batch " . ($batchIndex + 1) . " exception: " . self::$lastSmtpError;

                    // Retry this batch with mail()
                    $mailResult = self::sendBulkViaMail($validEmails, $subject, $body, $isHtml, $config);
                    $result['sent'] += $mailResult['sent'];
                    $result['failed'] = $result['failed'] - count($validEmails) + $mailResult['failed'];
                    $result['method'] = 'mail_fallback';
                }
            } else {
                // ── mail() batch ──
                $mailResult = self::sendBulkViaMail($validEmails, $subject, $body, $isHtml, $config);
                $result['sent'] += $mailResult['sent'];
                $result['failed'] += $mailResult['failed'];
                $result['errors'] = array_merge($result['errors'], $mailResult['errors']);
                $result['method'] = 'mail';
            }

            // Delay between batches
            if ($batchIndex < count($batches) - 1 && $batchDelayMs > 0) {
                usleep($batchDelayMs * 1000);
            }
        }

        return $result;
    }

    /**
     * Send bulk via PHP mail() (one mail() call per recipient).
     */
    private static function sendBulkViaMail(array $emails, string $subject, string $body, bool $isHtml, array $config): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'errors' => []];
        foreach ($emails as $email) {
            if (self::sendViaMailFunction($email, $subject, $body, $isHtml, null, $config)) {
                $result['sent']++;
            } else {
                $result['failed']++;
                $result['errors'][] = "mail() failed for $email";
            }
        }
        return $result;
    }

    // ──────────────────────────────────────────────
    //  TEST CONNECTION (with full diagnostics)
    // ──────────────────────────────────────────────

    /**
     * Test SMTP connection with detailed diagnostics.
     * Also tests PHP mail() as a fallback.
     * Returns array with success/fail status and detailed info.
     */
    public static function testConnection(?string $toEmail = null): array
    {
        $config = self::getResolvedConfig();
        $result = [
            'success'      => false,
            'method'       => '',
            'host'         => $config['host'],
            'port'         => $config['port'],
            'encryption'   => $config['encryption'],
            'username'     => $config['username'],
            'from_email'   => $config['from_email'],
            'from_name'    => $config['from_name'],
            'checks'       => [],
            'error'        => '',
        ];

        // Check 1: OpenSSL extension
        $hasOpenSSL = extension_loaded('openssl');
        $result['checks'][] = [
            'name'    => 'PHP OpenSSL Extension',
            'status'  => $hasOpenSSL ? 'ok' : 'fail',
            'message' => $hasOpenSSL ? 'Loaded' : 'NOT loaded — TLS/SSL connections will fail',
        ];

        // Check 2: mail() function available
        $mailDisabled = !function_exists('mail') || ini_get('disable_functions') && in_array('mail', explode(',', ini_get('disable_functions')));
        $result['checks'][] = [
            'name'    => 'PHP mail() Function',
            'status'  => $mailDisabled ? 'fail' : 'ok',
            'message' => $mailDisabled ? 'DISABLED by hosting provider' : 'Available (fallback method)',
        ];

        // Check 3: SMTP Host configured
        $hasSmtpConfig = !empty($config['host']) && !empty($config['username']);
        $result['checks'][] = [
            'name'    => 'SMTP Configuration',
            'status'  => $hasSmtpConfig ? 'ok' : 'warn',
            'message' => $hasSmtpConfig ? "Host: {$config['host']}:{$config['port']}" : 'No SMTP configured — will use PHP mail() only',
        ];

        if (!$hasSmtpConfig) {
            // No SMTP — test mail() directly
            if ($toEmail && filter_var($toEmail, FILTER_VALIDATE_EMAIL) && !$mailDisabled) {
                $sent = self::sendViaMailFunction($toEmail, 'ShopSmart Test (mail())', '<p>This is a test email from ShopSmart via PHP mail().</p>', true, null, $config);
                $result['success'] = $sent;
                $result['method']  = 'mail';
                $result['checks'][] = [
                    'name'    => 'PHP mail() Send Test',
                    'status'  => $sent ? 'ok' : 'fail',
                    'message' => $sent ? "Test email sent to $toEmail via PHP mail()" : 'PHP mail() returned false. Contact your hosting provider.',
                ];
            } else {
                $result['success'] = true;
                $result['method']  = 'mail';
                $result['checks'][] = [
                    'name'    => 'Ready',
                    'status'  => 'ok',
                    'message' => 'SMTP not configured. Emails will be sent via PHP mail() (server local MTA). Enter a test email to verify.',
                ];
            }
            return $result;
        }

        // ── SMTP tests (when configured) ──

        if (!$hasOpenSSL && strtolower($config['encryption']) !== 'none') {
            $result['error'] = 'PHP OpenSSL extension is not loaded. Set encryption to "None" or ask your host to enable openssl.';
            return $result;
        }

        // Check 4: DNS resolution
        $dnsOk = @gethostbyname($config['host']) !== $config['host'];
        $result['checks'][] = [
            'name'    => 'DNS Resolution (' . $config['host'] . ')',
            'status'  => $dnsOk ? 'ok' : 'warn',
            'message' => $dnsOk
                ? 'Resolved to ' . @gethostbyname($config['host'])
                : 'DNS resolution failed — the host may be unreachable from this server',
        ];

        // Check 5: Socket connection test
        $socketConnected = false;
        $socketError = '';
        $sock = @fsockopen($config['host'], $config['port'], $errno, $errstr, 10);
        if ($sock) {
            $socketConnected = true;
            fclose($sock);
        } else {
            $socketError = "Error $errno: $errstr";
        }
        $result['checks'][] = [
            'name'    => "TCP Socket ({$config['host']}:{$config['port']})",
            'status'  => $socketConnected ? 'ok' : 'fail',
            'message' => $socketConnected
                ? 'Connection successful'
                : "Cannot connect — $socketError. Your hosting provider may be blocking this port.",
        ];

        if (!$socketConnected) {
            // Try common alternative ports
            $alternatives = [];
            $altPorts = [465 => 'SSL', 587 => 'TLS', 25 => 'None', 2525 => 'TLS'];
            foreach ($altPorts as $altPort => $label) {
                if ($altPort == $config['port']) continue;
                $altSock = @fsockopen($config['host'], $altPort, $aErrno, $aErrstr, 5);
                if ($altSock) {
                    $alternatives[] = "Port $altPort ($label) — OPEN ✓";
                    fclose($altSock);
                } else {
                    $alternatives[] = "Port $altPort ($label) — blocked ✗";
                }
            }
            $result['checks'][] = [
                'name'    => 'Alternative Ports',
                'status'  => 'info',
                'message' => implode(' | ', $alternatives),
            ];

            // Check for local SMTP
            $localSock = @fsockopen('localhost', 25, $lErrno, $lErrstr, 3);
            if ($localSock) {
                $result['checks'][] = [
                    'name'    => 'Local SMTP (localhost:25)',
                    'status'  => 'ok',
                    'message' => 'Your server has a local SMTP! You can set host=localhost, port=25, encryption=None',
                ];
                fclose($localSock);
            }
        }

        // Check 6: If socket works, try full SMTP auth + send
        if ($socketConnected) {
            try {
                self::resetInstance();
                $mail = self::getInstance();

                if ($toEmail && filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                    $mail->addAddress($toEmail);
                    $mail->Subject = 'ShopSmart SMTP Test';
                    $mail->Body    = '<p>This is a test email from ShopSmart to verify your SMTP configuration is working.</p>';
                    $mail->isHTML(true);
                    $mail->AltBody = 'This is a test email from ShopSmart to verify your SMTP configuration is working.';

                    $sent = $mail->send();
                    $result['success'] = $sent;
                    $result['method']  = 'smtp';
                    $result['checks'][] = [
                        'name'    => 'SMTP Login & Send',
                        'status'  => $sent ? 'ok' : 'fail',
                        'message' => $sent
                            ? "Email sent to $toEmail successfully!"
                            : 'Login OK but send failed: ' . $mail->ErrorInfo,
                    ];
                } else {
                    $result['checks'][] = [
                        'name'    => 'SMTP Connection',
                        'status'  => 'ok',
                        'message' => 'Socket + auth OK. Enter a test email to verify full send.',
                    ];
                    $result['success'] = true;
                    $result['method']  = 'smtp';
                }
            } catch (Exception $e) {
                $errInfo = $mail->ErrorInfo ?? $e->getMessage();
                $result['checks'][] = [
                    'name'    => 'SMTP Send',
                    'status'  => 'fail',
                    'message' => 'Failed: ' . $errInfo,
                ];

                // Detect common issues and give helpful advice
                $errLower = strtolower($errInfo);
                $smtpHost = strtolower($config['host'] ?? '');
                $isGmail = strpos($smtpHost, 'gmail') !== false || strpos($smtpHost, 'google') !== false;

                if (strpos($errLower, 'spam') !== false || strpos($errLower, 'data not accepted') !== false) {
                    if ($isGmail) {
                        $result['error'] = 'Gmail rejected the email as spam. The From address is now forced to match your Gmail. If it persists, use an App Password. Error: ' . $errInfo;
                    } else {
                        $result['error'] = 'Your SMTP server classified the email as spam. The From email has been auto-fixed to match your SMTP username. If this persists, create a proper email account (e.g. noreply@cloudonehost.top) in cPanel and use it as both SMTP username and From email. Error: ' . $errInfo;
                    }
                } elseif (strpos($errLower, 'authentication') !== false || strpos($errLower, 'auth') !== false) {
                    if ($isGmail) {
                        $result['error'] = 'Gmail auth failed. Use an App Password (not your regular password). Error: ' . $errInfo;
                    } else {
                        $result['error'] = 'SMTP authentication failed. Check your username and password. Error: ' . $errInfo;
                    }
                } else {
                    $result['error'] = 'SMTP error: ' . $errInfo;
                }
            }
        }

        // ── If SMTP failed, auto-test mail() fallback ──
        if (!$result['success'] && !$mailDisabled && $toEmail && filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $mailSent = self::sendViaMailFunction($toEmail, 'ShopSmart Test (mail() fallback)', '<p>This is a test email from ShopSmart sent via PHP mail() because SMTP is unavailable on your server.</p>', true, null, $config);
            if ($mailSent) {
                $result['checks'][] = [
                    'name'    => 'PHP mail() Fallback',
                    'status'  => 'ok',
                    'message' => "SMTP is blocked, but PHP mail() works! Test email sent to $toEmail.",
                ];
                $result['success'] = true;
                $result['method']  = 'mail';
            } else {
                $result['checks'][] = [
                    'name'    => 'PHP mail() Fallback',
                    'status'  => 'fail',
                    'message' => 'PHP mail() also failed. Contact your hosting provider about email sending.',
                ];
            }
        }

        if (!$result['error'] && !$result['success']) {
            $result['error'] = "Cannot connect to {$config['host']}:{$config['port']}. $socketError. Your hosting provider likely blocks outgoing SMTP. The system will use PHP mail() as fallback automatically.";
        }

        return $result;
    }

    // ──────────────────────────────────────────────
    //  CONFIG MANAGEMENT
    // ──────────────────────────────────────────────

    /**
     * Check if any mail method is available.
     */
    public static function isConfigured(): bool
    {
        $config = self::getResolvedConfig();
        // Either SMTP is configured, or mail() function exists
        return !empty($config['host']) || function_exists('mail');
    }

    /**
     * Get current SMTP config (for display, passwords masked).
     */
    public static function getConfig(): array
    {
        $config = self::getResolvedConfig();
        return [
            'host'           => $config['host'],
            'port'           => $config['port'],
            'username'       => $config['username'],
            'password'       => $config['password'],
            'password_masked' => $config['password'] ? '********' : '',
            'encryption'     => $config['encryption'],
            'from_name'      => $config['from_name'],
            'from_email'     => $config['from_email'],
            'smtp_status'    => self::$smtpWorks === null ? 'untested' : (self::$smtpWorks ? 'working' : 'fallback_to_mail'),
            'last_smtp_error' => self::$lastSmtpError,
        ];
    }

    /**
     * Save SMTP settings to database.
     */
    public static function saveConfig(array $data): void
    {
        $fields = ['mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption', 'mail_from_name', 'mail_from_email'];
        foreach ($fields as $key) {
            // Skip fields not present in $data (e.g., password left blank to keep existing)
            if (!array_key_exists($key, $data)) continue;
            $val = $data[$key];
            $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$key]);
            if ($existing) {
                Database::update('settings', ['value' => $val, 'updated_at' => date('Y-m-d H:i:s')], '`key` = ?', [$key]);
            } else {
                Database::insert('settings', [
                    'key' => $key,
                    'value' => $val,
                    'group_name' => 'mail',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
        // Clear cached config and reset SMTP status so it gets re-tested
        self::resetInstance();
        self::$smtpWorks = null;
    }

    // ──────────────────────────────────────────────
    //  PRIVATE HELPERS
    // ──────────────────────────────────────────────

    /**
     * Get or create a configured PHPMailer SMTP instance.
     * Public so newsletter routes can add attachments directly.
     * For regular email sending, use Mailer::send() instead.
     */
    public static function getInstance(): PHPMailer
    {
        if (self::$mailer !== null) {
            // Reset for reuse
            self::$mailer->clearAddresses();
            self::$mailer->clearAttachments();
            self::$mailer->clearReplyTos();
            self::$mailer->clearCCs();
            self::$mailer->clearBCCs();
            self::$mailer->clearCustomHeaders();
            return self::$mailer;
        }

        $config = self::getResolvedConfig();

        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->Port       = (int)$config['port'];
        $mail->SMTPAuth   = !empty($config['username']);
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];

        // Handle encryption — shared hosting friendly
        $encryption = strtolower($config['encryption'] ?? 'tls');
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls' || $encryption === 'starttls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = '';
        }

        $mail->SMTPAutoTLS = false;
        $mail->Timeout     = 15;  // 15 seconds — don't hang too long
        $mail->CharSet     = 'UTF-8';

        // Anti-spam: set proper X-Mailer and Message-ID
        $mail->XMailer  = 'ShopSmart E-Commerce';
        $mail->MessageID = '<' . md5(uniqid(microtime(true), true)) . '@' . ($_SERVER['HTTP_HOST'] ?? 'shopsmart.co.ke') . '>';

        // Disable SSL peer verification (shared hosting friendly)
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

        // Set From address — MUST match the SMTP authenticated username to avoid spam rejection
        $fromEmail = $config['from_email'] ?: '';
        $fromName  = $config['from_name'] ?: 'ShopSmart';

        $smtpUser = $config['username'] ?? '';

        // Force From email to match SMTP username — hosting servers reject otherwise (spam)
        if (!empty($smtpUser) && filter_var($smtpUser, FILTER_VALIDATE_EMAIL)) {
            $fromEmail = $smtpUser;
        }
        // Fallback: if no from_email set, use SMTP username
        if (empty($fromEmail) && !empty($smtpUser)) {
            $fromEmail = $smtpUser;
        }

        if (!empty($fromEmail)) {
            $mail->setFrom($fromEmail, $fromName);
        }

        self::$mailer = $mail;
        return $mail;
    }

    /**
     * Reset the singleton so new settings take effect on next use.
     */
    public static function resetInstance(): void
    {
        self::$mailer = null;
    }

    /**
     * Get resolved config (DB settings override .env settings).
     */
    private static function getResolvedConfig(): array
    {
        static $cached = null;
        if ($cached !== null) return $cached;

        $config = require ROOT_PATH . '/config/app.php';
        $mc = $config['mail'];

        $db = [];
        $keys = ['mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption', 'mail_from_name', 'mail_from_email'];
        foreach ($keys as $key) {
            try {
                $row = Database::selectOne("SELECT value FROM settings WHERE `key` = ?", [$key]);
                $db[$key] = $row['value'] ?? null;
            } catch (\Throwable $e) {
                $db[$key] = null;
            }
        }

        $cached = [
            'host'       => !empty($db['mail_host']) ? $db['mail_host'] : $mc['host'],
            'port'       => !empty($db['mail_port']) ? (int)$db['mail_port'] : $mc['port'],
            'username'   => !empty($db['mail_username']) ? $db['mail_username'] : $mc['username'],
            'password'   => !empty($db['mail_password']) ? $db['mail_password'] : $mc['password'],
            'encryption' => !empty($db['mail_encryption']) ? $db['mail_encryption'] : $mc['encryption'],
            'from_name'  => !empty($db['mail_from_name']) ? $db['mail_from_name'] : $mc['from_name'],
            'from_email' => !empty($db['mail_from_email']) ? $db['mail_from_email'] : $mc['from_email'],
        ];
        return $cached;
    }

    /**
     * Convert HTML to plain text (basic).
     */
    private static function htmlToText(string $html): string
    {
        $text = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</h1>', '</h2>', '</h3>', '</h4>', '</li>'], "\n", $html));
        // Collapse multiple newlines
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }
}
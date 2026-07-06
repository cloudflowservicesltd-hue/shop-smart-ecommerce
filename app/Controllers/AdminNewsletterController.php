<?php

class AdminNewsletterController extends BaseController
{
    public function subscribers(): void
    {
        $breadcrumbs = [['Marketing', ''], ['Newsletter Subscribers', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/newsletter-subscribers.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function toggleSubscriber(): void
    {
        $id = (int)Request::post('id', 0);
        if ($id) {
            $sub = Database::selectOne("SELECT * FROM newsletter_subscribers WHERE id = ?", [$id]);
            if ($sub) {
                Database::update('newsletter_subscribers', ['is_active' => $sub['is_active'] ? 0 : 1], 'id = ?', [$id]);
                echo json_encode(['success' => true, 'active' => !$sub['is_active']]);
                return;
            }
        }
        echo json_encode(['success' => false]);
    }

    public function deleteSubscriber(): void
    {
        $id = (int)Request::post('id', 0);
        if ($id) Database::delete('newsletter_subscribers', 'id = ?', [$id]);
        Session::flash('success', 'Subscriber deleted');
        Redirect::back();
    }

    public function bulkDeleteSubscribers(): void
    {
        $ids = array_filter(array_map('intval', explode(',', Request::post('ids', ''))));
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            Database::delete('newsletter_subscribers', "id IN ($placeholders)", $ids);
        }
        Session::flash('success', count($ids) . ' subscriber(s) deleted');
        Redirect::to('/admin/newsletter/subscribers');
    }

    public function exportSubscribers(): void
    {
        $subscribers = Database::select("SELECT email, is_active, created_at FROM newsletter_subscribers ORDER BY created_at DESC");
        $filename = 'newsletter-subscribers-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Email', 'Status', 'Subscribed At']);
        foreach ($subscribers as $s) {
            fputcsv($output, [$s['email'], $s['is_active'] ? 'Active' : 'Inactive', $s['created_at']]);
        }
        fclose($output);
        exit;
    }

    public function compose(): void
    {
        $breadcrumbs = [['Marketing', ''], ['Compose Email', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/newsletter-compose.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function sendTest(): void
    {
        header('Content-Type: application/json');
        $to = Request::post('to', '');
        $subject = Request::post('subject', 'Test Email from ShopSmart');
        $body = Request::post('body', '<p>This is a test email from ShopSmart.</p>');
        $isHtml = (bool)Request::post('is_html', true);
        $fromName = Request::post('from_name', '');

        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email address']);
            return;
        }

        $mail = Mailer::getInstance();
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->isHTML($isHtml);
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));
        if ($fromName) {
            $mail->setFrom($mail->From, $fromName);
        }

        // Handle attachments
        if (!empty($_FILES['attachments'])) {
            $maxSize = 5 * 1024 * 1024;
            foreach ($_FILES['attachments']['name'] as $i => $name) {
                if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK && $_FILES['attachments']['size'][$i] <= $maxSize) {
                    $mail->addAttachment($_FILES['attachments']['tmp_name'][$i], $name);
                }
            }
        }

        try {
            $ok = $mail->send();
            echo json_encode($ok ? ['success' => true] : ['success' => false, 'error' => 'SMTP error — check your mail settings']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $mail->ErrorInfo]);
        }
    }

    public function send(): void
    {
        $recipientType = Request::post('recipient_type', 'all');
        $subject = trim(Request::post('subject', ''));
        $body = trim(Request::post('body', ''));
        $isHtml = (bool)Request::post('is_html', true);
        $replyTo = Request::post('reply_to', '');
        $fromName = trim(Request::post('from_name', ''));

        if (empty($subject) || empty($body)) {
            Session::flash('error', 'Subject and message body are required.');
            Redirect::back();
        }

        // Collect recipient emails
        $emails = [];
        if ($recipientType === 'custom') {
            $raw = Request::post('custom_emails', '');
            // Split by comma, semicolon, or newline
            $parts = preg_split('/[\s,;]+/', $raw);
            foreach ($parts as $e) {
                $e = trim($e);
                if (filter_var($e, FILTER_VALIDATE_EMAIL)) $emails[] = $e;
            }
        } else {
            $subs = Database::select("SELECT email FROM newsletter_subscribers WHERE is_active = 1");
            $emails = array_column($subs, 'email');
        }

        if (empty($emails)) {
            Session::flash('error', 'No valid recipients found.');
            Redirect::back();
        }

        // Process merge tags
        $config = require ROOT_PATH . '/config/app.php';
        $storeUrl = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $body = str_replace(
            ['{store_name}', '{current_date}', '{current_year}', '{unsubscribe_url}'],
            [e($config['name']), date('F j, Y'), date('Y'), $storeUrl . '/newsletter/unsubscribe'],
            $body
        );
        $subject = str_replace(
            ['{store_name}', '{current_date}', '{current_year}'],
            [$config['name'], date('F j, Y'), date('Y')],
            $subject
        );

        // Collect attachment file paths
        $attachmentFiles = [];
        $maxSize = 5 * 1024 * 1024;
        if (!empty($_FILES['attachments']['name'])) {
            foreach ($_FILES['attachments']['name'] as $i => $name) {
                if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK && $_FILES['attachments']['size'][$i] <= $maxSize) {
                    $attachmentFiles[] = [
                        'path' => $_FILES['attachments']['tmp_name'][$i],
                        'name' => $name,
                    ];
                }
            }
        }

        // Send using Mailer directly to support attachments
        $result = ['sent' => 0, 'failed' => 0, 'errors' => [], 'total' => count($emails)];
        $batches = array_chunk($emails, 50);
        foreach ($batches as $batchIndex => $batch) {
            try {
                $mail = Mailer::getInstance();
                $mail->Subject = $subject;
                $mail->Body = $body;
                $mail->isHTML($isHtml);
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));
                if ($replyTo) $mail->addReplyTo($replyTo);

                $batchFailed = 0;
                foreach ($batch as $email) {
                    $email = trim($email);
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $mail->addBCC($email);
                    } else {
                        $batchFailed++;
                        $result['errors'][] = "Invalid email: $email";
                    }
                }

                // Add attachments to each batch
                foreach ($attachmentFiles as $af) {
                    $mail->addAttachment($af['path'], $af['name']);
                }

                if ($mail->send()) {
                    $result['sent'] += count($batch) - $batchFailed;
                } else {
                    $result['failed'] += count($batch) - $batchFailed;
                    $result['errors'][] = "Batch " . ($batchIndex + 1) . " failed: " . $mail->ErrorInfo;
                }
                $result['failed'] += $batchFailed;
            } catch (\Exception $e) {
                $result['failed'] += count($batch);
                $result['errors'][] = "Batch " . ($batchIndex + 1) . " exception: " . $mail->ErrorInfo;
            }
            if ($batchIndex < count($batches) - 1) {
                usleep(1000000);
            }
        }

        if ($result['sent'] > 0 && empty($result['errors'])) {
            Session::flash('success', "Email sent successfully to {$result['sent']} recipient(s).");
        } elseif ($result['sent'] > 0) {
            Session::flash('success', "Sent to {$result['sent']} recipient(s). " . count($result['errors']) . " had issues.");
            if (!empty($result['errors'])) Session::flash('error', implode('<br>', array_slice($result['errors'], 0, 5)));
        } else {
            Session::flash('error', 'Failed to send emails. ' . implode('<br>', array_slice($result['errors'], 0, 3)));
        }

        Redirect::to('/admin/newsletter/subscribers');
    }

    public function settings(): void
    {
        $breadcrumbs = [['Marketing', ''], ['Mail Settings', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/newsletter-settings.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function updateSettings(): void
    {
        $data = [
            'mail_host'       => Request::post('mail_host', ''),
            'mail_port'       => Request::post('mail_port', 587),
            'mail_username'   => Request::post('mail_username', ''),
            'mail_password'   => Request::post('mail_password', ''),
            'mail_encryption' => Request::post('mail_encryption', 'tls'),
            'mail_from_name'  => Request::post('mail_from_name', 'ShopSmart'),
            'mail_from_email' => Request::post('mail_from_email', ''),
        ];

        // If password field is empty, keep existing
        if (empty($data['mail_password'])) {
            $existing = Database::selectOne("SELECT value FROM settings WHERE `key` = 'mail_password'");
            if ($existing) unset($data['mail_password']);
        }

        Mailer::saveConfig($data);
        // Reset Mailer singleton so new settings take effect (already done inside saveConfig now)
        Session::flash('success', 'Mail settings saved successfully');
        Redirect::to('/admin/newsletter/settings');
    }

    public function testConnection(): void
    {
        header('Content-Type: application/json');
        $toEmail = Request::post('test_email', '');
        $result = Mailer::testConnection($toEmail ?: null);
        echo json_encode($result);
    }
}
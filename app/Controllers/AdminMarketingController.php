<?php

class AdminMarketingController extends BaseController
{
    public function social(): void
    {
        $breadcrumbs = [['Marketing', ''], ['Social Publishing', '']];
        $blotatoConnected = false;
        $accounts = [];
        $recentPosts = [];
        try {
            if (class_exists('BlotatoAPI') && BlotatoAPI::getApiKey()) {
                $blotatoConnected = true;
                $accounts = BlotatoAPI::getAccounts();
                $recentPosts = BlotatoAPI::listPosts(10);
            }
        } catch (\Throwable $e) {
            $blotatoConnected = false;
        }
        ob_start();
        include ROOT_PATH . '/resources/views/admin/marketing/social.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function socialConnect(): void
    {
        header('Content-Type: application/json');
        $key = trim(Request::post('api_key', ''));
        if (empty($key)) { echo json_encode(['success' => false, 'error' => 'API key is required']); return; }
        BlotatoAPI::setApiKey($key);
        try {
            $user = BlotatoAPI::testConnection();
            echo json_encode(['success' => true, 'message' => 'Connected as ' . ($user['email'] ?? $user['name'] ?? 'user'), 'user' => $user]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function socialAccounts(): void
    {
        header('Content-Type: application/json');
        try {
            $platform = Request::post('platform', null);
            $accounts = BlotatoAPI::getAccounts($platform ?: null);
            echo json_encode(['success' => true, 'accounts' => $accounts]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function socialSubaccounts(): void
    {
        header('Content-Type: application/json');
        try {
            $accountId = Request::post('account_id', '');
            $result = BlotatoAPI::getSubaccounts($accountId);
            echo json_encode(['success' => true, 'subaccounts' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function pinterestBoards(): void
    {
        header('Content-Type: application/json');
        try {
            $accountId = Request::post('account_id', '');
            $result = BlotatoAPI::getPinterestBoards($accountId);
            echo json_encode(['success' => true, 'boards' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function socialPublish(): void
    {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            $accountId = $input['account_id'] ?? '';
            $platform = $input['platform'] ?? '';
            $text = $input['text'] ?? '';
            $mediaUrls = $input['media_urls'] ?? [];
            $scheduledTime = $input['scheduled_time'] ?? null;
            $useNextFreeSlot = $input['use_next_free_slot'] ?? false;

            if (empty($accountId) || empty($platform) || empty($text)) {
                echo json_encode(['success' => false, 'error' => 'Account, platform, and content are required']);
                return;
            }

            $postPayload = [
                'accountId' => $accountId,
                'content' => [
                    'text' => $text,
                    'mediaUrls' => $mediaUrls,
                    'platform' => $platform,
                ],
                'target' => ['targetType' => $platform],
            ];

            // Platform-specific target fields
            if ($platform === 'facebook' && !empty($input['page_id'])) {
                $postPayload['target']['pageId'] = $input['page_id'];
            }
            if ($platform === 'pinterest' && !empty($input['board_id'])) {
                $postPayload['target']['boardId'] = $input['board_id'];
            }
            if ($platform === 'youtube') {
                $postPayload['target']['title'] = $input['title'] ?? '';
                $postPayload['target']['privacyStatus'] = $input['privacy_status'] ?? 'public';
                $postPayload['target']['shouldNotifySubscribers'] = (bool)($input['notify_subscribers'] ?? false);
            }
            if ($platform === 'tiktok') {
                $postPayload['target']['privacyLevel'] = $input['privacy_level'] ?? 'PUBLIC_TO_EVERYONE';
                $postPayload['target']['disabledComments'] = (bool)($input['disabled_comments'] ?? false);
                $postPayload['target']['disabledDuet'] = (bool)($input['disabled_duet'] ?? false);
                $postPayload['target']['disabledStitch'] = (bool)($input['disabled_stitch'] ?? false);
                $postPayload['target']['isBrandedContent'] = (bool)($input['is_branded_content'] ?? false);
                $postPayload['target']['isYourBrand'] = (bool)($input['is_your_brand'] ?? false);
                $postPayload['target']['isAiGenerated'] = false;
            }

            $rootPayload = ['post' => $postPayload];
            if (!empty($scheduledTime)) {
                $rootPayload['scheduledTime'] = $scheduledTime;
            } elseif ($useNextFreeSlot) {
                $rootPayload['useNextFreeSlot'] = true;
            }

            $result = BlotatoAPI::createPost($rootPayload);
            echo json_encode(['success' => true, 'post_submission_id' => $result['postSubmissionId'] ?? '', 'message' => 'Post submitted successfully']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function socialPostStatus(): void
    {
        header('Content-Type: application/json');
        try {
            $id = Request::query('id', '');
            if (empty($id)) { echo json_encode(['success' => false, 'error' => 'Post ID required']); return; }
            $result = BlotatoAPI::getPostStatus($id);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function socialPosts(): void
    {
        header('Content-Type: application/json');
        try {
            $result = BlotatoAPI::listPosts(20);
            echo json_encode(['success' => true, 'posts' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function socialAnalytics(): void
    {
        header('Content-Type: application/json');
        try {
            $sortBy = Request::query('sort_by', 'views_count');
            $platform = Request::query('platform', null);
            $result = BlotatoAPI::listTopPosts($sortBy, 10, $platform);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function socialTemplates(): void
    {
        header('Content-Type: application/json');
        try {
            $result = BlotatoAPI::listTemplates();
            echo json_encode(['success' => true, 'templates' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function socialCreateVisual(): void
    {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $templateId = $input['template_id'] ?? '';
            $prompt = $input['prompt'] ?? '';
            if (empty($templateId) || empty($prompt)) {
                echo json_encode(['success' => false, 'error' => 'Template ID and prompt are required']);
                return;
            }
            $result = BlotatoAPI::createVisual($templateId, $prompt);
            echo json_encode(['success' => true, 'id' => $result['item']['id'] ?? '', 'status' => $result['item']['status'] ?? 'queueing']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function socialVisualStatus(): void
    {
        header('Content-Type: application/json');
        try {
            $id = Request::query('id', '');
            if (empty($id)) { echo json_encode(['success' => false, 'error' => 'Visual ID required']); return; }
            $result = BlotatoAPI::getVisualStatus($id);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function socialUpload(): void
    {
        header('Content-Type: application/json');
        try {
            $url = trim(Request::post('url', ''));
            if (empty($url)) { echo json_encode(['success' => false, 'error' => 'Media URL is required']); return; }
            $result = BlotatoAPI::uploadMediaFromUrl($url);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function socialSchedules(): void
    {
        header('Content-Type: application/json');
        try {
            $result = BlotatoAPI::listSchedules(20);
            echo json_encode(['success' => true, 'schedules' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function socialScheduleUpdate(): void
    {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? '';
            $scheduledTime = $input['scheduled_time'] ?? null;
            if (empty($id)) { echo json_encode(['success' => false, 'error' => 'Schedule ID required']); return; }
            $patch = [];
            if ($scheduledTime) $patch['scheduledTime'] = $scheduledTime;
            BlotatoAPI::updateSchedule($id, $patch);
            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function socialScheduleDelete(): void
    {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? '';
            if (empty($id)) { echo json_encode(['success' => false, 'error' => 'Schedule ID required']); return; }
            BlotatoAPI::deleteSchedule($id);
            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function whatsapp(): void
    {
        $breadcrumbs = [['Marketing', ''], ['WhatsApp', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/marketing/whatsapp.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function email(): void
    {
        $breadcrumbs = [['Marketing', ''], ['Email Campaigns', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/marketing/email.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function whatsappSettings(): void
    {
        header('Content-Type: application/json');
        $fields = ['wa_phone_id','wa_access_token','wa_business_id','wa_verify_token','wa_api_version'];
        foreach ($fields as $f) {
            $val = Request::post($f, '');
            if ($f === 'wa_access_token' && empty($val)) {
                $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$f]);
                if ($existing) continue;
            }
            $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$f]);
            if ($existing) {
                Database::update('settings', ['value' => $val, 'updated_at' => date('Y-m-d H:i:s')], '`key` = ?', [$f]);
            } else {
                Database::insert('settings', ['key' => $f, 'value' => $val, 'group_name' => 'whatsapp', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            }
        }
        echo json_encode(['success' => true]);
    }

    public function whatsappTest(): void
    {
        header('Content-Type: application/json');
        $phoneId = '';
        try { $r = Database::selectOne("SELECT value FROM settings WHERE `key` = 'wa_phone_id'"); $phoneId = $r['value'] ?? ''; } catch (\Throwable $e) {}
        $token = '';
        try { $r = Database::selectOne("SELECT value FROM settings WHERE `key` = 'wa_access_token'"); $token = $r['value'] ?? ''; } catch (\Throwable $e) {}
        $version = '';
        try { $r = Database::selectOne("SELECT value FROM settings WHERE `key` = 'wa_api_version'"); $version = $r['value'] ?? 'v21.0'; } catch (\Throwable $e) {}

        if (empty($phoneId) || empty($token)) {
            echo json_encode(['success' => false, 'error' => 'Phone Number ID and Access Token are required']);
            return;
        }

        $ch = curl_init("https://graph.facebook.com/{$version}/{$phoneId}");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token]]);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            echo json_encode(['success' => false, 'error' => 'Network error: ' . $err]);
        } else {
            $data = json_decode($resp, true);
            if (isset($data['display_phone_number'])) {
                echo json_encode(['success' => true, 'message' => 'Connected to ' . $data['display_phone_number']]);
            } else {
                $errMsg = $data['error']['message'] ?? json_encode($data);
                echo json_encode(['success' => false, 'error' => $errMsg]);
            }
        }
    }

    public function whatsappSend(): void
    {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $message = trim($input['message'] ?? '');
        $audience = $input['audience'] ?? 'all_customers';

        if (empty($message)) { echo json_encode(['success' => false, 'error' => 'Message is required']); return; }

        $phoneId = '';
        try { $r = Database::selectOne("SELECT value FROM settings WHERE `key` = 'wa_phone_id'"); $phoneId = $r['value'] ?? ''; } catch (\Throwable $e) {}
        $token = '';
        try { $r = Database::selectOne("SELECT value FROM settings WHERE `key` = 'wa_access_token'"); $token = $r['value'] ?? ''; } catch (\Throwable $e) {}
        $version = '';
        try { $r = Database::selectOne("SELECT value FROM settings WHERE `key` = 'wa_api_version'"); $version = $r['value'] ?? 'v21.0'; } catch (\Throwable $e) {}

        if (empty($phoneId) || empty($token)) {
            echo json_encode(['success' => false, 'error' => 'WhatsApp not configured. Go to Settings first.']);
            return;
        }

        // Get recipient phone numbers
        $phones = [];
        if ($audience === 'custom') {
            $raw = $input['custom_phones'] ?? '';
            foreach (explode("\n", $raw) as $line) {
                $phone = preg_replace('/[^0-9]/', '', trim($line));
                if (strlen($phone) >= 10) $phones[] = $phone;
            }
        } else {
            $where = '1=1';
            if ($audience === 'new_customers') $where = 'created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
            if ($audience === 'vip') $where = '(SELECT COUNT(*) FROM orders WHERE orders.user_id = users.id AND payment_status = \'paid\') >= 5';
            if ($audience === 'subscribers') {
                $subs = Database::select("SELECT phone FROM newsletter_subscribers WHERE status = 'active' AND phone IS NOT NULL AND phone != ''");
                foreach ($subs as $s) {
                    $phone = preg_replace('/[^0-9]/', '', $s['phone']);
                    if (strlen($phone) >= 10) $phones[] = $phone;
                }
            } else {
                $users = Database::select("SELECT phone FROM users WHERE $where AND phone IS NOT NULL AND phone != '' LIMIT 500");
                foreach ($users as $u) {
                    $phone = preg_replace('/[^0-9]/', '', $u['phone']);
                    if (strlen($phone) >= 10) $phones[] = $phone;
                }
            }
        }

        if (empty($phones)) {
            echo json_encode(['success' => false, 'error' => 'No recipients found']);
            return;
        }

        // Send via WhatsApp Business API
        $sent = 0;
        $failed = 0;
        $phoneIdClean = preg_replace('/[^0-9]/', '', $phoneId);

        foreach (array_slice($phones, 0, 100) as $phone) {
            // Ensure phone has country code format
            if (!str_starts_with($phone, '+')) $phone = '+' . $phone;

            $payload = json_encode([
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'text',
                'text' => ['body' => $message],
            ]);

            $ch = curl_init("https://graph.facebook.com/{$version}/{$phoneIdClean}/messages");
            curl_setopt_array($ch, [
                CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
                CURLOPT_POSTFIELDS => $payload,
            ]);
            $resp = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($resp, true);

            if (isset($data['messages'][0]['id'])) $sent++;
            else $failed++;
        }

        Database::insert('marketing_campaigns', [
            'name' => 'WA Blast ' . date('M j, g:i A'), 'platform' => 'whatsapp', 'type' => 'blast',
            'status' => $sent > 0 ? 'sent' : 'failed', 'content' => $message,
            'total_sent' => $sent, 'total_failed' => $failed,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);

        echo json_encode(['success' => $sent > 0, 'sent' => $sent, 'failed' => $failed, 'total' => count($phones)]);
    }
}
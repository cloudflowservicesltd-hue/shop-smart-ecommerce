<?php

/**
 * Webhook Controller
 *
 * Handles generic webhook endpoints for logging payment gateway callbacks.
 */
class WebhookController extends BaseController
{
    /**
     * M-Pesa generic webhook — logs raw callback and acknowledges.
     * POST /api/webhooks/mpesa
     */
    public function mpesa(): void
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        @file_put_contents(ROOT_PATH . '/storage/logs/mpesa_callback.log', date('Y-m-d H:i:s') . " - " . json_encode($data) . "\n", FILE_APPEND);
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        exit;
    }

    /**
     * Stripe webhook — logs raw payload and acknowledges.
     * POST /api/webhooks/stripe
     */
    public function stripe(): void
    {
        $payload = file_get_contents('php://input');
        @file_put_contents(ROOT_PATH . '/storage/logs/stripe_callback.log', date('Y-m-d H:i:s') . " - " . $payload . "\n", FILE_APPEND);
        http_response_code(200);
        echo json_encode(['status' => 'ok']);
        exit;
    }

    /**
     * IntaSend webhook — logs raw callback and acknowledges.
     * POST /api/webhooks/intasend
     */
    public function intasend(): void
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        @file_put_contents(ROOT_PATH . '/storage/logs/intasend_callback.log', date('Y-m-d H:i:s') . " - " . json_encode($data) . "\n", FILE_APPEND);
        echo json_encode(['status' => 'ok']);
        exit;
    }
}
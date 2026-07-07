<?php

/**
 * Referral Controller
 *
 * Handles referral link redirection: /ref/{code} → /register?ref={code}
 * Saves the referral code in session for use during registration.
 */
class ReferralController extends BaseController
{
    /**
     * Handle referral link visit.
     * Route: GET /ref/{code}
     */
    public function handle(string $code): void
    {
        // Save referral code in session
        $_SESSION['referral_code'] = $code;

        // Redirect to register page with ref query param
        header('Location: /register?ref=' . urlencode($code));
        exit;
    }
}
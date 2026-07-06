<?php

/**
 * Page Controller
 *
 * Handles static pages (e.g. about, terms) and the contact form submission.
 */
class PageController extends BaseController
{
    /**
     * GET /page/{slug}
     * Render a static page by slug.
     */
    public function show(string $slug): void
    {
        $page = Database::selectOne("SELECT * FROM pages WHERE slug = ? AND is_active = 1", [$slug]);
        if (!$page) { http_response_code(404); View::render('errors/404'); return; }
        $pageTitle = $page['title'] . ' - ' . getStoreName();
        ob_start();
        include ROOT_PATH . '/resources/views/customer/page.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }

    /**
     * POST /page/contact-us
     * Handle the contact form submission, stores as a notification.
     */
    public function contactSubmit(): void
    {
        $name = trim(Request::post('name', ''));
        $email = trim(Request::post('email', ''));
        $subject = trim(Request::post('subject', ''));
        $message = trim(Request::post('message', ''));
        if (!$name || !$email || !$message) { Session::flash('error', 'Please fill in all required fields'); Redirect::back(); }
        // Store contact message (could also send email)
        Database::insert('notifications', [
            'type' => 'contact_form',
            'title' => "Contact: $subject",
            'message' => "From: $name ($email)\n\n$message",
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Thank you for contacting us! We\'ll get back to you soon.');
        Redirect::to('/page/contact-us');
    }
}
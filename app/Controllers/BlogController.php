<?php

/**
 * Blog Controller
 *
 * Handles the blog listing and blog post detail pages.
 */
class BlogController extends BaseController
{
    /**
     * GET /blog
     * Paginated blog listing.
     */
    public function index(): void
    {
        $page = (int)Request::query('page', 1);
        $posts = Database::paginate('blog_posts', $page, 9, 'is_published = 1', [], 'created_at DESC');
        $pageTitle = 'Blog';
        ob_start();
        include ROOT_PATH . '/resources/views/customer/blog.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }

    /**
     * GET /blog/{slug}
     * Single blog post detail page. Increments view count.
     */
    public function show(string $slug): void
    {
        $post = Database::selectOne("SELECT bp.*, u.name as author_name FROM blog_posts bp LEFT JOIN users u ON bp.author_id = u.id WHERE bp.slug = ? AND bp.is_published = 1", [$slug]);
        if (!$post) Redirect::to('/blog');

        // Increment views
        Database::query("UPDATE blog_posts SET views = views + 1 WHERE id = ?", [$post['id']]);

        $pageTitle = $post['meta_title'] ?: $post['title'];
        $metaDescription = $post['meta_description'] ?? '';
        ob_start();
        include ROOT_PATH . '/resources/views/customer/blog-detail.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }
}
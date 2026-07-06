<?php

class AdminBlogController extends BaseController
{
    public function index(): void
    {
        $page = (int)Request::query('page', 1);
        $search = Request::query('search', '');

        $where = '1=1';
        $params = [];
        if ($search) { $where .= ' AND (bp.title LIKE ? OR bp.slug LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

        $posts = Database::paginate("blog_posts bp LEFT JOIN users u ON bp.author_id = u.id", $page, 15, $where, $params, 'bp.created_at DESC', 'bp.*, u.name as author_name');

        $breadcrumbs = [['Blog Posts', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/blogs.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function create(): void
    {
        $post = null;
        $breadcrumbs = [['Blog Posts', '/admin/blogs'], ['Create Post', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/blog-form.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function store(): void
    {
        $title = Request::post('title', '');
        $slug = Request::post('slug', '') ?: strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));

        // Ensure unique slug
        $existing = Database::selectOne("SELECT id FROM blog_posts WHERE slug = ?", [$slug]);
        if ($existing) { $slug .= '-' . time(); }

        $featuredImage = FileUpload::handle('featured_image', 'blog');

        Database::insert('blog_posts', [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => Request::post('excerpt', ''),
            'content' => Request::post('content', ''),
            'featured_image' => $featuredImage,
            'author_id' => Auth::id(),
            'is_published' => Request::post('is_published') ? 1 : 0,
            'meta_title' => Request::post('meta_title', ''),
            'meta_description' => Request::post('meta_description', ''),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Blog post created');
        Redirect::to('/admin/blogs');
    }

    public function edit($id): void
    {
        $post = Database::selectOne("SELECT * FROM blog_posts WHERE id = ?", [(int)$id]);
        if (!$post) Redirect::to('/admin/blogs');
        $breadcrumbs = [['Blog Posts', '/admin/blogs'], [e($post['title']), '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/blog-form.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function update($id): void
    {
        $post = Database::selectOne("SELECT * FROM blog_posts WHERE id = ?", [(int)$id]);
        if (!$post) Redirect::to('/admin/blogs');

        $title = Request::post('title', '');
        $slug = Request::post('slug', '') ?: strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));

        // Ensure unique slug (exclude current post)
        $existing = Database::selectOne("SELECT id FROM blog_posts WHERE slug = ? AND id != ?", [$slug, (int)$id]);
        if ($existing) { $slug .= '-' . time(); }

        $featuredImage = FileUpload::handle('featured_image', 'blog');
        $data = [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => Request::post('excerpt', ''),
            'content' => Request::post('content', ''),
            'is_published' => Request::post('is_published') ? 1 : 0,
            'meta_title' => Request::post('meta_title', ''),
            'meta_description' => Request::post('meta_description', ''),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($featuredImage) {
            if ($post['featured_image']) FileUpload::delete($post['featured_image']);
            $data['featured_image'] = $featuredImage;
        }

        Database::update('blog_posts', $data, 'id = ?', [(int)$id]);
        Session::flash('success', 'Blog post updated');
        Redirect::to('/admin/blogs');
    }

    public function delete($id): void
    {
        $post = Database::selectOne("SELECT * FROM blog_posts WHERE id = ?", [(int)$id]);
        if ($post) {
            if ($post['featured_image']) FileUpload::delete($post['featured_image']);
            Database::delete('blog_posts', 'id = ?', [(int)$id]);
        }
        Session::flash('success', 'Blog post deleted');
        Redirect::to('/admin/blogs');
    }
}
<?php

class AdminTestimonialController extends BaseController
{
    public function index(): void
    {
        $testimonials = Database::select("SELECT * FROM testimonials ORDER BY sort_order ASC, created_at DESC");
        $breadcrumbs = [['Frontend Content', ''], ['Testimonials', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/testimonials.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function store(): void
    {
        Database::insert('testimonials', [
            'author_name' => Request::post('author_name', ''),
            'author_title' => Request::post('author_title', ''),
            'author_photo' => Request::post('author_photo', ''),
            'rating' => (int)Request::post('rating', 5),
            'review_text' => Request::post('review_text', ''),
            'source' => Request::post('source', 'google'),
            'is_featured' => Request::post('is_featured') ? 1 : 0,
            'sort_order' => (int)Request::post('sort_order', 0),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Testimonial added successfully');
        Redirect::to('/admin/testimonials');
    }

    public function update($id): void
    {
        Database::update('testimonials', [
            'author_name' => Request::post('author_name', ''),
            'author_title' => Request::post('author_title', ''),
            'author_photo' => Request::post('author_photo', ''),
            'rating' => (int)Request::post('rating', 5),
            'review_text' => Request::post('review_text', ''),
            'source' => Request::post('source', 'google'),
            'is_featured' => Request::post('is_featured') ? 1 : 0,
            'sort_order' => (int)Request::post('sort_order', 0),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
        Session::flash('success', 'Testimonial updated successfully');
        Redirect::to('/admin/testimonials');
    }

    public function delete($id): void
    {
        Database::delete('testimonials', 'id = ?', [$id]);
        Session::flash('success', 'Testimonial deleted');
        Redirect::to('/admin/testimonials');
    }
}
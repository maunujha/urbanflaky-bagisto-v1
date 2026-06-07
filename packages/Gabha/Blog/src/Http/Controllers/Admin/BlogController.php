<?php

namespace Gabha\Blog\Http\Controllers\Admin;

use Gabha\Blog\DataGrids\BlogDataGrid;
use Gabha\Blog\Repositories\BlogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;

class BlogController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected BlogRepository $blogRepository) {}

    /**
     * Display a listing of the blog posts.
     */
    public function index(): mixed
    {
        if (request()->ajax()) {
            return datagrid(BlogDataGrid::class)->process();
        }

        return view('blog::admin.blogs.index');
    }

    /**
     * Show the form for creating a new blog post.
     */
    public function create(): View
    {
        return view('blog::admin.blogs.create');
    }

    /**
     * Store a newly created blog post in storage.
     */
    public function store(): mixed
    {
        $this->validate(request(), $this->rules());

        Event::dispatch('blog.blog.create.before');

        $data = $this->prepareData();

        $blog = $this->blogRepository->create($data);

        $this->blogRepository->uploadImage(request()->all(), $blog);

        Event::dispatch('blog.blog.create.after', $blog);

        session()->flash('success', trans('blog::app.admin.blogs.create-success'));

        return redirect()->route('admin.blogs.index');
    }

    /**
     * Show the form for editing the specified blog post.
     */
    public function edit(int $id): View
    {
        $blog = $this->blogRepository->findOrFail($id);

        return view('blog::admin.blogs.edit', compact('blog'));
    }

    /**
     * Update the specified blog post in storage.
     */
    public function update(int $id): mixed
    {
        $this->validate(request(), $this->rules($id));

        Event::dispatch('blog.blog.update.before', $id);

        $data = $this->prepareData($id);

        $blog = $this->blogRepository->update($data, $id);

        $this->blogRepository->uploadImage(request()->all(), $blog);

        Event::dispatch('blog.blog.update.after', $blog);

        session()->flash('success', trans('blog::app.admin.blogs.update-success'));

        return redirect()->route('admin.blogs.index');
    }

    /**
     * Remove the specified blog post from storage.
     */
    public function delete(int $id): JsonResponse
    {
        $this->blogRepository->findOrFail($id);

        try {
            Event::dispatch('blog.blog.delete.before', $id);

            $this->blogRepository->delete($id);

            Event::dispatch('blog.blog.delete.after', $id);

            return new JsonResponse([
                'message' => trans('blog::app.admin.blogs.delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => trans('blog::app.admin.blogs.delete-failed'),
            ], 500);
        }
    }

    /**
     * Mass delete the blog posts.
     */
    public function massDelete(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $indices = $massDestroyRequest->input('indices');

        foreach ($indices as $index) {
            Event::dispatch('blog.blog.delete.before', $index);

            $this->blogRepository->delete($index);

            Event::dispatch('blog.blog.delete.after', $index);
        }

        return new JsonResponse([
            'message' => trans('blog::app.admin.blogs.index.datagrid.mass-delete-success'),
        ]);
    }

    /**
     * Validation rules shared by store and update.
     */
    protected function rules(?int $id = null): array
    {
        return [
            'title'            => 'required|string|max:255',
            'slug'             => 'nullable|string|max:255|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'author'           => 'nullable|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'content'          => 'nullable|string',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords'    => 'nullable|string|max:255',
            'published_at'     => 'nullable|date',
            'status'           => 'nullable|boolean',
        ];
    }

    /**
     * Build the persistable payload from the request: a unique slug, sanitised
     * HTML content and normalised status / publish date.
     */
    protected function prepareData(?int $id = null): array
    {
        $data = request()->only([
            'title',
            'slug',
            'author',
            'short_description',
            'content',
            'meta_title',
            'meta_description',
            'meta_keywords',
            'published_at',
        ]);

        $slugSource = ! empty($data['slug']) ? $data['slug'] : $data['title'];

        $data['slug'] = $this->blogRepository->generateUniqueSlug($slugSource, $id);

        $data['content'] = clean_content($data['content'] ?? '');

        $data['status'] = request()->boolean('status');

        $data['published_at'] = ! empty($data['published_at']) ? $data['published_at'] : now();

        return $data;
    }
}

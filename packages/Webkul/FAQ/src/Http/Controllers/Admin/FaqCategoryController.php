<?php

namespace Webkul\FAQ\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\FAQ\DataGrids\FaqCategoryDataGrid;
use Webkul\FAQ\Repositories\FaqCategoryRepository;

class FaqCategoryController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected FaqCategoryRepository $faqCategoryRepository) {}

    /**
     * Display a listing of the FAQ categories.
     */
    public function index(): mixed
    {
        if (request()->ajax()) {
            return datagrid(FaqCategoryDataGrid::class)->process();
        }

        return view('faq::admin.categories.index');
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(): View
    {
        return view('faq::admin.categories.create');
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(): mixed
    {
        $this->validate(request(), [
            'name'       => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'status'     => 'nullable|boolean',
        ]);

        Event::dispatch('faq.category.create.before');

        $category = $this->faqCategoryRepository->create([
            'name'       => request()->input('name'),
            'slug'       => $this->generateUniqueSlug(request()->input('name')),
            'sort_order' => request()->input('sort_order') ?: 0,
            'status'     => request()->boolean('status'),
        ]);

        Event::dispatch('faq.category.create.after', $category);

        session()->flash('success', trans('faq::app.admin.categories.create-success'));

        return redirect()->route('admin.faqs.categories.index');
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(int $id): View
    {
        $category = $this->faqCategoryRepository->findOrFail($id);

        return view('faq::admin.categories.edit', compact('category'));
    }

    /**
     * Update the specified category in storage.
     */
    public function update(int $id): mixed
    {
        $this->validate(request(), [
            'name'       => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'status'     => 'nullable|boolean',
        ]);

        $category = $this->faqCategoryRepository->findOrFail($id);

        Event::dispatch('faq.category.update.before', $id);

        $name = request()->input('name');

        $data = [
            'name'       => $name,
            'sort_order' => request()->input('sort_order') ?: 0,
            'status'     => request()->boolean('status'),
        ];

        if ($name !== $category->name) {
            $data['slug'] = $this->generateUniqueSlug($name, $id);
        }

        $category = $this->faqCategoryRepository->update($data, $id);

        Event::dispatch('faq.category.update.after', $category);

        session()->flash('success', trans('faq::app.admin.categories.update-success'));

        return redirect()->route('admin.faqs.categories.index');
    }

    /**
     * Remove the specified category from storage.
     */
    public function delete(int $id): JsonResponse
    {
        $category = $this->faqCategoryRepository->findOrFail($id);

        if ($category->faqs()->count()) {
            return new JsonResponse([
                'message' => trans('faq::app.admin.categories.has-faqs'),
            ], 400);
        }

        try {
            Event::dispatch('faq.category.delete.before', $id);

            $this->faqCategoryRepository->delete($id);

            Event::dispatch('faq.category.delete.after', $id);

            return new JsonResponse([
                'message' => trans('faq::app.admin.categories.delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => trans('faq::app.admin.categories.delete-failed'),
            ], 500);
        }
    }

    /**
     * Generate a unique slug for a category name.
     */
    protected function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'category';

        $slug = $base;

        $suffix = 1;

        while (
            $this->faqCategoryRepository
                ->getModel()
                ->newQuery()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}

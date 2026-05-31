<?php

namespace Webkul\FAQ\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\FAQ\DataGrids\FaqDataGrid;
use Webkul\FAQ\Repositories\FaqCategoryRepository;
use Webkul\FAQ\Repositories\FaqRepository;

class FaqController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected FaqRepository $faqRepository,
        protected FaqCategoryRepository $faqCategoryRepository
    ) {}

    /**
     * Display a listing of the FAQs.
     */
    public function index(): mixed
    {
        if (request()->ajax()) {
            return datagrid(FaqDataGrid::class)->process();
        }

        return view('faq::admin.faqs.index');
    }

    /**
     * Show the form for creating a new FAQ.
     */
    public function create(): View
    {
        $categories = $this->faqCategoryRepository->orderBy('sort_order')->all();

        return view('faq::admin.faqs.create', compact('categories'));
    }

    /**
     * Store a newly created FAQ in storage.
     */
    public function store(): mixed
    {
        $this->validate(request(), [
            'faq_category_id' => 'required|integer|exists:faq_categories,id',
            'question'        => 'required|string',
            'answer'          => 'required|string',
            'sort_order'      => 'nullable|integer|min:0',
            'status'          => 'nullable|boolean',
        ]);

        Event::dispatch('faq.faq.create.before');

        $data = request()->only(['faq_category_id', 'question', 'answer', 'sort_order']);

        $data['answer'] = clean_content($data['answer']);
        $data['status'] = request()->boolean('status');
        $data['sort_order'] = request()->input('sort_order') ?: 0;

        $faq = $this->faqRepository->create($data);

        Event::dispatch('faq.faq.create.after', $faq);

        session()->flash('success', trans('faq::app.admin.faqs.create-success'));

        return redirect()->route('admin.faqs.index');
    }

    /**
     * Show the form for editing the specified FAQ.
     */
    public function edit(int $id): View
    {
        $faq = $this->faqRepository->findOrFail($id);

        $categories = $this->faqCategoryRepository->orderBy('sort_order')->all();

        return view('faq::admin.faqs.edit', compact('faq', 'categories'));
    }

    /**
     * Update the specified FAQ in storage.
     */
    public function update(int $id): mixed
    {
        $this->validate(request(), [
            'faq_category_id' => 'required|integer|exists:faq_categories,id',
            'question'        => 'required|string',
            'answer'          => 'required|string',
            'sort_order'      => 'nullable|integer|min:0',
            'status'          => 'nullable|boolean',
        ]);

        Event::dispatch('faq.faq.update.before', $id);

        $data = request()->only(['faq_category_id', 'question', 'answer', 'sort_order']);

        $data['answer'] = clean_content($data['answer']);
        $data['status'] = request()->boolean('status');
        $data['sort_order'] = request()->input('sort_order') ?: 0;

        $faq = $this->faqRepository->update($data, $id);

        Event::dispatch('faq.faq.update.after', $faq);

        session()->flash('success', trans('faq::app.admin.faqs.update-success'));

        return redirect()->route('admin.faqs.index');
    }

    /**
     * Remove the specified FAQ from storage.
     */
    public function delete(int $id): JsonResponse
    {
        $this->faqRepository->findOrFail($id);

        try {
            Event::dispatch('faq.faq.delete.before', $id);

            $this->faqRepository->delete($id);

            Event::dispatch('faq.faq.delete.after', $id);

            return new JsonResponse([
                'message' => trans('faq::app.admin.faqs.delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => trans('faq::app.admin.faqs.delete-failed'),
            ], 500);
        }
    }

    /**
     * Mass delete the FAQs.
     */
    public function massDelete(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $indices = $massDestroyRequest->input('indices');

        foreach ($indices as $index) {
            Event::dispatch('faq.faq.delete.before', $index);

            $this->faqRepository->delete($index);

            Event::dispatch('faq.faq.delete.after', $index);
        }

        return new JsonResponse([
            'message' => trans('faq::app.admin.faqs.index.datagrid.mass-delete-success'),
        ]);
    }
}

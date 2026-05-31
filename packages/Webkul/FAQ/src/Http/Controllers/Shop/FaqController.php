<?php

namespace Webkul\FAQ\Http\Controllers\Shop;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Webkul\FAQ\Repositories\FaqCategoryRepository;
use Webkul\FAQ\Repositories\FaqRepository;

class FaqController
{
    /**
     * Minimum characters required before search runs.
     */
    const SEARCH_MIN_LENGTH = 3;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected FaqCategoryRepository $faqCategoryRepository,
        protected FaqRepository $faqRepository
    ) {}

    /**
     * Display the public FAQ page.
     */
    public function index(): View
    {
        $categories = $this->faqCategoryRepository
            ->getModel()
            ->newQuery()
            ->where('status', 1)
            ->whereHas('activeFaqs')
            ->with('activeFaqs')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('faq::shop.index', compact('categories'));
    }

    /**
     * Search active FAQs by question and answer (AJAX autocomplete).
     */
    public function search(): JsonResponse
    {
        $query = trim((string) request()->input('query', ''));

        if (Str::length($query) < self::SEARCH_MIN_LENGTH) {
            return new JsonResponse(['results' => []]);
        }

        $results = $this->faqRepository
            ->getModel()
            ->newQuery()
            ->where('faqs.status', 1)
            ->where(function ($builder) use ($query) {
                $builder->where('question', 'LIKE', '%'.$query.'%')
                    ->orWhere('answer', 'LIKE', '%'.$query.'%');
            })
            ->join('faq_categories', 'faqs.faq_category_id', '=', 'faq_categories.id')
            ->where('faq_categories.status', 1)
            ->orderBy('faq_categories.sort_order')
            ->orderBy('faqs.sort_order')
            ->limit(8)
            ->get(['faqs.id', 'faqs.question', 'faqs.answer', 'faq_categories.name as category']);

        $results = $results->map(function ($faq) {
            $plain = trim(preg_replace('/\s+/', ' ', strip_tags($faq->answer)));

            return [
                'id'       => $faq->id,
                'question' => $faq->question,
                'category' => $faq->category,
                'snippet'  => Str::limit($plain, 100),
            ];
        });

        return new JsonResponse(['results' => $results]);
    }
}

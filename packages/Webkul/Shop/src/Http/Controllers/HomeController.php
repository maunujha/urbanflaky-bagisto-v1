<?php

namespace Webkul\Shop\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Shop\Http\Requests\ContactRequest;
use Webkul\Shop\Http\Resources\CategoryTreeResource;
use Webkul\Shop\Mail\ContactUs;
use Webkul\Shop\Mail\ContactUsAcknowledgement;
use Webkul\Theme\Repositories\ThemeCustomizationRepository;

class HomeController extends Controller
{
    /**
     * Using const variable for status
     */
    const STATUS = 1;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected ThemeCustomizationRepository $themeCustomizationRepository, protected CategoryRepository $categoryRepository) {}

    /**
     * Loads the home page for the storefront.
     *
     * @return View
     */
    public function index()
    {
        $customizations = $this->themeCustomizationRepository->orderBy('sort_order')->findWhere([
            'status' => self::STATUS,
            'channel_id' => core()->getCurrentChannel()->id,
            'theme_code' => core()->getCurrentChannel()->theme,
        ]);

        $categories = $this->categoryRepository->getVisibleCategoryTree(core()->getCurrentChannel()->root_category_id);

        $categories = CategoryTreeResource::collection($categories);

        return view('shop::home.index', compact('customizations', 'categories'));
    }

    /**
     * Loads the home page for the storefront if something wrong.
     *
     * @return \Exception
     */
    public function notFound()
    {
        abort(404);
    }

    /**
     * Summary of contact.
     *
     * @return View
     */
    public function contactUs()
    {
        return view('shop::home.contact-us');
    }

    /**
     * Summary of store.
     *
     * @return RedirectResponse
     */
    public function sendContactUsMail(ContactRequest $contactRequest)
    {
        $data = $contactRequest->only([
            'name',
            'email',
            'contact',
            'topic',
            'message',
        ]);

        try {
            /* Notify the store (admin inbox). */
            Mail::queue(new ContactUs($data));

            /* Send the customer an acknowledgement so they know it was received.
             * Isolated so a failure here never blocks the admin notification. */
            try {
                Mail::queue(new ContactUsAcknowledgement($data));
            } catch (\Exception $e) {
                report($e);
            }

            session()->forget(['contact_phone_verified', 'contact_otp_phone']);
            session()->flash('success', trans('shop::app.home.thanks-for-contact'));

            /* GA4 generate_lead / Meta Lead — flushed on the next page render. */
            \App\Support\DataLayer::flash([
                'event'         => 'contact_submit',
                'form_location' => 'contact_page',
            ]);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());

            report($e);
        }

        return back();
    }
}

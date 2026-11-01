<?php

namespace Gabha\Notifications\Http\Controllers\Admin;

use Gabha\Notifications\Mail\TestEmail;
use Gabha\Notifications\Models\EmailLog;
use Gabha\Notifications\Models\EmailTemplateSetting;
use Gabha\Notifications\Services\EmailDispatcher;
use Gabha\Notifications\Templates\EmailCatalog;
use Gabha\Notifications\Templates\RenderedEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Email Templates: every customer email with its subject (editable), a
 * preview and a test send, both built from the latest real record.
 */
class EmailTemplateController extends Controller
{
    /** Test sends per admin per hour: the sender's reputation is shared with customers. */
    public const TEST_LIMIT = 5;

    public function __construct(protected EmailCatalog $catalog) {}

    public function index()
    {
        return view('notifications::admin.templates.index', [
            'groups'   => collect($this->catalog->all())->groupBy('group', preserveKeys: true),
            'subjects' => EmailTemplateSetting::subjects(),
        ]);
    }

    public function edit(string $key)
    {
        $entry = $this->entry($key);
        $rendered = $this->render($key);

        return view('notifications::admin.templates.edit', [
            'key'          => $key,
            'entry'        => $entry,
            'custom'       => EmailTemplateSetting::subjects()[$key] ?? '',
            'rendered'     => $rendered,
            'placeholders' => EmailCatalog::PLACEHOLDERS,
            'testsLeft'    => RateLimiter::remaining($this->limiterKey(), self::TEST_LIMIT),
        ]);
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $this->entry($key);

        $data = $request->validate(['subject' => ['nullable', 'string', 'max:200']]);

        EmailTemplateSetting::updateOrCreate(['key' => $key], ['subject' => trim((string) ($data['subject'] ?? '')) ?: null]);

        session()->flash('success', trans('notifications::app.admin.templates.saved'));

        return redirect()->route('admin.notifications.templates.edit', $key);
    }

    /**
     * The email exactly as rendered, for the preview frame. Never sends.
     */
    public function preview(string $key, string $format = 'html'): Response
    {
        $this->entry($key);

        $rendered = $this->render($key);

        if (! $rendered) {
            return response(trans('notifications::app.admin.no-preview'));
        }

        return $format === 'text'
            ? response($rendered->text, 200, ['Content-Type' => 'text/plain; charset=UTF-8'])
            : response($rendered->html);
    }

    public function test(Request $request, string $key): RedirectResponse
    {
        $this->entry($key);

        $data = $request->validate(['email' => ['required', 'email:rfc,strict', 'max:160']]);

        $back = redirect()->route('admin.notifications.templates.edit', $key);

        if (! EmailDispatcher::deliverable($data['email'])) {
            return $back->with('error', trans('notifications::app.admin.templates.test-invalid'));
        }

        if (RateLimiter::tooManyAttempts($this->limiterKey(), self::TEST_LIMIT)) {
            return $back->with('error', trans('notifications::app.admin.templates.test-limit', ['limit' => self::TEST_LIMIT]));
        }

        $rendered = $this->render($key);

        if (! $rendered) {
            return $back->with('error', trans('notifications::app.admin.no-preview'));
        }

        RateLimiter::hit($this->limiterKey(), 3600);

        try {
            Mail::send(new TestEmail($data['email'], $rendered->subject, $rendered->html, $rendered->text));
        } catch (Throwable $e) {
            report($e);

            EmailLog::create([
                'type'      => 'test.'.$key,
                'mailable'  => TestEmail::class,
                'recipient' => mb_strtolower($data['email']),
                'subject'   => mb_substr('[Test] '.$rendered->subject, 0, 255),
                'status'    => EmailLog::STATUS_FAILED,
                'error'     => mb_substr($e->getMessage(), 0, 1000),
            ]);

            return $back->with('error', trans('notifications::app.admin.templates.test-failed'));
        }

        return $back->with('success', trans('notifications::app.admin.templates.test-sent', ['email' => $data['email']]));
    }

    protected function entry(string $key): array
    {
        return $this->catalog->get($key) ?? abort(404);
    }

    protected function render(string $key): ?RenderedEmail
    {
        try {
            $mailable = $this->catalog->sample($key);

            return $mailable ? RenderedEmail::from($key, $mailable) : null;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    protected function limiterKey(): string
    {
        return 'email-template-test:'.auth()->guard('admin')->id();
    }
}

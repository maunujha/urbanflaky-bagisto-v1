<?php

namespace Gabha\Notifications\Http\Controllers\Admin;

use Gabha\Notifications\Models\EmailLog;
use Gabha\Notifications\Models\EmailSuppression;
use Gabha\Notifications\Models\MarketingConsent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Webkul\Core\Models\SubscribersList;

/**
 * Every email the store queued or sent, with its delivery status. Previews
 * and test sends live on Customer Emails (EmailTemplateController).
 */
class EmailLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = EmailLog::query()
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('type'), fn ($query, $type) => $query->where('type', $type))
            ->when($request->query('q'), fn ($query, $q) => $query->where('recipient', 'like', '%'.mb_strtolower($q).'%'))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('notifications::admin.logs.index', [
            'logs'    => $logs,
            'types'   => EmailLog::query()->distinct()->orderBy('type')->pluck('type'),
            'consent' => $this->consent((string) $request->query('q')),
            'stats'   => [
                'sent-today'      => EmailLog::where('status', EmailLog::STATUS_SENT)->where('sent_at', '>=', now()->startOfDay())->count(),
                'failed-week'     => EmailLog::where('status', EmailLog::STATUS_FAILED)->where('created_at', '>=', now()->subDays(7))->count(),
                'marketing-today' => EmailLog::where('category', EmailLog::CATEGORY_MARKETING)->whereIn('status', EmailLog::OUTGOING)->where('created_at', '>=', now()->startOfDay())->count()
                    .' / '.config('notifications.limits.marketing_daily_cap'),
            ],
        ]);
    }

    /**
     * Re-queues the exact job that failed, once. Refused when the email has
     * gone out another way since (a later send took its dedupe key), so a
     * retry can never mail the customer twice.
     */
    public function retry(int $id)
    {
        $log = EmailLog::findOrFail($id);

        if (! $log->canRetry() || ! app('queue.failer')->find($log->job_uuid)) {
            return back()->with('error', trans('notifications::app.admin.retry.unavailable'));
        }

        $key = $log->originalDedupeKey();

        if ($key && EmailLog::where('dedupe_key', $key)->whereKeyNot($log->id)->exists()) {
            return back()->with('error', trans('notifications::app.admin.retry.already-sent'));
        }

        try {
            /* Claimed atomically: a double click re-queues it only once. */
            $claimed = EmailLog::whereKey($log->id)
                ->where('status', EmailLog::STATUS_FAILED)
                ->update(['status' => EmailLog::STATUS_QUEUED, 'error' => null, 'dedupe_key' => $key, 'job_uuid' => null]);
        } catch (UniqueConstraintViolationException) {
            return back()->with('error', trans('notifications::app.admin.retry.already-sent'));
        }

        if (! $claimed) {
            return back()->with('error', trans('notifications::app.admin.retry.unavailable'));
        }

        Artisan::call('queue:retry', ['id' => [$log->job_uuid]]);

        return back()->with('success', trans('notifications::app.admin.retry.queued'));
    }

    /**
     * Marketing consent for an exact address typed in the search box.
     *
     * @return array<string, mixed>|null
     */
    protected function consent(string $q): ?array
    {
        $email = mb_strtolower(trim($q));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return [
            'email'        => $email,
            'subscribed'   => SubscribersList::where('email', $email)->where('is_subscribed', 1)->exists(),
            'suppressions' => EmailSuppression::where('email', $email)->pluck('scope')->all(),
            'history'      => MarketingConsent::where('email', $email)->latest('id')->limit(10)->get(),
        ];
    }
}

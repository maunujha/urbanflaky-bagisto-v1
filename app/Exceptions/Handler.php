<?php

namespace App\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Webkul\Core\Exceptions\Handler as CoreHandler;

/**
 * Bagisto's production handler (debug off) with two corrections:
 *
 *  - Client errors core does not know keep their real status. Core maps every
 *    status outside 401/403/404/503 to 500, so a throttled request (429), a
 *    wrong HTTP method (405) and an expired session/CSRF token (419) all
 *    showed "500 Internal Server Error". Headers (Retry-After, Allow) are kept.
 *  - HttpResponseException returns its own response. Core's Throwable
 *    catch-all rendered it as a 500, breaking limiters with a custom response.
 *
 * Registered before core's callbacks (first match wins); all other
 * exceptions render exactly as core does.
 */
class Handler extends CoreHandler
{
    /**
     * Statuses rendered with their own code and copy (`{ns}::app.errors.{code}`).
     */
    protected const PRESERVED_STATUSES = [405, 419, 429];

    /**
     * Render the preserved client errors with their own status, headers and copy.
     */
    protected function handleHttpException(): void
    {
        $this->renderable(function (HttpException $exception, Request $request) {
            $status = $exception->getStatusCode();

            if (! in_array($status, self::PRESERVED_STATUSES, true)) {
                return null;
            }

            $namespace = $request->is(config('app.admin_url').'/*') ? 'admin' : 'shop';
            $headers = $exception->getHeaders();

            if ($request->wantsJson()) {
                return response()->json([
                    'error'       => trans("{$namespace}::app.errors.{$status}.title"),
                    'description' => trans("{$namespace}::app.errors.{$status}.description"),
                    'message'     => trans("{$namespace}::app.errors.{$status}.description"),
                ], $status, $headers);
            }

            return response()->view("{$namespace}::errors.index", ['errorCode' => $status], $status, $headers);
        });

        parent::handleHttpException();
    }

    /**
     * Let HttpResponseException through before core's Throwable catch-all.
     */
    protected function handleServerException(): void
    {
        $this->renderable(fn (HttpResponseException $exception) => $exception->getResponse());

        parent::handleServerException();
    }
}

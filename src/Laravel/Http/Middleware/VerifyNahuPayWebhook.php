<?php

declare(strict_types=1);

namespace NahuPay\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use NahuPay\Exception\WebhookException;
use NahuPay\NahuPay;

/**
 * Laravel middleware that verifies the X-NahuPay-Signature header.
 *
 * The verified event payload is attached to the request under the
 * ``nahupay_event`` attribute for downstream handlers to consume.
 *
 * Route registration (routes/api.php):
 *
 * ```php
 * Route::post('/webhooks/nahupay', WebhookController::class)
 *      ->middleware(\NahuPay\Laravel\Http\Middleware\VerifyNahuPayWebhook::class);
 * ```
 *
 * ⚠️  IMPORTANT: This middleware must receive the raw request body.
 * If you use any other middleware that consumes the body first (e.g. JSON parsing),
 * move this middleware before it or use a dedicated route group without those middlewares.
 *
 * In your controller:
 *
 * ```php
 * public function __invoke(Request $request): JsonResponse
 * {
 *     $event = $request->attributes->get('nahupay_event'); // array
 *     $payment = $event['data'];
 *
 *     if ($event['event'] === 'payment.success') {
 *         // fulfil the order ...
 *     }
 *
 *     return response()->json(['received' => true]);
 * }
 * ```
 */
class VerifyNahuPayWebhook
{
    public function handle(Request $request, Closure $next): mixed
    {
        $rawBody   = $request->getContent();
        $signature = $request->header('X-NahuPay-Signature');
        $secret    = config('nahupay.secret_key', '');

        try {
            $event = NahuPay::webhooks()->verify($rawBody, $signature, $secret);
        } catch (WebhookException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $request->attributes->set('nahupay_event', $event);

        return $next($request);
    }
}

<?php

/**
 * Example: NahuPay integration in a Laravel application.
 *
 * Add these routes to routes/api.php (or routes/web.php).
 *
 * Setup:
 *   1. composer require nahupay/nahupay-php
 *   2. Add NAHUPAY_SECRET_KEY=sk_test_... to .env
 *   3. php artisan vendor:publish --tag=nahupay
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use NahuPay\Exception\ApiException;
use NahuPay\Exception\WebhookException;
use NahuPay\Laravel\Facades\NahuPay;

// ── Create a payment ──────────────────────────────────────────────────────────
Route::post('/checkout', function (Request $request) {
    $request->validate([
        'amount'       => 'required|numeric|min:1',
        'email'        => 'required|email',
        'name'         => 'nullable|string|max:255',
        'description'  => 'nullable|string|max:500',
    ]);

    try {
        $payment = NahuPay::payments()->create([
            'amount'        => $request->float('amount'),
            'customerEmail' => $request->string('email')->toString(),
            'customerName'  => $request->string('name')->toString(),
            'description'   => $request->string('description')->toString(),
            'returnUrl'     => route('checkout.return') . '?ref={PAYMENT_REFERENCE}',
            'webhookUrl'    => route('webhooks.nahupay'),
            'metadata'      => [
                'user_id'    => auth()->id(),
                'ip_address' => $request->ip(),
            ],
        ]);
    } catch (ApiException $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'code'  => $e->getErrorCode(),
        ], 422);
    }

    // Option A: redirect to checkout
    return redirect($payment['checkoutUrl']);

    // Option B: return JSON (for SPA / mobile)
    // return response()->json($payment);
});


// ── Return URL after payment ──────────────────────────────────────────────────
Route::get('/checkout/return', function (Request $request) {
    $reference = $request->query('ref');
    if (!$reference) {
        return redirect('/')->with('error', 'Invalid payment reference.');
    }

    $payment = NahuPay::payments()->retrieve($reference);

    return match ($payment['status']) {
        'SUCCESS'  => redirect('/dashboard')->with('success', 'Payment successful!'),
        'FAILED'   => redirect('/checkout')->with('error', 'Payment failed. Please try again.'),
        default    => redirect('/checkout')->with('info', "Payment status: {$payment['status']}"),
    };
})->name('checkout.return');


// ── Webhook handler ───────────────────────────────────────────────────────────
//
// ⚠️  IMPORTANT: This route must be excluded from CSRF verification.
// Add the path to the $except array in app/Http/Middleware/VerifyCsrfToken.php:
//   protected $except = ['api/webhooks/nahupay'];
//
Route::post('/webhooks/nahupay', function (Request $request) {
    $rawBody   = $request->getContent();
    $signature = $request->header('X-NahuPay-Signature');

    try {
        $event = NahuPay::webhooks()->verify(
            $rawBody,
            $signature,
            config('nahupay.api_key')
        );
    } catch (WebhookException $e) {
        logger()->warning('NahuPay webhook rejected', ['error' => $e->getMessage()]);
        return response()->json(['error' => $e->getMessage()], 400);
    }

    $payment = $event['data'];

    if ($event['event'] === 'payment.success') {
        $meta = json_decode($payment['metadata'] ?? '{}', true);
        logger()->info('Payment succeeded', [
            'reference' => $payment['reference'],
            'amount'    => $payment['amount'],
            'user_id'   => $meta['user_id'] ?? null,
        ]);

        // TODO: dispatch a job to fulfil the order
        // FulfilOrderJob::dispatch($payment['reference'], $meta);
    }

    if ($event['event'] === 'payment.failed') {
        logger()->warning('Payment failed', [
            'reference' => $payment['reference'],
            'reason'    => $payment['failureReason'],
        ]);
        // TODO: notify the customer
    }

    return response()->json(['received' => true]);
})->name('webhooks.nahupay');

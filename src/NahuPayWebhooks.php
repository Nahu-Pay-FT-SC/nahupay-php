<?php

declare(strict_types=1);

namespace NahuPay;

use NahuPay\Exception\WebhookException;

/**
 * Webhook signature verification utilities.
 *
 * How NahuPay signs webhooks
 * --------------------------
 * The backend computes HMAC-SHA256(rawBody, secretKey) and sends the result
 * in the X-NahuPay-Signature header as "sha256=<hex-digest>".
 * The secretKey is the merchant's secret API key for the payment mode
 * (sk_test_… for TEST payments, sk_live_… for LIVE payments).
 *
 * Laravel / Plain PHP setup
 * -------------------------
 * You MUST receive the raw request body — not the parsed JSON — or the
 * signature will not match.
 *
 * Laravel example:
 *
 *   Route::post('/webhooks/nahupay', function (Request $request) {
 *       $event = NahuPayFacade::webhooks()->verify(
 *           $request->getContent(),
 *           $request->header('X-NahuPay-Signature'),
 *           config('nahupay.secret_key'),
 *       );
 *       // safe to use $event
 *       return response()->json(['received' => true]);
 *   });
 */
class NahuPayWebhooks
{
    /**
     * Verify a webhook request from NahuPay and return the parsed event.
     *
     * @param  string      $rawBody    Raw request body string.
     * @param  string|null $signature  Value of the X-NahuPay-Signature header.
     * @param  string      $secret     Your secret API key (sk_test_… or sk_live_…).
     * @return array{event:string,timestamp:string,data:array<string,mixed>}
     *
     * @throws WebhookException  If the signature is missing, malformed, or does not match.
     */
    public function verify(string $rawBody, ?string $signature, string $secret): array
    {
        if (empty($signature)) {
            throw new WebhookException(
                'Missing X-NahuPay-Signature header. '
                . 'Make sure you are passing the raw request body, not parsed JSON.'
            );
        }

        $prefix = 'sha256=';
        if (!str_starts_with($signature, $prefix)) {
            throw new WebhookException(
                "Unexpected signature format \"{$signature}\". Expected \"sha256=<hex>\"."
            );
        }

        $receivedHex = substr($signature, strlen($prefix));

        // Compute expected HMAC
        $expectedHex = hash_hmac('sha256', $rawBody, $secret);

        // Constant-time comparison to prevent timing attacks
        if (!hash_equals($expectedHex, $receivedHex)) {
            throw new WebhookException(
                'Webhook signature verification failed. '
                . 'Ensure you are using the correct secret key and the raw request body.'
            );
        }

        // Signature verified — safe to parse
        $data = json_decode($rawBody, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new WebhookException(
                'Webhook body is not valid JSON despite a valid signature. '
                . 'This should not happen — please contact NahuPay support.'
            );
        }

        /** @var array{event?:string,data?:array<string,mixed>} $data */
        if (empty($data['event']) || empty($data['data'])) {
            throw new WebhookException(
                'Webhook payload is missing required fields (`event` or `data`).'
            );
        }

        /** @var array{event:string,timestamp:string,data:array<string,mixed>} $data */
        return $data;
    }
}

<?php

declare(strict_types=1);

namespace NahuPay;

use NahuPay\Resources\PaymentsResource;
use NahuPay\Resources\RefundsResource;

/**
 * Main entry point for the NahuPay PHP SDK.
 *
 * @example
 * ```php
 * $nahupay = new \NahuPay\NahuPay([
 *     'api_key'  => 'sk_test_...',                       // required
 *     'base_url' => 'http://localhost:8080/api/v1',      // omit in production
 *     'timeout'  => 30,                                  // seconds
 * ]);
 *
 * $payment = $nahupay->payments->create([
 *     'amount'        => 500,
 *     'customerEmail' => 'abebe@example.com',
 *     'customerName'  => 'Abebe Bikila',
 *     'description'   => 'Order #1042',
 *     'returnUrl'     => 'https://myshop.com/ty?ref={PAYMENT_REFERENCE}',
 *     'webhookUrl'    => 'https://myshop.com/webhooks/nahupay',
 *     'metadata'      => ['order_id' => '1042'],
 * ]);
 *
 * header('Location: ' . $payment['checkoutUrl']);
 * exit;
 * ```
 */
class NahuPay
{
    /** Create, retrieve, list, cancel, refund, and simulate payments. */
    public readonly PaymentsResource $payments;

    /** Create and list refunds (top-level aliases for convenience). */
    public readonly RefundsResource $refunds;

    private static ?NahuPayWebhooks $webhooksInstance = null;

    /**
     * @param array{
     *     api_key:   string,
     *     base_url?: string,
     *     timeout?:  float|int,
     * } $config
     */
    public function __construct(array $config)
    {
        $apiKey  = $config['api_key']  ?? '';
        $baseUrl = $config['base_url'] ?? null;
        $timeout = (float) ($config['timeout'] ?? 30.0);

        self::validateConfig($apiKey, $timeout);

        $http = new HttpClient($apiKey, $baseUrl, $timeout);

        $this->payments = new PaymentsResource($http);
        $this->refunds  = new RefundsResource($http);
    }

    /**
     * Webhook signature verification utilities.
     *
     * Available as a **static** method so you do not need an instance:
     *
     * ```php
     * $event = \NahuPay\NahuPay::webhooks()->verify($rawBody, $signature, $secret);
     * ```
     */
    public static function webhooks(): NahuPayWebhooks
    {
        if (self::$webhooksInstance === null) {
            self::$webhooksInstance = new NahuPayWebhooks();
        }
        return self::$webhooksInstance;
    }

    // ─── Config validation ────────────────────────────────────────────────────

    private static function validateConfig(string $apiKey, float $timeout): void
    {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException(
                "NahuPay: api_key is required. "
                . "Pass your secret key: new NahuPay(['api_key' => 'sk_test_...'])"
            );
        }

        if (!str_starts_with($apiKey, 'sk_test_') && !str_starts_with($apiKey, 'sk_live_')) {
            $preview = substr($apiKey, 0, 10);
            throw new \InvalidArgumentException(
                "NahuPay: Invalid API key format \"{$preview}…\". "
                . 'Secret keys start with sk_test_ (sandbox) or sk_live_ (production).'
            );
        }

        if ($timeout <= 0) {
            throw new \InvalidArgumentException(
                'NahuPay: timeout must be a positive number of seconds.'
            );
        }
    }
}

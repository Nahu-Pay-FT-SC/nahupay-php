<?php

declare(strict_types=1);

namespace NahuPay\Resources;

use NahuPay\HttpClient;

/**
 * Access via {@see \NahuPay\NahuPay::$payments}.
 *
 * @example
 * ```php
 * $nahupay = new \NahuPay\NahuPay(['api_key' => 'sk_test_...']);
 *
 * $payment = $nahupay->payments->create([
 *     'amount'        => 500,
 *     'customerEmail' => 'abebe@example.com',
 *     'returnUrl'     => 'https://myshop.com/ty?ref={PAYMENT_REFERENCE}',
 *     'webhookUrl'    => 'https://myshop.com/webhooks/nahupay',
 * ]);
 *
 * header('Location: ' . $payment['checkoutUrl']);
 * ```
 */
class PaymentsResource
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    // ─── Core operations ──────────────────────────────────────────────────────

    /**
     * Create a new payment.
     *
     * @param  array{
     *     amount:         float|int,
     *     customerEmail:  string,
     *     customerName?:  string,
     *     customerPhone?: string,
     *     currency?:      string,
     *     description?:   string,
     *     callbackUrl?:   string,
     *     returnUrl?:     string,
     *     webhookUrl?:    string,
     *     metadata?:      array<string,mixed>|string,
     *     idempotencyKey?: string,
     * } $params
     * @return array<string,mixed>  The created payment.
     */
    public function create(array $params): array
    {
        // Serialise metadata array → JSON string
        if (isset($params['metadata']) && is_array($params['metadata'])) {
            $params['metadata'] = json_encode($params['metadata']);
        }

        /** @var array<string,mixed> $result */
        $result = $this->http->post('/payments', $params);
        return $result;
    }

    /**
     * Retrieve a single payment by its reference (e.g. "PAY-20260523-ABCD1234").
     *
     * @return array<string,mixed>
     */
    public function retrieve(string $reference): array
    {
        /** @var array<string,mixed> $result */
        $result = $this->http->get("/payments/{$reference}");
        return $result;
    }

    /**
     * List payments with optional server-side filtering.
     *
     * @param  array{status?:string, page?:int, size?:int} $params
     * @return array{content:array<array<string,mixed>>,totalElements:int,totalPages:int,number:int,size:int,first:bool,last:bool,empty:bool}
     */
    public function list(array $params = []): array
    {
        /** @var array{content:array<array<string,mixed>>,totalElements:int,totalPages:int,number:int,size:int,first:bool,last:bool,empty:bool} $result */
        $result = $this->http->get('/payments', [
            'status' => $params['status'] ?? null,
            'page'   => $params['page'] ?? 0,
            'size'   => $params['size'] ?? 20,
        ]);
        return $result;
    }

    /**
     * Cancel a pending payment.  Only PENDING payments can be cancelled.
     *
     * @return array<string,mixed>
     */
    public function cancel(string $reference): array
    {
        /** @var array<string,mixed> $result */
        $result = $this->http->post("/payments/{$reference}/cancel");
        return $result;
    }

    // ─── Refunds ──────────────────────────────────────────────────────────────

    /**
     * Issue a refund against a SUCCESS or PARTIALLY_REFUNDED payment.
     *
     * @param  array{amount?:float|int|null, reason?:string} $params
     *         Omit amount for a full refund of the remaining refundable balance.
     * @return array<string,mixed>
     */
    public function refund(string $reference, array $params = []): array
    {
        /** @var array<string,mixed> $result */
        $result = $this->http->post("/payments/{$reference}/refund", [
            'amount' => $params['amount'] ?? null,
            'reason' => $params['reason'] ?? null,
        ]);
        return $result;
    }

    /**
     * List all refunds issued against a payment.
     *
     * @return array<array<string,mixed>>
     */
    public function listRefunds(string $reference): array
    {
        /** @var array<array<string,mixed>> $result */
        $result = $this->http->get("/payments/{$reference}/refunds");
        return $result ?? [];
    }

    // ─── Test-mode simulation ─────────────────────────────────────────────────

    /**
     * Test mode only. Instantly mark a payment as SUCCESS.
     * Triggers webhooks and creates transactions exactly as a real payment would.
     *
     * @return array<string,mixed>
     * @throws \NahuPay\Exception\ApiException  If called with a sk_live_ key (HTTP 403).
     */
    public function simulateSuccess(string $reference): array
    {
        /** @var array<string,mixed> $result */
        $result = $this->http->post("/payments/{$reference}/simulate/success");
        return $result;
    }

    /**
     * Test mode only. Instantly mark a payment as FAILED.
     * Triggers the payment.failed webhook.
     *
     * @return array<string,mixed>
     * @throws \NahuPay\Exception\ApiException  If called with a sk_live_ key (HTTP 403).
     */
    public function simulateFailure(string $reference): array
    {
        /** @var array<string,mixed> $result */
        $result = $this->http->post("/payments/{$reference}/simulate/failure");
        return $result;
    }
}

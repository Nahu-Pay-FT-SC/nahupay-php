<?php

declare(strict_types=1);

namespace NahuPay\Resources;

use NahuPay\HttpClient;

/**
 * Top-level refunds resource — mirrors PaymentsResource::refund() but provides
 * a standalone namespace for code that works exclusively with refunds.
 *
 * Access via {@see \NahuPay\NahuPay::$refunds}.
 */
class RefundsResource
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Issue a refund against a payment.
     *
     * @param  array{amount?:float|int|null, reason?:string} $params
     *         Omit amount for a full refund.
     * @return array<string,mixed>
     *
     * @example
     * ```php
     * $refund = $nahupay->refunds->create('PAY-xxx', ['amount' => 250]);
     * echo $refund['refundReference']; // "REF-20260523-..."
     * echo $refund['status'];          // "PENDING"
     * ```
     */
    public function create(string $paymentReference, array $params = []): array
    {
        /** @var array<string,mixed> $result */
        $result = $this->http->post("/payments/{$paymentReference}/refund", [
            'amount' => $params['amount'] ?? null,
            'reason' => $params['reason'] ?? null,
        ]);
        return $result;
    }

    /**
     * List all refunds for a given payment.
     *
     * @return array<array<string,mixed>>
     *
     * @example
     * ```php
     * $refunds = $nahupay->refunds->list('PAY-xxx');
     * ```
     */
    public function list(string $paymentReference): array
    {
        /** @var array<array<string,mixed>> $result */
        $result = $this->http->get("/payments/{$paymentReference}/refunds");
        return $result ?? [];
    }
}

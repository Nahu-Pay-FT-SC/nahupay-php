<?php

declare(strict_types=1);

namespace NahuPay\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use NahuPay\NahuPayWebhooks;
use NahuPay\Resources\PaymentsResource;
use NahuPay\Resources\RefundsResource;

/**
 * Laravel facade for the NahuPay SDK.
 *
 * @see \NahuPay\NahuPay
 *
 * @property-read PaymentsResource $payments
 * @property-read RefundsResource  $refunds
 *
 * @method static PaymentsResource payments()
 * @method static RefundsResource  refunds()
 * @method static NahuPayWebhooks  webhooks()
 *
 * @example
 * ```php
 * use NahuPay\Laravel\Facades\NahuPay;
 *
 * $payment = NahuPay::payments()->create([
 *     'amount'        => 500,
 *     'customerEmail' => 'abebe@example.com',
 * ]);
 *
 * $event = NahuPay::webhooks()->verify($rawBody, $signature, config('nahupay.api_key'));
 * ```
 */
class NahuPay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'nahupay';
    }
}

<?php

declare(strict_types=1);

namespace NahuPay\Exception;

/**
 * Thrown by {@see \NahuPay\NahuPayWebhooks::verify()} when the incoming
 * request cannot be authenticated as a genuine NahuPay webhook.
 */
class WebhookException extends NahuPayException
{
}

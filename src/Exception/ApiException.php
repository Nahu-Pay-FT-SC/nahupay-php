<?php

declare(strict_types=1);

namespace NahuPay\Exception;

/**
 * Thrown when the NahuPay API returns a non-2xx HTTP status or
 * a {@code {"success": false}} envelope.
 */
class ApiException extends NahuPayException
{
    /** @var int HTTP status code (e.g. 400, 401, 404). 0 = network error. */
    private int $statusCode;

    /** @var string|null Machine-readable error code from the API (e.g. "NOT_FOUND"). */
    private ?string $errorCode;

    /** @var array<string,mixed>|null Full parsed response body. */
    private ?array $raw;

    public function __construct(
        string $message,
        int $statusCode = 0,
        ?string $errorCode = null,
        ?array $raw = null
    ) {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
        $this->errorCode  = $errorCode;
        $this->raw        = $raw;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /** @return array<string,mixed>|null */
    public function getRaw(): ?array
    {
        return $this->raw;
    }
}

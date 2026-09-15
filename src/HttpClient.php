<?php

declare(strict_types=1);

namespace NahuPay;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use NahuPay\Exception\ApiException;

/**
 * Thin Guzzle wrapper used internally by all resource classes.
 *
 * Handles:
 * - Authorization header injection (Bearer <apiKey>)
 * - JSON serialisation / deserialisation
 * - ApiEnvelope unwrapping ({ success, data, message })
 * - ApiException on non-2xx or success=false responses
 * - Request timeout
 */
class HttpClient
{
    private const DEFAULT_BASE_URL = 'https://api.nahupay.com/api/v1';
    private const SDK_VERSION      = '0.1.0';

    private Client $guzzle;
    private string $baseUrl;

    public function __construct(
        string  $apiKey,
        ?string $baseUrl = null,
        float   $timeout = 30.0,
        ?Client $guzzle  = null
    ) {
        $this->baseUrl = rtrim($baseUrl ?? self::DEFAULT_BASE_URL, '/');

        $this->guzzle = $guzzle ?? new Client([
            'timeout'         => $timeout,
            'connect_timeout' => 10,
            'headers'         => [
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'User-Agent'    => 'nahupay-php/' . self::SDK_VERSION,
            ],
        ]);
    }

    // ─── Public helpers ───────────────────────────────────────────────────────

    /**
     * @param array<string,mixed> $query
     * @return mixed
     */
    public function get(string $path, array $query = []): mixed
    {
        $cleanQuery = array_filter(
            $query,
            fn ($v) => $v !== null && $v !== ''
        );

        return $this->request('GET', $path, ['query' => $cleanQuery]);
    }

    /**
     * @param array<string,mixed>|null $body
     * @return mixed
     */
    public function post(string $path, ?array $body = null): mixed
    {
        $options = $body !== null ? ['json' => $body] : [];
        return $this->request('POST', $path, $options);
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    /**
     * @param array<string,mixed> $options
     * @return mixed
     */
    private function request(string $method, string $path, array $options = []): mixed
    {
        try {
            $response = $this->guzzle->request(
                $method,
                $this->baseUrl . $path,
                $options
            );
        } catch (ConnectException $e) {
            throw new ApiException(
                "Connection error: {$e->getMessage()}",
                0,
                'CONNECTION_ERROR'
            );
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response === null) {
                throw new ApiException(
                    "Request failed: {$e->getMessage()}",
                    0,
                    'REQUEST_FAILED'
                );
            }
            // Fall through to parse the error response body
        }

        $statusCode = isset($response) ? $response->getStatusCode() : 0;
        $body       = isset($response) ? (string) $response->getBody() : '{}';

        $parsed = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($parsed)) {
            throw new ApiException(
                "Unexpected non-JSON response (HTTP {$statusCode})",
                $statusCode,
                'INVALID_RESPONSE'
            );
        }

        /** @var array<string,mixed> $parsed */
        if (($parsed['success'] ?? true) === false || $statusCode >= 400) {
            throw new ApiException(
                (string) ($parsed['message'] ?? "API error (HTTP {$statusCode})"),
                $statusCode,
                isset($parsed['errorCode']) ? (string) $parsed['errorCode'] : null,
                $parsed
            );
        }

        return $parsed['data'] ?? null;
    }
}

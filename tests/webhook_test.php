<?php
// Standalone PHP webhook test (no Composer/Guzzle needed)
spl_autoload_register(function ($class) {
    $base = __DIR__ . '/../src/';
    $file = $base . str_replace(['NahuPay\\', '\\'], ['', '/'], $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use NahuPay\NahuPayWebhooks;
use NahuPay\Exception\WebhookException;

$webhooks = new NahuPayWebhooks();
$secret   = 'sk_test_abc123';

$payload = json_encode([
    'event'     => 'payment.success',
    'timestamp' => '2026-05-23T11:05:00',
    'data'      => [
        'id'            => '550e8400-e29b-41d4-a716-446655440000',
        'reference'     => 'PAY-20260523-ABCD1234',
        'amount'        => '500.00',
        'currency'      => 'ETB',
        'status'        => 'SUCCESS',
        'mode'          => 'TEST',
        'customerEmail' => 'abebe@example.com',
        'checkoutUrl'   => 'https://checkout.nahupay.com/PAY-20260523-ABCD1234',
        'expiresAt'     => '2026-05-23T12:00:00',
        'createdAt'     => '2026-05-23T11:00:00',
    ],
]);

$sign = fn (string $body, ?string $s = null): string =>
    'sha256=' . hash_hmac('sha256', $body, $s ?? $secret);

$pass = 0;
$fail = 0;

function ok(string $label): void
{
    global $pass;
    $pass++;
    echo "✅  {$label}\n";
}

function fail(string $label, string $err): void
{
    global $fail;
    $fail++;
    echo "❌  {$label}: {$err}\n";
}

// Test 1: valid signature
try {
    $event = $webhooks->verify($payload, $sign($payload), $secret);
    assert($event['event'] === 'payment.success');
    assert($event['data']['reference'] === 'PAY-20260523-ABCD1234');
    ok('Test 1: valid signature');
} catch (\Throwable $e) {
    fail('Test 1', $e->getMessage());
}

// Test 2: missing signature
try {
    $webhooks->verify($payload, null, $secret);
    fail('Test 2', 'Should have thrown');
} catch (WebhookException $e) {
    assert(str_contains($e->getMessage(), 'Missing'));
    ok('Test 2: missing signature');
}

// Test 3: wrong prefix
try {
    $webhooks->verify($payload, 'md5=abc123', $secret);
    fail('Test 3', 'Should have thrown');
} catch (WebhookException $e) {
    assert(str_contains($e->getMessage(), 'Unexpected'));
    ok('Test 3: wrong prefix');
}

// Test 4: wrong secret
try {
    $webhooks->verify($payload, $sign($payload, 'wrong_secret'), $secret);
    fail('Test 4', 'Should have thrown');
} catch (WebhookException $e) {
    assert(str_contains($e->getMessage(), 'verification failed'));
    ok('Test 4: wrong secret');
}

// Test 5: tampered body
try {
    $tampered = str_replace('payment.success', 'payment.failed', $payload);
    $webhooks->verify($tampered, $sign($payload), $secret);
    fail('Test 5', 'Should have thrown');
} catch (WebhookException $e) {
    assert(str_contains($e->getMessage(), 'verification failed'));
    ok('Test 5: tampered body');
}

// Test 6: invalid JSON
try {
    $bad = 'not json at all';
    $webhooks->verify($bad, $sign($bad), $secret);
    fail('Test 6', 'Should have thrown');
} catch (WebhookException $e) {
    assert(str_contains($e->getMessage(), 'JSON'));
    ok('Test 6: invalid JSON');
}

// Test 7: missing event field
try {
    $bad2 = json_encode(['timestamp' => '2026-05-23', 'data' => ['reference' => 'PAY']]);
    $webhooks->verify($bad2, $sign($bad2), $secret);
    fail('Test 7', 'Should have thrown');
} catch (WebhookException $e) {
    assert(str_contains($e->getMessage(), 'missing required fields'));
    ok('Test 7: missing event field');
}

echo "\n";
echo $fail === 0
    ? "🎉  All {$pass} PHP webhook tests passed!\n"
    : "💥  {$fail} test(s) FAILED, {$pass} passed.\n";

exit($fail);

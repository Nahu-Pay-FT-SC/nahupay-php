# nahupay-php

Official PHP / Laravel SDK for the **NahuPay** payment platform.

Requires PHP ≥ 8.1. Ships with built-in **Laravel** auto-discovery (service provider + facade + middleware).

---

## Installation

```bash
composer require nahupay/nahupay-php
```

---

## Plain PHP quick start

```php
$nahupay = new \NahuPay\NahuPay([
    'api_key'  => $_ENV['NAHUPAY_SECRET_KEY'],   // sk_test_… or sk_live_…
    'base_url' => 'http://localhost:8080/api/v1', // omit in production
]);
```

---

## Laravel quick start

The package auto-discovers via Composer. Add your key to `.env`:

```env
NAHUPAY_SECRET_KEY=sk_test_...
NAHUPAY_API_URL=https://api.nahupay.com/api/v1
```

Publish the config (optional):

```bash
php artisan vendor:publish --tag=nahupay
```

Then use the facade:

```php
use NahuPay\Laravel\Facades\NahuPay;

$payment = NahuPay::payments()->create([
    'amount'        => 500,
    'customerEmail' => 'abebe@example.com',
]);

return redirect($payment['checkoutUrl']);
```

---

## Payments

### Create a payment

```php
$payment = $nahupay->payments->create([
    'amount'        => 500,                    // ETB, minimum 1.00
    'customerEmail' => 'abebe@example.com',
    'customerName'  => 'Abebe Bikila',
    'description'   => 'Order #1042',
    'returnUrl'     => 'https://myshop.com/ty?ref={PAYMENT_REFERENCE}',
    'webhookUrl'    => 'https://myshop.com/webhooks/nahupay',
    'metadata'      => ['order_id' => '1042'], // serialised to JSON
]);

header('Location: ' . $payment['checkoutUrl']);
exit;
```

### Retrieve, list, cancel

```php
$payment = $nahupay->payments->retrieve('PAY-20260523-ABCD1234');
echo $payment['status'];  // "SUCCESS"

$page = $nahupay->payments->list(['status' => 'SUCCESS', 'size' => 50]);
echo $page['totalElements'];
foreach ($page['content'] as $p) { … }

$nahupay->payments->cancel('PAY-xxx');
```

---

## Refunds

```php
// Full refund
$refund = $nahupay->payments->refund('PAY-xxx');

// Partial refund
$refund = $nahupay->payments->refund('PAY-xxx', ['amount' => 100, 'reason' => 'Partial return']);

// List refunds
$refunds = $nahupay->payments->listRefunds('PAY-xxx');

// Top-level aliases
$refund  = $nahupay->refunds->create('PAY-xxx', ['amount' => 100]);
$refunds = $nahupay->refunds->list('PAY-xxx');
```

---

## Webhooks

### Plain PHP

```php
$rawBody   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_NAHUPAY_SIGNATURE'] ?? null;

try {
    $event = \NahuPay\NahuPay::webhooks()->verify(
        $rawBody,
        $signature,
        $_ENV['NAHUPAY_SECRET_KEY'],
    );
} catch (\NahuPay\Exception\WebhookException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

$payment = $event['data'];
if ($event['event'] === 'payment.success') {
    $meta = json_decode($payment['metadata'] ?? '{}', true);
    // fulfil the order …
}

echo json_encode(['received' => true]);
```

### Laravel (with VerifyNahuPayWebhook middleware)

```php
// routes/api.php
Route::post('/webhooks/nahupay', WebhookController::class)
     ->middleware(\NahuPay\Laravel\Http\Middleware\VerifyNahuPayWebhook::class)
     ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// WebhookController.php
public function __invoke(Request $request): JsonResponse
{
    $event   = $request->attributes->get('nahupay_event');
    $payment = $event['data'];

    if ($event['event'] === 'payment.success') {
        // fulfil the order …
    }

    return response()->json(['received' => true]);
}
```

---

## Test mode & simulation

```php
// Instantly mark a payment as SUCCESS (fires webhook)
$paid = $nahupay->payments->simulateSuccess('PAY-xxx');

// Instantly mark a payment as FAILED
$failed = $nahupay->payments->simulateFailure('PAY-xxx');
```

---

## Error handling

```php
use NahuPay\Exception\ApiException;
use NahuPay\Exception\WebhookException;

try {
    $nahupay->payments->retrieve('PAY-does-not-exist');
} catch (ApiException $e) {
    echo $e->getStatusCode();   // 404
    echo $e->getErrorCode();    // "NOT_FOUND"
    echo $e->getMessage();      // "Payment not found"
}
```

---

## SDK architecture

| File | Role |
|------|------|
| `src/NahuPay.php` | Main class — validates key, exposes `payments`, `refunds`, `webhooks()` |
| `src/HttpClient.php` | Guzzle wrapper — auth headers, JSON, envelope unwrap, error handling |
| `src/Resources/PaymentsResource.php` | All 8 payment methods |
| `src/Resources/RefundsResource.php` | create + list |
| `src/NahuPayWebhooks.php` | `verify()` — HMAC-SHA256 constant-time compare |
| `src/Exception/` | `NahuPayException`, `ApiException`, `WebhookException` |
| `src/Laravel/NahuPayServiceProvider.php` | Service provider — binds, publishes config |
| `src/Laravel/Facades/NahuPay.php` | Laravel facade |
| `src/Laravel/Http/Middleware/VerifyNahuPayWebhook.php` | Webhook verification middleware |
| `config/nahupay.php` | Config template |

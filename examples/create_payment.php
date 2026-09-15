<?php

/**
 * Example: create a payment and simulate success (test mode).
 *
 * Run:
 *   composer install
 *   NAHUPAY_SECRET_KEY=sk_test_... php examples/create_payment.php
 */

require __DIR__ . '/../vendor/autoload.php';

use NahuPay\Exception\ApiException;
use NahuPay\NahuPay;

$secretKey = getenv('NAHUPAY_SECRET_KEY') ?: 'sk_test_replace_me';
$apiUrl    = getenv('NAHUPAY_API_URL')    ?: 'http://localhost:8080/api/v1';

$nahupay = new NahuPay([
    'api_key'  => $secretKey,
    'base_url' => $apiUrl,
]);

// ── 1. Create a payment ───────────────────────────────────────────────────────
echo "Creating payment …\n";
try {
    $payment = $nahupay->payments->create([
        'amount'        => 500,
        'customerEmail' => 'abebe@example.com',
        'customerName'  => 'Abebe Bikila',
        'description'   => 'Order #1042 — 2 items',
        'returnUrl'     => 'http://localhost:3002/thank-you?ref={PAYMENT_REFERENCE}',
        'webhookUrl'    => 'http://localhost:3002/webhooks/nahupay',
        'metadata'      => ['order_id' => '1042', 'user_id' => 'u_abc'],
    ]);
} catch (ApiException $e) {
    echo "Error: {$e->getMessage()}  (status={$e->getStatusCode()}, code={$e->getErrorCode()})\n";
    exit(1);
}

echo "  Reference  : {$payment['reference']}\n";
echo "  Status     : {$payment['status']}\n";
echo "  Checkout   : {$payment['checkoutUrl']}\n";
echo "  Expires at : {$payment['expiresAt']}\n\n";

// ── 2. Retrieve the payment ───────────────────────────────────────────────────
echo "Retrieving payment …\n";
$fetched = $nahupay->payments->retrieve($payment['reference']);
echo "  Status: {$fetched['status']}\n\n";

// ── 3. Simulate success (test mode only) ─────────────────────────────────────
if (str_starts_with($secretKey, 'sk_test_')) {
    echo "Simulating success …\n";
    $paid = $nahupay->payments->simulateSuccess($payment['reference']);
    echo "  Status : {$paid['status']}\n";
    echo "  Paid at: {$paid['paidAt']}\n\n";

    // ── 4. Partial refund ─────────────────────────────────────────────────────
    echo "Issuing partial refund (ETB 100) …\n";
    $refund = $nahupay->payments->refund($payment['reference'], [
        'amount' => 100,
        'reason' => 'Customer request',
    ]);
    echo "  Refund ref : {$refund['refundReference']}\n";
    echo "  Status     : {$refund['status']}\n\n";

    // ── 5. List refunds ───────────────────────────────────────────────────────
    echo "Listing refunds …\n";
    $refunds = $nahupay->payments->listRefunds($payment['reference']);
    foreach ($refunds as $r) {
        echo "  {$r['refundReference']}  ETB {$r['amount']}  {$r['status']}\n";
    }
    echo "\n";
}

// ── 6. List payments ──────────────────────────────────────────────────────────
echo "Listing recent payments …\n";
$page = $nahupay->payments->list(['size' => 5]);
echo "  Total: {$page['totalElements']} payments ({$page['totalPages']} pages)\n";
foreach ($page['content'] as $p) {
    echo "  {$p['reference']}  ETB {$p['amount']}  {$p['status']}\n";
}

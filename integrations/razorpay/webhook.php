<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/config.php';

$secret = setting('razorpay_webhook_secret', '');
if (!$secret) { http_response_code(500); exit('Webhook secret not set'); }

$raw = file_get_contents('php://input');
$sig = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';
$expected = hash_hmac('sha256', $raw, $secret);
if (!hash_equals($expected, $sig)) { http_response_code(401); exit('Bad signature'); }

$data = json_decode($raw, true) ?: [];
$event = $data['event'] ?? '';
$paymentEntity = $data['payload']['payment']['entity'] ?? [];

if ($event === 'payment.captured' && !empty($paymentEntity['order_id'])) {
    $orderId = $paymentEntity['order_id'];
    $payment = db_one('SELECT * FROM payments WHERE order_id=?',[$orderId]);
    if ($payment && $payment['status'] !== 'successful') {
        // Mark successful — enrollment flow will be idempotent via verify-payment if user returns
        db_update('payments', [
            'payment_id' => $paymentEntity['id'] ?? null,
            'status'     => 'successful',
            'method'     => $paymentEntity['method'] ?? null,
            'paid_at'    => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id'=>$payment['id']]);
        db_insert('payment_transactions', ['payment_id'=>$payment['id'],'event_type'=>'webhook.captured','payload_json'=>$raw]);
        createAuditLog('payment.webhook_captured','payment',$payment['id']);
    }
}

if ($event === 'payment.failed' && !empty($paymentEntity['order_id'])) {
    $payment = db_one('SELECT * FROM payments WHERE order_id=?',[$paymentEntity['order_id']]);
    if ($payment) {
        db_update('payments', ['status'=>'failed'], 'id = :id', ['id'=>$payment['id']]);
        db_insert('payment_transactions', ['payment_id'=>$payment['id'],'event_type'=>'webhook.failed','payload_json'=>$raw]);
    }
}

http_response_code(200);
echo 'ok';
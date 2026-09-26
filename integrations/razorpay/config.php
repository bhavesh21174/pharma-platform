<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

function razorpay_config(): array {
    return [
        'key_id'     => setting('razorpay_key_id', ''),
        'key_secret' => setting('razorpay_key_secret', ''),
        'currency'   => setting('razorpay_currency', 'INR'),
    ];
}

/** cURL helper for Razorpay REST */
function razorpay_request(string $method, string $endpoint, array $body = []): array {
    $cfg = razorpay_config();
    if (!$cfg['key_id'] || !$cfg['key_secret']) throw new RuntimeException('Razorpay not configured.');

    $ch = curl_init('https://api.razorpay.com/v1/' . ltrim($endpoint,'/'));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => $cfg['key_id'] . ':' . $cfg['key_secret'],
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_POSTFIELDS     => $body ? json_encode($body) : null,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($resp === false) throw new RuntimeException('Razorpay network: '.$err);
    $data = json_decode($resp, true) ?: [];
    if ($code >= 400) throw new RuntimeException('Razorpay '.$code.': '.($data['error']['description'] ?? $resp));
    return $data;
}
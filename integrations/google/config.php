<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

function google_config(): array {
    return [
        'client_email'  => setting('google_client_email', ''),
        'private_key'   => str_replace('\\n', "\n", (string) setting('google_private_key', '')),
        'calendar_id'   => setting('google_calendar_id', 'primary'),
        'timezone'      => setting('timezone', 'Asia/Kolkata'),
    ];
}

/** Base64url helpers */
function b64url(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/** Create a JWT and exchange for an OAuth2 access token (service account). */
function google_access_token(): string {
    static $cached = null;
    static $expires = 0;
    if ($cached && time() < $expires - 60) return $cached;

    $cfg = google_config();
    if (!$cfg['client_email'] || !$cfg['private_key']) throw new RuntimeException('Google not configured.');

    $now = time();
    $header = ['alg'=>'RS256','typ'=>'JWT'];
    $claim = [
        'iss'   => $cfg['client_email'],
        'scope' => 'https://www.googleapis.com/auth/calendar',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => $now,
        'exp'   => $now + 3600,
    ];
    $unsigned = b64url(json_encode($header)) . '.' . b64url(json_encode($claim));

    $key = openssl_pkey_get_private($cfg['private_key']);
    if (!$key) throw new RuntimeException('Invalid Google private key.');
    openssl_sign($unsigned, $sig, $key, OPENSSL_ALGO_SHA256);
    $jwt = $unsigned . '.' . b64url($sig);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type'=>'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'=>$jwt,
        ]),
        CURLOPT_TIMEOUT => 30,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($resp === false || $code >= 400) throw new RuntimeException('Google token error: '.$resp);

    $data = json_decode($resp, true) ?: [];
    if (empty($data['access_token'])) throw new RuntimeException('No access token.');
    $cached  = $data['access_token'];
    $expires = time() + (int)($data['expires_in'] ?? 3600);
    return $cached;
}
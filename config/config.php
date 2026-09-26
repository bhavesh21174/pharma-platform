<?php
declare(strict_types=1);

// ---------- Environment ----------
define('APP_ENV', getenv('APP_ENV') ?: 'development'); // development|production
$demoPaymentsSetting = getenv('DEMO_PAYMENTS_ENABLED');
define('DEMO_PAYMENTS_ENABLED', APP_ENV === 'development'
    && ($demoPaymentsSetting === false || filter_var($demoPaymentsSetting, FILTER_VALIDATE_BOOLEAN)));

// ---------- Site ----------
define('SITE_NAME', 'Pharma Academy');
define('SITE_URL',  'http://localhost/pharma-platform');
define('TIMEZONE',  'Asia/Kolkata');

// ---------- Paths ----------
define('BASE_PATH',   dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/assets/uploads');
define('UPLOAD_URL',  SITE_URL . '/assets/uploads');
define('LOG_PATH',    BASE_PATH . '/logs');

// ---------- Session ----------
define('SESSION_TIMEOUT', 3600);       // 1 hour idle
define('SESSION_NAME', 'PHARMA_SESSID');

// ---------- Upload limits ----------
define('UPLOAD_MAX_BYTES', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_MIME', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg','image/png','application/zip','application/x-zip-compressed'
]);

// ---------- Timezone ----------
date_default_timezone_set(TIMEZONE);

// ---------- Error reporting ----------
if (APP_ENV === 'production') {
    ini_set('display_errors','0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
} else {
    ini_set('display_errors','1');
    error_reporting(E_ALL);
}

// ---------- Session hardening ----------
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
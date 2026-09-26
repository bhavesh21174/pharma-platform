<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
if (isLoggedIn()) {
    require_once __DIR__ . '/includes/audit-log.php';
    createAuditLog('logout', 'auth');
}
session_unset();
session_destroy();
redirect(SITE_URL . '/login.php');
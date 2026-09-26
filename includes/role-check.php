<?php
require_once __DIR__ . '/functions.php';

function requireRole(string ...$types): void {
    requireLogin();
    $u = currentUser();
    if (!$u || !in_array($u['user_type'], $types, true)) {
        http_response_code(403);
        exit('403 — Access denied.');
    }
}
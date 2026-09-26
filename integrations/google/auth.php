<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('settings.edit');
header('Content-Type: application/json');
try {
    $token = google_access_token();
    echo json_encode(['ok'=>true, 'token_preview'=>substr($token,0,12).'...']);
} catch (Throwable $e) {
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
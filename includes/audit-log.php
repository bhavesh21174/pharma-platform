<?php
require_once __DIR__ . '/functions.php';

function createAuditLog(string $action, string $module, $recordId = null, $old = null, $new = null): void {
    db_insert('audit_logs', [
        'user_id'    => $_SESSION['user_id'] ?? null,
        'action'     => $action,
        'module'     => $module,
        'record_id'  => $recordId !== null ? (string)$recordId : null,
        'old_data'   => $old !== null ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
        'new_data'   => $new !== null ? json_encode($new, JSON_UNESCAPED_UNICODE) : null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
    ]);
}
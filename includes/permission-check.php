<?php
require_once __DIR__ . '/functions.php';

function userPermissions(int $userId): array {
    static $cache = [];
    if (isset($cache[$userId])) return $cache[$userId];

    // role perms + explicit user grants - explicit user denies
    $granted = db_all(
      'SELECT DISTINCT p.code
         FROM permissions p
         JOIN role_permissions rp ON rp.permission_id = p.id
         JOIN user_roles ur ON ur.role_id = rp.role_id
        WHERE ur.user_id = ?
        UNION
       SELECT p.code
         FROM permissions p
         JOIN user_permissions up ON up.permission_id = p.id
        WHERE up.user_id = ? AND up.allowed = 1', [$userId, $userId]);

    $denied = db_all(
      'SELECT p.code FROM permissions p
         JOIN user_permissions up ON up.permission_id = p.id
        WHERE up.user_id = ? AND up.allowed = 0', [$userId]);

    $permissions = array_column($granted, 'code');
    $permissions = array_diff($permissions, array_column($denied, 'code'));

    return $cache[$userId] = array_values($permissions);
}

function hasPermission(string $code): bool {
    if (!isLoggedIn()) return false;
    $u = currentUser();
    if ($u && $u['user_type'] === USER_SUPER_ADMIN) return true;
    return in_array($code, userPermissions((int)$_SESSION['user_id']), true);
}

function requirePermission(string $code): void {
    requireLogin();
    if (!hasPermission($code)) {
        http_response_code(403);
        exit('403 — You do not have permission: ' . e($code));
    }
}
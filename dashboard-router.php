<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();
switch ($_SESSION['user_type']) {
    case USER_SUPER_ADMIN:
    case USER_ADMIN:
    case USER_COURSE_MGR:
    case USER_HR:
        redirect(SITE_URL . '/admin/dashboard.php');
    case USER_FINANCE:
        redirect(SITE_URL . '/finance/dashboard.php');
    case USER_MENTOR:
        redirect(SITE_URL . '/mentor/dashboard.php');
    case USER_STUDENT:
        redirect(SITE_URL . '/student/dashboard.php');
}
http_response_code(403); exit('Unknown role.');
<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requireRole(USER_STUDENT);
http_response_code(410);
exit('Session booking no longer uses a separate payment. Choose an assigned mentor and book an available session from My Mentor.');

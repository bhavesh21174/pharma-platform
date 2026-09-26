<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/certificate.php';

$enroll = db_all("SELECT student_id, course_id FROM enrollments WHERE status='active'");
$n = 0;
foreach ($enroll as $e) {
    if (issue_certificate_if_eligible((int)$e['student_id'], (int)$e['course_id'])) $n++;
}
echo "certificate-check done. Issued: $n\n";
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Assessments starting in next 24h
$a = db_all("SELECT a.*, c.name AS course_name FROM assessments a
             JOIN courses c ON c.id=a.course_id
             WHERE a.status='published' AND a.start_at BETWEEN NOW() AND NOW() + INTERVAL 1 DAY");
foreach ($a as $asm) {
    $students = db_all("SELECT s.user_id FROM students s JOIN enrollments e ON e.student_id=s.id
                        WHERE e.course_id=? AND e.status='active'",[$asm['course_id']]);
    foreach ($students as $st) {
        createNotification((int)$st['user_id'],'Assessment starts soon',
            $asm['title'].' — '.$asm['course_name'], SITE_URL.'/admin/students/assessments.php');
    }
}
echo "assessment-reminders done\n";
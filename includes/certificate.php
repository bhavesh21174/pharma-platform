<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/performance.php';

/** Generate a certificate if completion rules are met and one doesn't already exist. */
function issue_certificate_if_eligible(int $studentId, int $courseId): ?array {
    if (!student_meets_completion($studentId, $courseId)) return null;

    $existing = db_one('SELECT * FROM certificates WHERE student_id=? AND course_id=?', [$studentId, $courseId]);
    if ($existing) return $existing;

    $student = db_one('SELECT s.*, u.full_name FROM students s JOIN users u ON u.id=s.user_id WHERE s.id=?', [$studentId]);
    $course  = db_one('SELECT name FROM courses WHERE id=?', [$courseId]);
    if (!$student || !$course) return null;

    $certId = 'PA-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));

    $id = db_insert('certificates', [
        'certificate_id'  => $certId,
        'student_id'      => $studentId,
        'course_id'       => $courseId,
        'student_name'    => $student['full_name'],
        'course_name'     => $course['name'],
        'completion_date' => date('Y-m-d'),
        'verification_url'=> SITE_URL . '/verify-certificate.php?id=' . $certId,
        'issued_by'       => $_SESSION['user_id'] ?? null,
    ]);

    // Mark enrollment completed
    db_query("UPDATE enrollments SET status='completed', completed_at=NOW() WHERE student_id=? AND course_id=? AND status='active'",
             [$studentId, $courseId]);

    createNotification((int)$student['user_id'], 'Certificate available!',
        'Your certificate for '.$course['name'].' is ready.',
        SITE_URL.'/admin/students/certificates.php');

    return db_one('SELECT * FROM certificates WHERE id=?', [$id]);
}
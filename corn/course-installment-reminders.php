<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$admin = db_one("SELECT id FROM users WHERE user_type IN ('SUPER_ADMIN','ADMIN') AND status='active' ORDER BY user_type='SUPER_ADMIN' DESC LIMIT 1");
if (!$admin) {
    exit("No active admin available for installment announcements.\n");
}

$dueSoon = db_all("SELECT e.id AS enrollment_id, e.student_id, e.course_id, e.total_fee, e.paid_amount,
                      e.second_installment_due_at, s.user_id, c.name AS course_name
                   FROM enrollments e
                   JOIN students s ON s.id=e.student_id
                   JOIN courses c ON c.id=e.course_id
                   WHERE e.status='active'
                     AND e.paid_amount < e.total_fee
                     AND e.second_installment_due_at IS NOT NULL
                     AND e.second_installment_due_at <= DATE_ADD(NOW(), INTERVAL 1 DAY)
                     AND e.second_reminder_sent_at IS NULL
                   ORDER BY e.second_installment_due_at");

foreach ($dueSoon as $enrollment) {
    $dueDate = date('d M Y', strtotime($enrollment['second_installment_due_at']));
    $remaining = max(0, (float)$enrollment['total_fee'] - (float)$enrollment['paid_amount']);
    $message = 'Your remaining course installment of ' . formatCurrency($remaining)
        . ' for ' . $enrollment['course_name'] . ' is due on ' . $dueDate . '.';

    db()->beginTransaction();
    try {
        createNotification((int)$enrollment['user_id'], 'Course installment due soon', $message, SITE_URL . '/student/payments.php');
        db_insert('announcements', [
            'title' => 'Course installment reminder',
            'description' => $message . ' Please open Payments in your student panel to complete it.',
            'audience' => 'student',
            'course_id' => (int)$enrollment['course_id'],
            'student_id' => (int)$enrollment['student_id'],
            'created_by' => (int)$admin['id'],
        ]);
        db_update('enrollments', ['second_reminder_sent_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $enrollment['enrollment_id']]);
        db()->commit();
        echo 'Reminder sent for enrollment ' . (int)$enrollment['enrollment_id'] . "\n";
    } catch (Throwable $exception) {
        if (db()->inTransaction()) db()->rollBack();
        error_log('Course installment reminder: ' . $exception->getMessage());
    }
}

echo 'Course installment reminder job complete. ' . count($dueSoon) . " due enrollment(s) checked.\n";
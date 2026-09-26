<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requireRole(USER_STUDENT);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
verifyCsrf();

$courseId = (int)($_POST['course_id'] ?? 0);
$mentorId = (int)($_POST['mentor_id'] ?? 0);
$availabilityId = (int)($_POST['avail_id'] ?? 0);
$student = db_one('SELECT * FROM students WHERE user_id=?', [$_SESSION['user_id']]);
if (!$student) exit('Student profile missing.');

$enrollment = db_one("SELECT e.id FROM enrollments e
                       JOIN mentor_assignments ma ON ma.student_id=e.student_id AND ma.course_id=e.course_id AND ma.mentor_id=e.mentor_id AND ma.status='active'
                       WHERE e.student_id=? AND e.course_id=? AND e.mentor_id=? AND e.status='active'", [$student['id'], $courseId, $mentorId]);
if (!$enrollment) { http_response_code(403); exit('This mentor is not assigned to your active course.'); }

try {
    db()->beginTransaction();
    $slot = db_query('SELECT * FROM mentor_availability WHERE id=? AND mentor_id=? AND is_blocked=0 FOR UPDATE', [$availabilityId, $mentorId])->fetch();
    if (!$slot || strtotime($slot['avail_date'] . ' ' . $slot['start_time']) <= time()) {
        throw new RuntimeException('This availability has expired. Choose another time.');
    }

    $conflict = db_one("SELECT id FROM meetings
                        WHERE mentor_id=? AND meeting_date=? AND status IN ('scheduled','rescheduled')
                          AND NOT (end_time <= ? OR start_time >= ?)
                        LIMIT 1 FOR UPDATE", [$mentorId, $slot['avail_date'], $slot['start_time'], $slot['end_time']]);
    if ($conflict) throw new RuntimeException('That time was just booked. Choose another slot.');

    $eventId = null;
    $meetUrl = null;
    try {
        require_once __DIR__ . '/../../integrations/google/calendar.php';
        [$eventId, $meetUrl] = google_create_meeting(
            $mentorId,
            (int)$student['id'],
            $courseId,
            $slot['avail_date'],
            $slot['start_time'],
            $slot['end_time']
        );
    } catch (Throwable $exception) {
        error_log('Google Meet create failed: ' . $exception->getMessage());
    }

    $meetingId = db_insert('meetings', [
        'student_id' => (int)$student['id'],
        'mentor_id' => $mentorId,
        'course_id' => $courseId,
        'meeting_date' => $slot['avail_date'],
        'start_time' => $slot['start_time'],
        'end_time' => $slot['end_time'],
        'gcal_event_id' => $eventId,
        'meet_url' => $meetUrl,
        'status' => 'scheduled',
    ]);

    $course = db_one('SELECT name FROM courses WHERE id=?', [$courseId]);
    $mentorUser = db_one('SELECT user_id FROM mentors WHERE id=?', [$mentorId]);
    createNotification((int)$_SESSION['user_id'], 'Session booked',
        'Your one-to-one session for ' . $course['name'] . ' is booked on ' . formatDate($slot['avail_date']) . ' at ' . substr($slot['start_time'], 0, 5) . '.',
        SITE_URL . '/student/meetings.php');
    if ($mentorUser) {
        createNotification((int)$mentorUser['user_id'], 'New one-to-one session',
            $student['full_name'] . ' booked a session for ' . $course['name'] . '.', SITE_URL . '/mentor/sessions.php');
    }
    createAuditLog('meeting.booked', 'meeting', $meetingId, null, ['course_id' => $courseId, 'mentor_id' => $mentorId]);
    db()->commit();
    setFlash('success', $meetUrl ? 'Session booked. Your Google Meet link is ready.' : 'Session booked. Meeting link will appear after calendar setup.');
    redirect(SITE_URL . '/student/meetings.php');
} catch (Throwable $exception) {
    if (db()->inTransaction()) db()->rollBack();
    setFlash('warning', $exception->getMessage());
    redirect(SITE_URL . '/admin/students/mentor-availability.php?course_id=' . $courseId . '&mentor_id=' . $mentorId);
}

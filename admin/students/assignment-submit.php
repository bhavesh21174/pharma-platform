<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requireRole(USER_STUDENT);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('assignments.php'); }
verifyCsrf();

$student = db_one('SELECT * FROM students WHERE user_id=?',[$_SESSION['user_id']]);
$assignment_id = (int)$_POST['assignment_id'];
$a = db_one('SELECT * FROM assignments WHERE id=?',[$assignment_id]);
if (!$a || !$student) exit('Invalid');

$enrolled = db_one("SELECT id FROM enrollments WHERE student_id=? AND course_id=? AND status='active'",
                    [$student['id'], $a['course_id']]);
if (!$enrolled) { http_response_code(403); exit('Not enrolled.'); }

$data = [
    'assignment_id'=>$assignment_id,
    'student_id'=>$student['id'],
    'text_response'=>$_POST['text_response'] ?? '',
    'status'=> (strtotime($a['due_date']) < time()) ? 'late' : 'submitted',
];

if (!empty($_FILES['submission_file']['name'])) {
    try { $data['file_path'] = upload_file($_FILES['submission_file'], 'assignments'); }
    catch (Throwable $e) { setFlash('danger', $e->getMessage()); redirect('assignment-view.php?id='.$assignment_id); }
}

// Upsert: if resubmission, delete old & insert new (keep history would need a separate table)
$existing = db_one('SELECT id FROM assignment_submissions WHERE assignment_id=? AND student_id=?', [$assignment_id, $student['id']]);
if ($existing) {
    db_update('assignment_submissions', $data, 'id = :id', ['id'=>$existing['id']]);
    $sid = (int)$existing['id'];
} else {
    $sid = db_insert('assignment_submissions', $data);
}

// Notify mentor
$ma = db_one("SELECT mentor_id FROM mentor_assignments WHERE student_id=? AND course_id=? AND status='active'",
             [$student['id'], $a['course_id']]);
if ($ma) {
    $mu = db_one('SELECT user_id FROM mentors WHERE id=?',[(int)$ma['mentor_id']]);
    if ($mu) createNotification((int)$mu['user_id'], 'New submission',
        'Assignment submitted: '.$a['title'], SITE_URL.'/admin/mentors/assignments.php');
}

createAuditLog('assignment.submit','assignment',$sid);
setFlash('success','Submission saved.');
redirect('assignment-view.php?id='.$assignment_id);
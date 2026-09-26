<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('student.edit');

$errors = [];
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
    $mentorId = (int)($_POST['mentor_id'] ?? 0);
    $request = db_one("SELECT e.*, c.name AS course_name, c.duration_days, s.user_id AS student_user_id, su.full_name AS student_name
                       FROM enrollments e
                       JOIN courses c ON c.id=e.course_id
                       JOIN students s ON s.id=e.student_id
                       JOIN users su ON su.id=s.user_id
                       WHERE e.id=? AND e.status='pending_admin'", [$enrollmentId]);
    $mentor = db_one("SELECT m.*, u.full_name FROM mentors m JOIN users u ON u.id=m.user_id WHERE m.id=? AND m.status='active' AND u.status='active'", [$mentorId]);
    if (!$request || !$mentor) {
        $errors[] = 'The enrollment request or selected mentor is no longer available.';
    } else {
        $assignedCount = (int)db_one("SELECT COUNT(*) AS c FROM mentor_assignments WHERE mentor_id=? AND status='active'", [$mentorId])['c'];
        if ($assignedCount >= (int)$mentor['max_students']) {
            $errors[] = 'Selected mentor has reached their student limit.';
        } else {
            $existingAssignment = db_one("SELECT id FROM mentor_assignments WHERE student_id=? AND course_id=? AND status='active'", [$request['student_id'], $request['course_id']]);
            if ($existingAssignment) {
                $errors[] = 'This student already has an active mentor assignment for the course.';
            } else {
                $midpointDays = max(1, (int)floor((int)$request['duration_days'] / 2));
                $courseStart = $request['enrolled_at'] ? strtotime($request['enrolled_at']) : time();
                $dueAt = date('Y-m-d H:i:s', $courseStart + ($midpointDays * 86400));
                db()->beginTransaction();
                try {
                    db_update('enrollments', [
                        'status' => 'active',
                        'mentor_id' => $mentorId,
                        'second_installment_due_at' => $dueAt,
                        'second_reminder_sent_at' => null,
                    ], 'id = :id', ['id' => $enrollmentId]);
                    db_insert('mentor_assignments', [
                        'mentor_id' => $mentorId,
                        'student_id' => (int)$request['student_id'],
                        'course_id' => (int)$request['course_id'],
                        'assigned_by' => (int)$_SESSION['user_id'],
                        'status' => 'active',
                    ]);
                    createNotification((int)$request['student_user_id'], 'Mentor assigned',
                        $mentor['full_name'] . ' has been assigned for ' . $request['course_name'] . '. Book your first session from My Mentor.',
                        SITE_URL . '/student/my-mentor.php?course_id=' . (int)$request['course_id']);
                    createNotification((int)$mentor['user_id'], 'New student assigned',
                        $request['student_name'] . ' has been assigned for ' . $request['course_name'] . '.',
                        SITE_URL . '/mentor/my-students.php');
                    db_insert('announcements', [
                        'title' => 'Your mentor is confirmed',
                        'description' => $mentor['full_name'] . ' has been assigned to your course. You can now choose an available one-to-one session time from My Mentor. Your remaining course balance is due on ' . date('d M Y', strtotime($dueAt)) . '.',
                        'audience' => 'student',
                        'course_id' => (int)$request['course_id'],
                        'student_id' => (int)$request['student_id'],
                        'created_by' => (int)$_SESSION['user_id'],
                    ]);
                    db()->commit();
                    createAuditLog('enrollment.mentor_allocated', 'enrollment', $enrollmentId, null, ['mentor_id' => $mentorId]);
                    setFlash('success', 'Mentor allocated and enrollment activated.');
                    redirect(SITE_URL . '/admin/enrollments/requests.php');
                } catch (Throwable $exception) {
                    if (db()->inTransaction()) db()->rollBack();
                    error_log('Mentor allocation: ' . $exception->getMessage());
                    $errors[] = 'Could not allocate this mentor. Please try again.';
                }
            }
        }
    }
}

$requests = db_all("SELECT e.id, e.student_id, e.course_id, e.requested_mentor_id, e.total_fee, e.paid_amount, e.enrolled_at,
                      c.name AS course_name, c.duration_days,
                      s.user_id AS student_user_id, su.full_name AS student_name, su.email AS student_email,
                      requested.full_name AS requested_mentor_name
                    FROM enrollments e
                    JOIN courses c ON c.id=e.course_id
                    JOIN students s ON s.id=e.student_id
                    JOIN users su ON su.id=s.user_id
                    LEFT JOIN mentors rm ON rm.id=e.requested_mentor_id
                    LEFT JOIN users requested ON requested.id=rm.user_id
                    WHERE e.status='pending_admin'
                    ORDER BY e.enrolled_at ASC");
$mentors = db_all("SELECT m.id, m.max_students, u.full_name,
                     (SELECT COUNT(*) FROM mentor_assignments ma WHERE ma.mentor_id=m.id AND ma.status='active') AS assigned_count
                   FROM mentors m JOIN users u ON u.id=m.user_id
                   WHERE m.status='active' AND u.status='active'
                   ORDER BY u.full_name");

$pageTitle = 'Enrollment Requests';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Enrollment Requests</h4>
  <?php if ($errors): ?><div class="alert alert-danger small"><?php foreach ($errors as $error) echo e($error) . '<br>'; ?></div><?php endif; ?>
  <?php if (!$requests): ?>
    <div class="card"><div class="card-body text-muted">No paid course requests are waiting for mentor allocation.</div></div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($requests as $request): ?>
        <div class="col-xl-6">
          <section class="card h-100"><div class="card-body">
            <div class="d-flex justify-content-between gap-3 mb-3">
              <div><h5 class="fw-bold mb-1"><?= e($request['student_name']) ?></h5><div class="small text-muted"><?= e($request['student_email']) ?></div></div>
              <span class="badge bg-warning text-dark align-self-start">Deposit received</span>
            </div>
            <dl class="row small mb-3">
              <dt class="col-5">Course</dt><dd class="col-7"><?= e($request['course_name']) ?> · <?= (int)$request['duration_days'] ?> days</dd>
              <dt class="col-5">Paid so far</dt><dd class="col-7"><?= formatCurrency((float)$request['paid_amount']) ?> / <?= formatCurrency((float)$request['total_fee']) ?></dd>
              <dt class="col-5">Student preference</dt><dd class="col-7"><?= e($request['requested_mentor_name'] ?? 'No preference') ?></dd>
            </dl>
            <form method="post" class="d-flex flex-wrap gap-2">
              <?= csrfField() ?>
              <input type="hidden" name="enrollment_id" value="<?= (int)$request['id'] ?>">
              <select name="mentor_id" class="form-select flex-grow-1" required aria-label="Assign mentor">
                <option value="">Choose available mentor</option>
                <?php foreach ($mentors as $mentor): $full = (int)$mentor['assigned_count'] >= (int)$mentor['max_students']; ?>
                  <option value="<?= (int)$mentor['id'] ?>" <?= (int)$mentor['id'] === (int)$request['requested_mentor_id'] ? 'selected' : '' ?> <?= $full ? 'disabled' : '' ?>><?= e($mentor['full_name']) ?><?= $full ? ' (full)' : ' (' . ((int)$mentor['max_students'] - (int)$mentor['assigned_count']) . ' spaces)' ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-primary">Allocate mentor</button>
            </form>
          </div></section>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
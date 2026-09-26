<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requireRole(USER_STUDENT);
$course_id = (int)($_GET['course_id'] ?? 0);
$course = db_one("SELECT * FROM courses WHERE id=? AND status='published'",[$course_id]);
if (!$course) { http_response_code(404); exit('Course not found'); }

$student = db_one('SELECT * FROM students WHERE user_id=?',[$_SESSION['user_id']]);
if (!$student) exit('Student profile missing');
$existingEnrollment = db_one('SELECT status FROM enrollments WHERE student_id=? AND course_id=?', [$student['id'], $course_id]);
if ($existingEnrollment && in_array($existingEnrollment['status'], ['pending_admin', 'active', 'completed'], true)) {
  setFlash('info', 'This course already has an enrollment request or active enrollment.');
  redirect(SITE_URL . '/student/my-courses.php');
}

$mentors = db_all("SELECT m.*, u.full_name,u.profile_photo,
                     (SELECT COUNT(*) FROM mentor_assignments ma WHERE ma.mentor_id=m.id AND ma.status='active') AS students
                   FROM mentors m JOIN users u ON u.id=m.user_id
                   WHERE m.status='active' AND u.status='active'
                   HAVING students < m.max_students
                   ORDER BY m.experience_years DESC");

$pageTitle='Select Mentor';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-1">Choose a Mentor</h4>
  <p class="text-muted small">Course: <strong><?= e($course['name']) ?></strong> · <?= (int)$course['duration_days'] ?> days · Pay 50% now, with the balance due halfway through the course. An administrator will confirm your mentor request.</p>

  <?php if (!$mentors): ?>
    <div class="alert alert-warning">No mentors available right now.</div>
  <?php else: ?>
  <div class="row g-3">
    <?php foreach ($mentors as $m): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body d-flex flex-column">
            <div class="d-flex gap-3 align-items-center mb-3">
              <?php if ($m['profile_photo']): ?>
                <img src="<?= UPLOAD_URL.'/'.e($m['profile_photo']) ?>" class="mentor-avatar-image" width="56" height="56" alt="">
              <?php else: ?>
                <div class="mentor-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr(trim($m['full_name']), 0, 1))) ?></div>
              <?php endif; ?>
              <div>
                <div class="fw-semibold"><?= e($m['full_name']) ?></div>
                <div class="small text-muted"><?= e($m['specialization']) ?></div>
              </div>
            </div>
            <div class="small text-muted mb-2"><?= e($m['qualification']) ?> · <?= (int)$m['experience_years'] ?> yrs</div>
            <div class="small mb-3"><span class="badge bg-info text-dark"><?= (int)$m['students'] ?>/<?= (int)$m['max_students'] ?> students</span></div>
            <form method="post" action="<?= SITE_URL ?>/integrations/razorpay/create-course-order.php" class="mt-auto">
              <?= csrfField() ?>
              <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
              <input type="hidden" name="mentor_id" value="<?= (int)$m['id'] ?>">
              <button class="btn btn-primary w-100">Request mentor · Pay <?= formatCurrency(round((float)($course['discount_price'] ?? $course['price']) / 2, 2)) ?></button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../includes/auth-check.php';
requireRole(USER_STUDENT);
$student = db_one('SELECT id FROM students WHERE user_id=?', [$_SESSION['user_id']]);
if (!$student) exit('Student profile missing.');

$courseId = (int)($_GET['course_id'] ?? 0);
$courses = db_all("SELECT e.course_id, e.status AS enrollment_status, e.requested_mentor_id,
                     c.name AS course_name,
                     COALESCE(ma.mentor_id, e.mentor_id) AS mentor_id,
                     u.full_name AS mentor_name, m.specialization, m.experience_years
                   FROM enrollments e
                   JOIN courses c ON c.id=e.course_id
                   LEFT JOIN mentor_assignments ma ON ma.student_id=e.student_id AND ma.course_id=e.course_id AND ma.status='active'
                   LEFT JOIN mentors m ON m.id=COALESCE(ma.mentor_id, e.mentor_id)
                   LEFT JOIN users u ON u.id=m.user_id
                   WHERE e.student_id=? AND e.status IN ('active','pending_admin')
                     AND (? = 0 OR e.course_id = ?)
                   ORDER BY c.name", [$student['id'], $courseId, $courseId]);

$pageTitle = 'My Mentor';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">My Mentor</h4>
  <?php if (!$courses): ?>
    <div class="card"><div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
      <div><h5 class="fw-bold mb-1">No active courses yet</h5><p class="text-muted mb-0">Choose a course before selecting a mentor and booking a session.</p></div>
      <a class="btn btn-primary" href="<?= SITE_URL ?>/public/courses.php">Browse courses</a>
    </div></div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($courses as $course): ?>
        <div class="col-md-6 col-xl-4">
          <section class="card h-100"><div class="card-body d-flex flex-column">
            <p class="small text-uppercase fw-bold text-muted mb-2"><?= e($course['course_name']) ?></p>
            <?php if ($course['enrollment_status'] === 'pending_admin'): ?>
              <h5 class="fw-bold mb-1"><?= e($course['mentor_name'] ?? 'Mentor allocation pending') ?></h5>
              <p class="text-muted small mb-0">Your deposit is recorded. The admin team will confirm the mentor before session booking opens.</p>
            <?php elseif ($course['mentor_id']): ?>
              <h5 class="fw-bold mb-1"><?= e($course['mentor_name'] ?? 'Assigned mentor') ?></h5>
              <p class="text-muted small mb-3"><?= e($course['specialization'] ?? 'Mentor') ?><?= $course['experience_years'] !== null ? ' · ' . (int)$course['experience_years'] . ' years' : '' ?></p>
              <a class="btn btn-primary mt-auto" href="<?= SITE_URL ?>/admin/students/mentor-availability.php?course_id=<?= (int)$course['course_id'] ?>&amp;mentor_id=<?= (int)$course['mentor_id'] ?>">View availability</a>
            <?php else: ?>
              <p class="text-muted mb-3">No mentor selected for this course.</p>
              <a class="btn btn-primary mt-auto" href="<?= SITE_URL ?>/admin/students/mentor-selection.php?course_id=<?= (int)$course['course_id'] ?>">Choose a mentor</a>
            <?php endif; ?>
          </div></section>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
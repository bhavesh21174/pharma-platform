<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requireRole(USER_MENTOR);
$mentor = db_one('SELECT id FROM mentors WHERE user_id=?', [$_SESSION['user_id']]);
if (!$mentor) exit('Mentor profile missing.');

$assessments = db_all("SELECT a.id, a.title, a.status, c.name AS course_name,
                         (SELECT COUNT(*)
                            FROM assessment_attempts t
                            JOIN mentor_assignments ma ON ma.student_id=t.student_id
                              AND ma.course_id=a.course_id AND ma.mentor_id=? AND ma.status='active'
                           WHERE t.assessment_id=a.id AND t.status='submitted') AS pending
                       FROM assessments a
                       JOIN courses c ON c.id=a.course_id
                       WHERE a.course_id IN (SELECT course_id FROM mentor_assignments WHERE mentor_id=? AND status='active')
                         AND a.status IN ('published','closed')
                       ORDER BY a.created_at DESC", [$mentor['id'], $mentor['id']]);

$pageTitle = 'Assessments';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/mentor-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Assessment Reviews</h4>
  <div class="card"><div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light"><tr><th>Assessment</th><th>Course</th><th>Pending reviews</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($assessments as $assessment): ?>
        <tr>
          <td class="fw-semibold"><?= e($assessment['title']) ?></td>
          <td><?= e($assessment['course_name']) ?></td>
          <td><?= (int)$assessment['pending'] ?></td>
          <td class="text-end"><a class="btn btn-sm btn-primary" href="<?= SITE_URL ?>/admin/students/assessment-review.php?id=<?= (int)$assessment['id'] ?>">Review</a></td>
        </tr>
      <?php endforeach; if (!$assessments): ?>
        <tr><td colspan="4" class="text-center text-muted py-4">No assessments are assigned to your courses.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
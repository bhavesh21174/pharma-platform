<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requireRole(USER_STUDENT);
$student = db_one('SELECT * FROM students WHERE user_id=?', [$_SESSION['user_id']]);
$rows = db_all("SELECT a.*, c.name AS course_name,
                  (SELECT COUNT(*) FROM assessment_attempts t WHERE t.assessment_id=a.id AND t.student_id=?) AS attempts,
                  (SELECT MAX(percentage) FROM assessment_results r WHERE r.assessment_id=a.id AND r.student_id=?) AS best
                FROM assessments a JOIN courses c ON c.id=a.course_id
                JOIN enrollments e ON e.course_id=a.course_id AND e.student_id=? AND e.status='active'
                WHERE a.status='published' ORDER BY a.id DESC", [$student['id'], $student['id'], $student['id']]);
$pageTitle = 'Assessments';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Assessments</h4>
  <div class="card"><div class="table-responsive">
    <table class="table small align-middle mb-0">
      <thead class="table-light"><tr><th>Title</th><th>Course</th><th>Attempts</th><th>Best %</th><th>Limit</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['title']) ?></td>
          <td><?= e($r['course_name']) ?></td>
          <td><?= (int)$r['attempts'] ?></td>
          <td><?= $r['best'] !== null ? number_format((float)$r['best'], 1) . '%' : '—' ?></td>
          <td><?= (int)$r['attempt_limit'] ?></td>
          <td class="text-end">
            <?php if ((int)$r['attempts'] < (int)$r['attempt_limit']): ?>
              <a class="btn btn-sm btn-primary" href="assessment-attempt.php?id=<?= (int)$r['id'] ?>">Start</a>
            <?php else: ?>
              <a class="btn btn-sm btn-outline-secondary" href="results.php?id=<?= (int)$r['id'] ?>">View Result</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; if (!$rows): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No published assessments yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
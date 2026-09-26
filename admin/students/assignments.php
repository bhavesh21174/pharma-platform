<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requireRole(USER_STUDENT);
$student = db_one('SELECT * FROM students WHERE user_id=?',[$_SESSION['user_id']]);

$rows = db_all("SELECT a.*, c.name AS course_name,
                  s.id AS submission_id, s.status AS sub_status, s.marks, s.feedback
                FROM assignments a
                JOIN courses c ON c.id=a.course_id
                JOIN enrollments e ON e.course_id=a.course_id AND e.student_id=? AND e.status='active'
                LEFT JOIN assignment_submissions s ON s.assignment_id=a.id AND s.student_id=?
                WHERE a.status IN ('published','closed')
                ORDER BY a.due_date DESC", [$student['id'], $student['id']]);

$pageTitle='Assignments';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">My Assignments</h4>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table align-middle small mb-0">
      <thead class="table-light"><tr><th>Title</th><th>Course</th><th>Due</th><th>Max</th><th>Status</th><th>Marks</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['title']) ?></td>
          <td><?= e($r['course_name']) ?></td>
          <td><?= formatDate($r['due_date'],'d M Y H:i') ?></td>
          <td><?= (int)$r['max_marks'] ?></td>
          <td>
            <?php if (!$r['submission_id']): ?>
              <span class="badge bg-warning text-dark">Pending</span>
            <?php else: ?>
              <span class="badge bg-<?= ['submitted'=>'info','checked'=>'success','resubmit'=>'warning','late'=>'danger'][$r['sub_status']] ?? 'secondary' ?>"><?= e($r['sub_status']) ?></span>
            <?php endif; ?>
          </td>
          <td><?= $r['marks'] !== null ? e($r['marks']) : '—' ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-primary" href="assignment-view.php?id=<?= (int)$r['id'] ?>">Open</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>

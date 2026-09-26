<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requireRole(USER_STUDENT);
$student = db_one('SELECT * FROM students WHERE user_id=?',[$_SESSION['user_id']]);
$rows = db_all("SELECT a.*, c.name AS course_name FROM attendance a JOIN courses c ON c.id=a.course_id
                WHERE a.student_id=? ORDER BY a.att_date DESC",[$student['id']]);
$pct = db_one("SELECT ROUND(100 * SUM(status IN ('present','late','excused')) / NULLIF(COUNT(*),0), 2) p
               FROM attendance WHERE student_id=?",[$student['id']])['p'] ?? 0;
$pageTitle='Attendance';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">My Attendance</h4>
  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="stat-card"><div class="label">Overall %</div><div class="value"><?= number_format((float)$pct,1) ?>%</div></div></div>
  </div>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table small align-middle mb-0">
      <thead class="table-light"><tr><th>Date</th><th>Course</th><th>Time</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= formatDate($r['att_date']) ?></td>
          <td><?= e($r['course_name']) ?></td>
          <td><?= e(substr($r['start_time'],0,5)) ?>–<?= e(substr($r['end_time'],0,5)) ?></td>
          <td><span class="badge bg-<?= ['present'=>'success','absent'=>'danger','late'=>'warning','excused'=>'info'][$r['status']] ?? 'secondary' ?>"><?= e($r['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
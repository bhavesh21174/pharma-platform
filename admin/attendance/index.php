<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('attendance.view');
$rows = db_all("SELECT a.*, u.full_name AS student_name, c.name AS course_name, mu.full_name AS mentor_name
                FROM attendance a
                JOIN students s ON s.id=a.student_id JOIN users u ON u.id=s.user_id
                JOIN courses c ON c.id=a.course_id
                JOIN mentors m ON m.id=a.mentor_id JOIN users mu ON mu.id=m.user_id
                ORDER BY a.att_date DESC LIMIT 200");
$pageTitle='Attendance';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">All Attendance</h4>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table small align-middle mb-0">
      <thead class="table-light"><tr><th>Date</th><th>Student</th><th>Mentor</th><th>Course</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= formatDate($r['att_date']) ?></td>
          <td><?= e($r['student_name']) ?></td>
          <td><?= e($r['mentor_name']) ?></td>
          <td><?= e($r['course_name']) ?></td>
          <td><span class="badge bg-<?= ['present'=>'success','absent'=>'danger','late'=>'warning','excused'=>'info'][$r['status']] ?? 'secondary' ?>"><?= e($r['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
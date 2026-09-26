<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('attendance.view');
$rows = db_all("SELECT f.*, u.full_name AS student_name, c.name AS course_name
                FROM feedback f
                JOIN students s ON s.id=f.student_id JOIN users u ON u.id=s.user_id
                LEFT JOIN courses c ON c.id=f.course_id
                ORDER BY f.id DESC LIMIT 200");
$avg = db_one('SELECT ROUND(AVG(rating),2) a FROM feedback')['a'] ?? 0;
$pageTitle='Feedback';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Feedback</h4>
  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="stat-card"><div class="label">Average Rating</div><div class="value"><?= $avg ?> / 5</div></div></div>
  </div>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table small align-middle mb-0">
      <thead class="table-light"><tr><th>Date</th><th>Student</th><th>Type</th><th>Rating</th><th>Comments</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= formatDate($r['created_at']) ?></td>
          <td><?= e($r['student_name']) ?></td>
          <td><?= e($r['type']) ?></td>
          <td><?= str_repeat('★',(int)$r['rating']) ?></td>
          <td><?= e($r['comments']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
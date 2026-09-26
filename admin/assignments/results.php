<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('assessment.review');
$id = (int)($_GET['id'] ?? 0);
$a = db_one('SELECT * FROM assessments WHERE id=?',[$id]);
if (!$a) exit('Not found');
$rows = db_all("SELECT r.*, u.full_name AS student_name FROM assessment_results r
                JOIN students s ON s.id=r.student_id JOIN users u ON u.id=s.user_id
                WHERE r.assessment_id=? ORDER BY r.percentage DESC",[$id]);
$pageTitle='Results';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Results — <?= e($a['title']) ?></h4>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table small align-middle mb-0">
      <thead class="table-light"><tr><th>Student</th><th>Score</th><th>Total</th><th>%</th><th>Result</th><th>Date</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['student_name']) ?></td>
          <td><?= e($r['score']) ?></td>
          <td><?= (int)$r['total'] ?></td>
          <td><?= number_format((float)$r['percentage'],1) ?>%</td>
          <td><span class="badge bg-<?= $r['result']==='pass'?'success':'danger' ?>"><?= e($r['result']) ?></span></td>
          <td><?= formatDate($r['published_at'],'d M Y H:i') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>

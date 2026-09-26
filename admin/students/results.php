<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requireRole(USER_STUDENT);
$student = db_one('SELECT * FROM students WHERE user_id=?',[$_SESSION['user_id']]);
$id = (int)($_GET['id'] ?? 0);
$a = db_one('SELECT * FROM assessments WHERE id=?',[$id]);
$r = db_one('SELECT * FROM assessment_results WHERE assessment_id=? AND student_id=? ORDER BY id DESC LIMIT 1',
             [$id, $student['id']]);
$pageTitle='Result';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3"><?= e($a['title']) ?> — Result</h4>
  <?php if (!$r): ?>
    <div class="alert alert-info">Awaiting manual grading.</div>
  <?php else: ?>
    <div class="row g-3">
      <div class="col-md-3"><div class="stat-card"><div class="label">Score</div><div class="value"><?= e($r['score']) ?>/<?= (int)$r['total'] ?></div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="label">Percent</div><div class="value"><?= number_format((float)$r['percentage'],1) ?>%</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="label">Result</div><div class="value"><?= e($r['result']) ?></div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="label">Date</div><div class="value small"><?= formatDate($r['published_at']) ?></div></div></div>
    </div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
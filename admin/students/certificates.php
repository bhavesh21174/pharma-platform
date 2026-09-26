<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/certificate.php';
requireRole(USER_STUDENT);
$student = db_one('SELECT * FROM students WHERE user_id=?',[$_SESSION['user_id']]);

// Auto-issue where eligible
$enroll = db_all("SELECT course_id FROM enrollments WHERE student_id=? AND status IN ('active','completed')",[$student['id']]);
foreach ($enroll as $e) issue_certificate_if_eligible((int)$student['id'], (int)$e['course_id']);

$rows = db_all('SELECT * FROM certificates WHERE student_id=? ORDER BY issued_at DESC',[$student['id']]);
$pageTitle='Certificates';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">My Certificates</h4>
  <?php if (!$rows): ?><div class="alert alert-info">No certificates yet. Complete your course to receive one.</div>
  <?php else: foreach ($rows as $c): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-3"><div class="card-body">
      <h6 class="fw-bold"><?= e($c['course_name']) ?></h6>
      <div class="small text-muted">Certificate ID: <?= e($c['certificate_id']) ?> · Issued <?= formatDate($c['completion_date']) ?></div>
      <a class="btn btn-sm btn-primary mt-2" target="_blank" href="<?= e($c['verification_url']) ?>">View / Verify</a>
    </div></div>
  <?php endforeach; endif; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requireRole(USER_STUDENT);
$student = db_one('SELECT * FROM students WHERE user_id=?',[$_SESSION['user_id']]);
$rows = db_all("SELECT a.* FROM announcements a
                LEFT JOIN enrollments e ON e.course_id=a.course_id AND e.student_id=? AND e.status='active'
                WHERE a.status='published'
                  AND (a.expire_at IS NULL OR a.expire_at > NOW())
                  AND (
                    a.audience IN ('all','students')
                    OR (a.audience='course' AND e.id IS NOT NULL)
                    OR (a.audience='student' AND a.student_id=?)
                  )
                ORDER BY a.publish_at DESC",[$student['id'],$student['id']]);
$pageTitle='Announcements';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Announcements</h4>
  <?php if (!$rows): ?><div class="alert alert-info">No announcements.</div>
  <?php else: foreach ($rows as $r): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-3"><div class="card-body">
      <div class="small text-muted"><?= formatDate($r['publish_at'],'d M Y H:i') ?></div>
      <h6 class="fw-bold"><?= e($r['title']) ?></h6>
      <p class="mb-0"><?= nl2br(e($r['description'])) ?></p>
      <?php if ($r['attachment']): ?>
        <a class="btn btn-sm btn-outline-primary mt-2" href="<?= UPLOAD_URL.'/'.$r['attachment'] ?>" target="_blank">Attachment</a>
      <?php endif; ?>
    </div></div>
  <?php endforeach; endif; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requireRole(USER_MENTOR);
$rows = db_all("SELECT * FROM announcements WHERE status='published'
                AND (expire_at IS NULL OR expire_at > NOW())
                AND audience IN ('all','mentors')
                ORDER BY publish_at DESC");
$pageTitle='Announcements';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/mentor-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Announcements</h4>
  <?php foreach ($rows as $r): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-3"><div class="card-body">
      <div class="small text-muted"><?= formatDate($r['publish_at'],'d M Y H:i') ?></div>
      <h6 class="fw-bold"><?= e($r['title']) ?></h6>
      <p class="mb-0"><?= nl2br(e($r['description'])) ?></p>
    </div></div>
  <?php endforeach; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
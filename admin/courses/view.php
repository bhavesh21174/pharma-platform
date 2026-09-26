<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('course.view');
$id = (int)($_GET['id'] ?? 0);
$c  = db_one('SELECT * FROM courses WHERE id=?', [$id]);
if (!$c) { http_response_code(404); exit('Not found'); }
$mods = db_all('SELECT m.*, (SELECT COUNT(*) FROM lessons l WHERE l.module_id=m.id) AS lesson_count
                FROM course_modules m WHERE m.course_id=? ORDER BY m.position', [$id]);
$enrolled = (int) db_one("SELECT COUNT(*) c FROM enrollments WHERE course_id=? AND status='active'", [$id])['c'];
$revenue  = (float) db_one("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE course_id=? AND status='successful'", [$id])['s'];

$pageTitle = $c['name'];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <nav><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="index.php">Courses</a></li><li class="breadcrumb-item active"><?= e($c['name']) ?></li></ol></nav>
      <h4 class="fw-bold mb-0"><?= e($c['name']) ?> <span class="badge bg-light text-dark"><?= e($c['course_code']) ?></span></h4>
    </div>
    <?php if (hasPermission('course.edit')): ?><a class="btn btn-outline-secondary" href="edit.php?id=<?= (int)$id ?>">Edit</a><?php endif; ?>
  </div>

  <div class="row g-3">
    <div class="col-6 col-md-3"><div class="stat-card"><div class="label">Enrolled</div><div class="value"><?= $enrolled ?></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="label">Revenue</div><div class="value"><?= formatCurrency($revenue) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="label">Modules</div><div class="value"><?= count($mods) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="label">Status</div><div class="value"><?= e($c['status']) ?></div></div></div>
  </div>

  <div class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Modules</h6>
        <?php if (hasPermission('course.edit')): ?>
          <a class="btn btn-sm btn-primary" href="modules.php?course_id=<?= (int)$id ?>">Manage Modules</a>
        <?php endif; ?>
      </div>
      <?php if (!$mods): ?><div class="text-muted small">No modules yet.</div>
      <?php else: ?>
      <ul class="list-group list-group-flush">
        <?php foreach ($mods as $m): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center px-0">
            <div><strong><?= e($m['title']) ?></strong> <span class="text-muted small">— <?= (int)$m['lesson_count'] ?> lessons</span></div>
            <?php if (hasPermission('course.edit')): ?><a class="btn btn-sm btn-outline-primary" href="lessons.php?module_id=<?= (int)$m['id'] ?>">Lessons</a><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
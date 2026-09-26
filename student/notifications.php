<?php
require_once __DIR__ . '/../includes/auth-check.php';
requireRole(USER_STUDENT);

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    if (($_POST['action']??'')==='mark_all_read') {
        db_query('UPDATE notifications SET is_read=1 WHERE user_id=?',[$_SESSION['user_id']]);
    }
    redirect('notifications.php');
}

$rows = db_all('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 100',[$_SESSION['user_id']]);
$pageTitle='Notifications';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/student-sidebar.php';
?>
<main class="app-main">
  <div class="d-flex justify-content-between mb-3">
    <h4 class="fw-bold mb-0">Notifications</h4>
    <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="mark_all_read">
      <button class="btn btn-outline-secondary btn-sm">Mark all read</button></form>
  </div>
  <?php foreach ($rows as $r): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-2 <?= $r['is_read']?'':'border-start border-4 border-primary' ?>"><div class="card-body">
      <div class="d-flex justify-content-between">
        <div>
          <div class="fw-semibold"><?= e($r['title']) ?></div>
          <div class="small text-muted"><?= formatDate($r['created_at'],'d M Y H:i') ?></div>
        </div>
        <?php if ($r['link']): ?><a class="btn btn-sm btn-outline-primary" href="<?= e($r['link']) ?>">Open</a><?php endif; ?>
      </div>
      <div class="mt-2"><?= e($r['message']) ?></div>
    </div></div>
  <?php endforeach; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
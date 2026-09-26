<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('course.edit');
$course_id = (int)($_GET['course_id'] ?? 0);
$c = db_one('SELECT id,name FROM courses WHERE id=?', [$course_id]);
if (!$c) exit('Course not found');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (($_POST['action'] ?? '') === 'delete') {
        $mid = (int)$_POST['module_id'];
        db_query('DELETE FROM course_modules WHERE id=? AND course_id=?', [$mid,$course_id]);
        createAuditLog('module.delete','course',$mid);
        setFlash('success','Module deleted.');
    } else {
        $title = trim($_POST['title'] ?? '');
        if ($title !== '') {
            $pos = (int) db_one('SELECT COALESCE(MAX(position),0)+1 p FROM course_modules WHERE course_id=?',[$course_id])['p'];
            $mid = db_insert('course_modules', ['course_id'=>$course_id,'title'=>$title,'description'=>$_POST['description']??'','position'=>$pos]);
            createAuditLog('module.create','course',$mid);
            setFlash('success','Module added.');
        }
    }
    redirect('modules.php?course_id='.$course_id);
}
$mods = db_all('SELECT * FROM course_modules WHERE course_id=? ORDER BY position', [$course_id]);
$pageTitle = 'Modules — '.$c['name'];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Modules — <?= e($c['name']) ?></h4>
  <div class="row g-3">
    <div class="col-md-5">
      <form method="post" class="card border-0 shadow-sm rounded-4">
        <div class="card-body"><?= csrfField() ?>
          <h6 class="fw-bold mb-3">Add Module</h6>
          <div class="mb-2"><input name="title" required class="form-control" placeholder="Module title"></div>
          <div class="mb-3"><textarea name="description" rows="2" class="form-control" placeholder="Description"></textarea></div>
          <button class="btn btn-primary w-100">Add Module</button>
        </div>
      </form>
    </div>
    <div class="col-md-7">
      <div class="card border-0 shadow-sm rounded-4"><div class="card-body">
        <h6 class="fw-bold mb-3">Existing Modules</h6>
        <?php if (!$mods): ?><div class="text-muted small">No modules.</div>
        <?php else: foreach ($mods as $m): ?>
          <div class="d-flex justify-content-between align-items-center border-bottom py-2">
            <div><strong><?= e($m['title']) ?></strong><div class="small text-muted"><?= e($m['description']) ?></div></div>
            <div class="d-flex gap-1">
              <a class="btn btn-sm btn-outline-primary" href="lessons.php?module_id=<?= (int)$m['id'] ?>">Lessons</a>
              <form method="post" onsubmit="return confirm('Delete?')" class="d-inline">
                <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="module_id" value="<?= (int)$m['id'] ?>">
                <button class="btn btn-sm btn-outline-danger">Del</button>
              </form>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div></div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('course.edit');
$module_id = (int)($_GET['module_id'] ?? 0);
$m = db_one('SELECT m.*, c.name AS course_name FROM course_modules m JOIN courses c ON c.id=m.course_id WHERE m.id=?', [$module_id]);
if (!$m) exit('Module not found');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (($_POST['action'] ?? '') === 'delete') {
        db_query('DELETE FROM lessons WHERE id=? AND module_id=?', [(int)$_POST['lesson_id'], $module_id]);
        setFlash('success','Lesson deleted.');
    } else {
        $title = trim($_POST['title'] ?? '');
        if ($title !== '') {
            $pos = (int) db_one('SELECT COALESCE(MAX(position),0)+1 p FROM lessons WHERE module_id=?',[$module_id])['p'];
            db_insert('lessons', [
                'module_id'=>$module_id,'title'=>$title,
                'content'=>$_POST['content']??'','video_url'=>trim($_POST['video_url']??''),
                'duration_minutes'=>(int)($_POST['duration_minutes']??0),
                'position'=>$pos,
                'status'=>$_POST['status']??'published',
            ]);
            setFlash('success','Lesson added.');
        }
    }
    redirect('lessons.php?module_id='.$module_id);
}
$lessons = db_all('SELECT * FROM lessons WHERE module_id=? ORDER BY position', [$module_id]);
$pageTitle = 'Lessons';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Lessons — <?= e($m['title']) ?></h4>
  <div class="row g-3">
    <div class="col-md-5">
      <form method="post" class="card border-0 shadow-sm rounded-4">
        <div class="card-body"><?= csrfField() ?>
          <h6 class="fw-bold mb-3">Add Lesson</h6>
          <input name="title" required class="form-control mb-2" placeholder="Title">
          <input name="video_url" class="form-control mb-2" placeholder="Video URL (YouTube/Vimeo)">
          <input type="number" name="duration_minutes" class="form-control mb-2" placeholder="Duration (min)">
          <textarea name="content" rows="4" class="form-control mb-2" placeholder="Content (HTML/Markdown)"></textarea>
          <select name="status" class="form-select mb-3">
            <option value="published">Published</option><option value="draft">Draft</option>
          </select>
          <button class="btn btn-primary w-100">Add Lesson</button>
        </div>
      </form>
    </div>
    <div class="col-md-7">
      <div class="card border-0 shadow-sm rounded-4"><div class="card-body">
        <h6 class="fw-bold mb-3">Lessons (<?= count($lessons) ?>)</h6>
        <?php foreach ($lessons as $l): ?>
          <div class="d-flex justify-content-between border-bottom py-2">
            <div><strong><?= e($l['title']) ?></strong><div class="small text-muted"><?= (int)$l['duration_minutes'] ?> min · <?= e($l['status']) ?></div></div>
            <form method="post" onsubmit="return confirm('Delete?')">
              <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="lesson_id" value="<?= (int)$l['id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Del</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div></div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
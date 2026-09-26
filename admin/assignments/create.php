<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('assignment.create');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data = [
        'course_id'    => (int)$_POST['course_id'],
        'module_id'    => $_POST['module_id'] ? (int)$_POST['module_id'] : null,
        'title'        => trim($_POST['title'] ?? ''),
        'description'  => $_POST['description'] ?? '',
        'instructions' => $_POST['instructions'] ?? '',
        'start_date'   => $_POST['start_date'] ?: null,
        'due_date'     => $_POST['due_date'] ?: null,
        'max_marks'    => (int)($_POST['max_marks'] ?? 100),
        'created_by'   => $_SESSION['user_id'],
        'status'       => $_POST['status'] ?? 'published',
    ];
    if (!$data['title']) $errors[] = 'Title required.';
    if (!$data['due_date']) $errors[] = 'Due date required.';

    if (!empty($_FILES['attachment']['name'])) {
        try { $data['attachment'] = upload_file($_FILES['attachment'], 'documents'); }
        catch (Throwable $e) { $errors[] = $e->getMessage(); }
    }

    if (!$errors) {
        $id = db_insert('assignments', $data);
        createAuditLog('assignment.create','assignment',$id);
        setFlash('success','Assignment created.');
        redirect('submissions.php?id='.$id);
    }
}

$courses = db_all("SELECT id,name FROM courses WHERE status='published'");
$pageTitle='Create Assignment';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Create Assignment</h4>
  <?php if ($errors): ?><div class="alert alert-danger small py-2"><?php foreach($errors as $e) echo '• '.e($e).'<br>'; ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4"><?= csrfField() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Course *</label>
          <select name="course_id" required class="form-select">
            <option value="">—</option>
            <?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Module (optional)</label>
          <select name="module_id" class="form-select"><option value="">— None —</option></select>
        </div>
        <div class="col-12"><label class="form-label small fw-semibold">Title *</label><input name="title" required class="form-control"></div>
        <div class="col-12"><label class="form-label small fw-semibold">Description</label><textarea name="description" rows="3" class="form-control"></textarea></div>
        <div class="col-12"><label class="form-label small fw-semibold">Instructions</label><textarea name="instructions" rows="3" class="form-control"></textarea></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Start</label><input type="datetime-local" name="start_date" class="form-control"></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Due *</label><input type="datetime-local" name="due_date" required class="form-control"></div>
        <div class="col-md-2"><label class="form-label small fw-semibold">Max Marks</label><input type="number" name="max_marks" value="100" class="form-control"></div>
        <div class="col-md-2"><label class="form-label small fw-semibold">Status</label>
          <select name="status" class="form-select"><option>published</option><option>draft</option><option>closed</option></select>
        </div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Attachment</label><input type="file" name="attachment" class="form-control"></div>
      </div>
      <div class="mt-4"><button class="btn btn-primary px-4">Create</button></div>
    </div>
  </form>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
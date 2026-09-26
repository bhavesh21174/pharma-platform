<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('course.edit');

$id = (int)($_GET['id'] ?? 0);
$c  = db_one('SELECT * FROM courses WHERE id=?', [$id]);
if (!$c) { http_response_code(404); exit('Not found'); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data = [
        'course_code'    => trim($_POST['course_code'] ?? ''),
        'name'           => trim($_POST['name'] ?? ''),
        'short_desc'     => trim($_POST['short_desc'] ?? ''),
        'description'    => $_POST['description'] ?? '',
        'duration_weeks' => (int)ceil((int)($_POST['duration_days'] ?? $c['duration_days']) / 7),
        'duration_days'  => (int)($_POST['duration_days'] ?? $c['duration_days']),
        'price'          => (float)($_POST['price'] ?? 0),
        'discount_price' => $_POST['discount_price'] !== '' ? (float)$_POST['discount_price'] : null,
        'level'          => $_POST['level'] ?? 'beginner',
        'eligibility'    => $_POST['eligibility'] ?? '',
        'outcomes'       => $_POST['outcomes'] ?? '',
        'benefits'       => $_POST['benefits'] ?? '',
        'syllabus'       => $_POST['syllabus'] ?? '',
        'status'         => $_POST['status'] ?? 'draft',
        'start_date'     => $_POST['start_date'] ?: null,
        'end_date'       => $_POST['end_date'] ?: null,
    ];
    if ($data['name'] === '') $errors[] = 'Course name required.';
    if (!in_array($data['duration_days'], [30, 60, 90], true)) $errors[] = 'Course duration must be 30, 60, or 90 days.';
    if (!empty($_FILES['thumbnail']['name'])) {
        try { $data['thumbnail'] = upload_file($_FILES['thumbnail'], 'courses', ['image/jpeg','image/png','image/webp']); }
        catch (Throwable $ex) { $errors[] = $ex->getMessage(); }
    }
    if (!$errors) {
        db_update('courses', $data, 'id = :id', ['id'=>$id]);
        createAuditLog('course.edit','course',$id,$c,$data);
        setFlash('success','Course updated.');
        redirect('view.php?id='.$id);
    }
}
$pageTitle = 'Edit Course';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Edit Course #<?= (int)$c['id'] ?></h4>
  <?php if ($errors): ?><div class="alert alert-danger small py-2"><?php foreach($errors as $e) echo '• '.e($e).'<br>'; ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4"><?= csrfField() ?>
      <div class="row g-3">
        <div class="col-md-4"><label class="form-label small fw-semibold">Course Code</label><input name="course_code" class="form-control" value="<?= e($c['course_code']) ?>"></div>
        <div class="col-md-8"><label class="form-label small fw-semibold">Name *</label><input name="name" required class="form-control" value="<?= e($c['name']) ?>"></div>
        <div class="col-12"><label class="form-label small fw-semibold">Short Description</label><input name="short_desc" class="form-control" value="<?= e($c['short_desc']) ?>"></div>
        <div class="col-12"><label class="form-label small fw-semibold">Full Description</label><textarea name="description" rows="4" class="form-control"><?= e($c['description']) ?></textarea></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Duration (days)</label><select name="duration_days" class="form-select"><?php foreach ([30,60,90] as $days): ?><option value="<?= $days ?>" <?= (int)$c['duration_days'] === $days ? 'selected' : '' ?>><?= $days ?> days</option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Price</label><input type="number" step="0.01" name="price" class="form-control" value="<?= e($c['price']) ?>"></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Discount Price</label><input type="number" step="0.01" name="discount_price" class="form-control" value="<?= e($c['discount_price']) ?>"></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Level</label>
          <select name="level" class="form-select">
            <?php foreach (['beginner','intermediate','advanced'] as $l): ?><option <?= $c['level']===$l?'selected':'' ?>><?= $l ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Start Date</label><input type="date" name="start_date" value="<?= e($c['start_date']) ?>" class="form-control"></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">End Date</label><input type="date" name="end_date" value="<?= e($c['end_date']) ?>" class="form-control"></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Eligibility</label><input name="eligibility" value="<?= e($c['eligibility']) ?>" class="form-control"></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Status</label>
          <select name="status" class="form-select">
            <?php foreach (['draft','published','archived'] as $s): ?><option <?= $c['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-12"><label class="form-label small fw-semibold">Outcomes</label><textarea name="outcomes" rows="3" class="form-control"><?= e($c['outcomes']) ?></textarea></div>
        <div class="col-12"><label class="form-label small fw-semibold">Benefits</label><textarea name="benefits" rows="3" class="form-control"><?= e($c['benefits']) ?></textarea></div>
        <div class="col-12"><label class="form-label small fw-semibold">Syllabus</label><textarea name="syllabus" rows="5" class="form-control"><?= e($c['syllabus']) ?></textarea></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Replace Thumbnail</label><input type="file" name="thumbnail" accept="image/*" class="form-control"></div>
      </div>
      <div class="mt-4"><button class="btn btn-primary px-4">Save Changes</button> <a href="view.php?id=<?= (int)$c['id'] ?>" class="btn btn-link">Cancel</a></div>
    </div>
  </form>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
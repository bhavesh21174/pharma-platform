<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('mentor.edit');
$id = (int)($_GET['id'] ?? 0);
$m  = db_one('SELECT m.*, u.full_name, u.email, u.mobile, u.status AS user_status FROM mentors m JOIN users u ON u.id=m.user_id WHERE m.id=?', [$id]);
if (!$m) exit('Not found');

$errors=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    $uData = [
        'full_name'=>trim($_POST['full_name']??''),
        'email'=>trim($_POST['email']??''),
        'mobile'=>trim($_POST['mobile']??''),
        'status'=>$_POST['user_status']??'active',
    ];
    if (!empty($_FILES['photo']['name'])) {
        try { $uData['profile_photo']=upload_file($_FILES['photo'],'profile',['image/jpeg','image/png','image/webp']); }
        catch (Throwable $e) { $errors[]=$e->getMessage(); }
    }
    $mData = [
        'qualification'=>$_POST['qualification']??'',
        'specialization'=>$_POST['specialization']??'',
        'experience_years'=>(int)($_POST['experience_years']??0),
        'bio'=>$_POST['bio']??'',
        'skills'=>$_POST['skills']??'',
        'max_students'=>(int)($_POST['max_students']??20),
        'status'=>$_POST['mentor_status']??'active',
    ];
    if (!$errors) {
        db()->beginTransaction();
        db_update('users',$uData,'id = :id',['id'=>$m['user_id']]);
        db_update('mentors',$mData,'id = :id',['id'=>$id]);
        db()->commit();
        createAuditLog('mentor.edit','mentor',$id,$m,$mData);
        setFlash('success','Mentor updated.');
        redirect('view.php?id='.$id);
    }
}
$pageTitle='Edit Mentor';
include __DIR__.'/../../includes/header.php';
include __DIR__.'/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Edit Mentor: <?= e($m['full_name']) ?></h4>
  <?php if ($errors): ?><div class="alert alert-danger small py-2"><?php foreach($errors as $e) echo '• '.e($e).'<br>'; ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4"><?= csrfField() ?>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label small fw-semibold">Full Name</label><input name="full_name" class="form-control" value="<?= e($m['full_name']) ?>" required></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Email</label><input type="email" name="email" class="form-control" value="<?= e($m['email']) ?>" required></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Mobile</label><input name="mobile" class="form-control" value="<?= e($m['mobile']) ?>"></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">User Status</label>
          <select name="user_status" class="form-select">
            <?php foreach(['active','inactive','suspended'] as $s): ?><option <?= $m['user_status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Qualification</label><input name="qualification" class="form-control" value="<?= e($m['qualification']) ?>"></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Specialization</label><input name="specialization" class="form-control" value="<?= e($m['specialization']) ?>"></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Experience (yrs)</label><input type="number" name="experience_years" class="form-control" value="<?= (int)$m['experience_years'] ?>"></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Max Students</label><input type="number" name="max_students" class="form-control" value="<?= (int)$m['max_students'] ?>"></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Mentor Status</label>
          <select name="mentor_status" class="form-select">
            <?php foreach(['active','inactive'] as $s): ?><option <?= $m['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Replace Photo</label><input type="file" name="photo" accept="image/*" class="form-control"></div>
        <div class="col-12"><label class="form-label small fw-semibold">Bio</label><textarea name="bio" rows="3" class="form-control"><?= e($m['bio']) ?></textarea></div>
        <div class="col-12"><label class="form-label small fw-semibold">Skills</label><input name="skills" class="form-control" value="<?= e($m['skills']) ?>"></div>
      </div>
      <div class="mt-4"><button class="btn btn-primary px-4">Save</button> <a href="view.php?id=<?= (int)$id ?>" class="btn btn-link">Cancel</a></div>
    </div>
  </form>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
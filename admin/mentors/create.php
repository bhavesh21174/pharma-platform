<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('mentor.create');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name  = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile= trim($_POST['mobile'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($name==='' || $email==='') $errors[] = 'Name and email required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
    if (strlen($pass) < 8) $errors[] = 'Password min 8 chars.';
    if (db_one('SELECT id FROM users WHERE email=?',[$email])) $errors[] = 'Email exists.';

    $photo = null;
    if (!empty($_FILES['photo']['name'])) {
        try { $photo = upload_file($_FILES['photo'], 'profile', ['image/jpeg','image/png','image/webp']); }
        catch (Throwable $ex) { $errors[] = $ex->getMessage(); }
    }

    if (!$errors) {
        db()->beginTransaction();
        try {
            $uid = db_insert('users', [
                'full_name'=>$name,'email'=>$email,'mobile'=>$mobile,
                'password_hash'=>password_hash($pass, PASSWORD_BCRYPT),
                'user_type'=>USER_MENTOR,'status'=>'active','profile_photo'=>$photo
            ]);
            $mid = db_insert('mentors', [
                'user_id'=>$uid,
                'qualification'=>$_POST['qualification'] ?? '',
                'specialization'=>$_POST['specialization'] ?? '',
                'experience_years'=>(int)($_POST['experience_years'] ?? 0),
                'bio'=>$_POST['bio'] ?? '',
                'skills'=>$_POST['skills'] ?? '',
                'max_students'=>(int)($_POST['max_students'] ?? 20),
                'status'=>'active',
            ]);
            $role = db_one('SELECT id FROM roles WHERE name=?',[USER_MENTOR]);
            if ($role) db_insert('user_roles', ['user_id'=>$uid,'role_id'=>$role['id']]);
            db()->commit();

            createAuditLog('mentor.create','mentor',$mid,null,['email'=>$email,'name'=>$name]);
            createNotification($uid,'Welcome mentor','Your mentor account is active.');
            setFlash('success','Mentor created.');
            redirect('index.php');
        } catch (Throwable $ex) {
            db()->rollBack();
            $errors[] = 'DB error: '.$ex->getMessage();
        }
    }
}

$pageTitle = 'Add Mentor';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Add Mentor</h4>
  <?php if ($errors): ?><div class="alert alert-danger small py-2"><?php foreach($errors as $e) echo '• '.e($e).'<br>'; ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4"><?= csrfField() ?>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label small fw-semibold">Full Name *</label><input name="full_name" required class="form-control" value="<?= e($_POST['full_name']??'') ?>"></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Email *</label><input type="email" name="email" required class="form-control" value="<?= e($_POST['email']??'') ?>"></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Mobile</label><input name="mobile" class="form-control" value="<?= e($_POST['mobile']??'') ?>"></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Password (min 8)</label><input type="password" name="password" required class="form-control"></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Qualification</label><input name="qualification" class="form-control" value="<?= e($_POST['qualification']??'') ?>"></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Specialization</label><input name="specialization" class="form-control" value="<?= e($_POST['specialization']??'') ?>"></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Experience (yrs)</label><input type="number" name="experience_years" class="form-control" value="<?= (int)($_POST['experience_years']??0) ?>"></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Max Students</label><input type="number" name="max_students" class="form-control" value="<?= (int)($_POST['max_students']??20) ?>"></div>
        <div class="col-12"><label class="form-label small fw-semibold">Bio</label><textarea name="bio" rows="3" class="form-control"><?= e($_POST['bio']??'') ?></textarea></div>
        <div class="col-12"><label class="form-label small fw-semibold">Skills (comma separated)</label><input name="skills" class="form-control" value="<?= e($_POST['skills']??'') ?>"></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Profile Photo</label><input type="file" name="photo" accept="image/*" class="form-control"></div>
      </div>
      <div class="mt-4"><button class="btn btn-primary px-4">Create Mentor</button></div>
    </div>
  </form>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
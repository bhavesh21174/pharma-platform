<?php
require_once __DIR__ . '/../includes/auth-check.php';
requireRole(USER_STUDENT); // or USER_MENTOR
$user = currentUser();
$errors=[]; $success=null;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    $data = ['full_name'=>trim($_POST['full_name']),'mobile'=>trim($_POST['mobile'] ?? '')];
    if (!empty($_POST['password'])) {
        if (strlen($_POST['password']) < 8) $errors[]='Password min 8 chars.';
        elseif ($_POST['password'] !== $_POST['confirm_password']) $errors[]='Passwords mismatch.';
        else $data['password_hash'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
    }
    if (!empty($_FILES['photo']['name'])) {
        try { $data['profile_photo'] = upload_file($_FILES['photo'],'profile',['image/jpeg','image/png','image/webp']); }
        catch (Throwable $e) { $errors[] = $e->getMessage(); }
    }
    if (!$errors) {
        db_update('users', $data, 'id = :id', ['id'=>$user['id']]);
        setFlash('success','Profile updated.');
        redirect('profile.php');
    }
}
$pageTitle='Profile';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Profile</h4>
  <?php if ($errors): ?><div class="alert alert-danger small py-2"><?php foreach($errors as $e) echo '• '.e($e).'<br>'; ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><?= csrfField() ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label small fw-semibold">Full Name</label><input name="full_name" value="<?= e($user['full_name']) ?>" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Email (read only)</label><input value="<?= e($user['email']) ?>" class="form-control" disabled></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Mobile</label><input name="mobile" value="<?= e($user['mobile']) ?>" class="form-control"></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Profile Photo</label><input type="file" name="photo" accept="image/*" class="form-control"></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">New Password (optional)</label><input type="password" name="password" class="form-control"></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Confirm Password</label><input type="password" name="confirm_password" class="form-control"></div>
    </div>
    <button class="btn btn-primary mt-3 px-4">Save</button>
  </div></form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
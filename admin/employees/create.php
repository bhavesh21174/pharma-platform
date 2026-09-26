<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('employee.create');
$errors=[];
$roles = db_all("SELECT name FROM roles WHERE name IN ('ADMIN','FINANCE','HR','COURSE_MANAGER')");

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    $name=trim($_POST['full_name'] ?? ''); $email=trim($_POST['email'] ?? ''); $pass=$_POST['password'] ?? '';
    $type=$_POST['user_type'] ?? 'FINANCE';
    if (!$name||!$email) $errors[]='Name & email required.';
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='Invalid email.';
    if (strlen($pass)<8) $errors[]='Password min 8 chars.';
    if (db_one('SELECT id FROM users WHERE email=?',[$email])) $errors[]='Email exists.';
    if (!in_array($type, array_column($roles,'name'))) $errors[]='Invalid role.';

    if (!$errors) {
        db()->beginTransaction();
        $uid = db_insert('users', [
            'full_name'=>$name,'email'=>$email,'mobile'=>trim($_POST['mobile'] ?? ''),
            'password_hash'=>password_hash($pass,PASSWORD_BCRYPT),
            'user_type'=>$type,'status'=>'active',
        ]);
        db_insert('employees', ['user_id'=>$uid,'department'=>$_POST['department'] ?? '','designation'=>$_POST['designation'] ?? '']);
        $role = db_one('SELECT id FROM roles WHERE name=?',[$type]);
        if ($role) db_insert('user_roles',['user_id'=>$uid,'role_id'=>$role['id']]);
        db()->commit();
        createAuditLog('employee.create','employee',$uid);
        setFlash('success','Employee created.');
        redirect('permissions.php?id='.$uid);
    }
}
$pageTitle='Add Employee';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Add Employee</h4>
  <?php if ($errors): ?><div class="alert alert-danger small py-2"><?php foreach($errors as $e) echo '• '.e($e).'<br>'; ?></div><?php endif; ?>
  <form method="post" class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><?= csrfField() ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label small fw-semibold">Full Name *</label><input name="full_name" required class="form-control"></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Email *</label><input type="email" name="email" required class="form-control"></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Mobile</label><input name="mobile" class="form-control"></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Password *</label><input type="password" name="password" required minlength="8" class="form-control"></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Role *</label>
        <select name="user_type" class="form-select">
          <?php foreach ($roles as $r): ?><option value="<?= e($r['name']) ?>"><?= e($r['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Department</label><input name="department" class="form-control"></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Designation</label><input name="designation" class="form-control"></div>
    </div>
    <div class="mt-4"><button class="btn btn-primary px-4">Create</button></div>
  </div></form>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
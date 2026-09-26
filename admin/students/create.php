<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('student.create');
$errors=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    $name=trim($_POST['full_name']??''); $email=trim($_POST['email']??'');
    $pass=$_POST['password']??'';
    if (!$name||!$email) $errors[]='Name & email required.';
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='Invalid email.';
    if (strlen($pass)<8) $errors[]='Password min 8 chars.';
    if (db_one('SELECT id FROM users WHERE email=?',[$email])) $errors[]='Email exists.';
    if (!$errors) {
        db()->beginTransaction();
        $uid = db_insert('users',[
            'full_name'=>$name,'email'=>$email,'mobile'=>trim($_POST['mobile']??''),
            'password_hash'=>password_hash($pass,PASSWORD_BCRYPT),
            'user_type'=>USER_STUDENT,'status'=>'active',
        ]);
        db_insert('students',[
            'user_id'=>$uid,'dob'=>$_POST['dob']?:null,'gender'=>$_POST['gender']??null,
            'address'=>$_POST['address']??'','qualification'=>$_POST['qualification']??'',
            'college'=>$_POST['college']??'',
        ]);
        $r=db_one('SELECT id FROM roles WHERE name=?',[USER_STUDENT]);
        if ($r) db_insert('user_roles',['user_id'=>$uid,'role_id'=>$r['id']]);
        db()->commit();
        createAuditLog('student.create','student',$uid);
        setFlash('success','Student created.');
        redirect('index.php');
    }
}
$pageTitle='Add Student';
include __DIR__.'/../../includes/header.php';
include __DIR__.'/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Add Student</h4>
  <?php if ($errors): ?><div class="alert alert-danger small py-2"><?php foreach($errors as $e) echo '• '.e($e).'<br>'; ?></div><?php endif; ?>
  <form method="post" class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><?= csrfField() ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label small fw-semibold">Full Name *</label><input name="full_name" required class="form-control"></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Email *</label><input type="email" name="email" required class="form-control"></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Mobile</label><input name="mobile" class="form-control"></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Password *</label><input type="password" name="password" required minlength="8" class="form-control"></div>
      <div class="col-md-4"><label class="form-label small fw-semibold">DOB</label><input type="date" name="dob" class="form-control"></div>
      <div class="col-md-4"><label class="form-label small fw-semibold">Gender</label>
        <select name="gender" class="form-select"><option value="">—</option><option>male</option><option>female</option><option>other</option></select>
      </div>
      <div class="col-md-4"><label class="form-label small fw-semibold">Qualification</label><input name="qualification" class="form-control"></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">College</label><input name="college" class="form-control"></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Address</label><input name="address" class="form-control"></div>
    </div>
    <div class="mt-4"><button class="btn btn-primary px-4">Create</button></div>
  </div></form>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

if (isLoggedIn()) redirect(SITE_URL . '/dashboard-router.php');

$courseId = (int)($_GET['course'] ?? $_POST['course_id'] ?? 0);
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name   = trim($_POST['full_name'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $dob    = $_POST['dob'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $addr   = trim($_POST['address'] ?? '');
    $qual   = trim($_POST['qualification'] ?? '');
    $clg    = trim($_POST['college'] ?? '');
    $pass   = $_POST['password'] ?? '';
    $cpass  = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $pass === '') $errors[] = 'Required fields missing.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $errors[] = 'Invalid email address.';
    if (strlen($pass) < 8)                              $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $cpass)                               $errors[] = 'Passwords do not match.';
    if (db_one('SELECT id FROM users WHERE email = ?', [$email])) $errors[] = 'Email already registered.';
    if ($courseId > 0 && !db_one("SELECT id FROM courses WHERE id = ? AND status = 'published'", [$courseId])) $errors[] = 'Selected course is no longer available.';

    if (!$errors) {
        try {
            db()->beginTransaction();
            $userId = db_insert('users', [
                'full_name'     => $name,
                'email'         => $email,
                'mobile'        => $mobile,
                'password_hash' => password_hash($pass, PASSWORD_BCRYPT),
                'user_type'     => USER_STUDENT,
                'status'        => 'active',
            ]);
            db_insert('students', [
                'user_id'       => $userId,
                'dob'           => $dob ?: null,
                'gender'        => $gender,
                'address'       => $addr,
                'qualification' => $qual,
                'college'       => $clg,
            ]);
            $role = db_one('SELECT id FROM roles WHERE name = ?', [USER_STUDENT]);
            if ($role) db_insert('user_roles', ['user_id' => $userId, 'role_id' => $role['id']]);
            db()->commit();

            $nextUrl = $courseId > 0
              ? SITE_URL . '/admin/students/mentor-selection.php?course_id=' . $courseId
              : SITE_URL . '/student/dashboard.php';
            createNotification($userId, 'Welcome!', 'Your account has been created.', $nextUrl);
            session_regenerate_id(true);
            $_SESSION['user_id']       = $userId;
            $_SESSION['user_type']     = USER_STUDENT;
            $_SESSION['last_activity'] = time();
            redirect($nextUrl);
        } catch (Throwable $ex) {
            db()->rollBack();
            error_log($ex->getMessage());
            $errors[] = 'Registration failed. Try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Register — <?= SITE_NAME ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= SITE_URL ?>/assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-bg">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card shadow-lg border-0 rounded-4">
        <div class="card-body p-4 p-md-5">
          <div class="auth-eyebrow"><?= SITE_NAME ?> · Learning Portal</div>
          <h3 class="fw-bold mb-1">Create your student account</h3>
          <p class="text-muted small mb-4">Join <?= SITE_NAME ?> and start learning</p>

          <?php if ($errors): ?>
            <div class="alert alert-danger py-2 small">
              <?php foreach ($errors as $er) echo '• ' . e($er) . '<br>'; ?>
            </div>
          <?php endif; ?>

          <form method="post" novalidate>
            <?= csrfField() ?>
            <?php if ($courseId > 0): ?><input type="hidden" name="course_id" value="<?= $courseId ?>"><?php endif; ?>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Full Name *</label>
                <input name="full_name" class="form-control" required value="<?= e($_POST['full_name'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Email *</label>
                <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Mobile</label>
                <input name="mobile" class="form-control" value="<?= e($_POST['mobile'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Date of Birth</label>
                <input type="date" name="dob" class="form-control" value="<?= e($_POST['dob'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Gender</label>
                <select name="gender" class="form-select">
                  <option value="">Select</option>
                  <option>male</option><option>female</option><option>other</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Qualification</label>
                <input name="qualification" class="form-control" value="<?= e($_POST['qualification'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">College / Institute</label>
                <input name="college" class="form-control" value="<?= e($_POST['college'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Address</label>
                <input name="address" class="form-control" value="<?= e($_POST['address'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Password *</label>
                <input type="password" name="password" class="form-control" required minlength="8">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Confirm Password *</label>
                <input type="password" name="confirm_password" class="form-control" required minlength="8">
              </div>
            </div>
            <button class="btn btn-primary w-100 py-2 fw-semibold mt-4">Create Account</button>
          </form>

          <p class="text-center small mt-4 mb-0">
            Already registered? <a href="login.php">Login</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/audit-log.php';

if (isLoggedIn()) redirect(SITE_URL . '/dashboard-router.php');

$error = null;
$courseId = (int)($_GET['course'] ?? $_POST['course_id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!$email || !$pass) {
        $error = 'Please fill all fields.';
    } else {
        $user = db_one('SELECT * FROM users WHERE email = ?', [$email]);
        if (!$user || !password_verify($pass, $user['password_hash'])) {
            $error = 'Invalid credentials.';
        } elseif ($user['status'] !== 'active') {
            $error = 'Your account is ' . $user['status'] . '.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id']       = (int)$user['id'];
            $_SESSION['user_type']     = $user['user_type'];
            $_SESSION['last_activity'] = time();
            db_update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
            createAuditLog('login', 'auth', $user['id']);
            $to = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);
            if ($to) redirect($to);
            if ($courseId > 0 && $user['user_type'] === USER_STUDENT
              && db_one("SELECT id FROM courses WHERE id = ? AND status = 'published'", [$courseId])) {
              redirect(SITE_URL . '/admin/students/mentor-selection.php?course_id=' . $courseId);
            }
            redirect(SITE_URL . '/dashboard-router.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — <?= SITE_NAME ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= SITE_URL ?>/assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-bg d-flex align-items-center min-vh-100">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-lg border-0 rounded-4">
        <div class="card-body p-4 p-md-5">
          <div class="auth-eyebrow"><?= SITE_NAME ?> · Learning Portal</div>
          <h3 class="fw-bold mb-1">Welcome back</h3>
          <p class="text-muted small mb-4">Login to your <?= SITE_NAME ?> account</p>

          <?php if ($error): ?>
            <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
          <?php endif; ?>
          <?php foreach (getFlash() as $t => $m): ?>
            <div class="alert alert-<?= e($t) ?> py-2 small"><?= e($m) ?></div>
          <?php endforeach; ?>

          <form method="post" novalidate>
            <?= csrfField() ?>
            <?php if ($courseId > 0): ?><input type="hidden" name="course_id" value="<?= $courseId ?>"><?php endif; ?>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Email</label>
              <input type="email" name="email" class="form-control" required autofocus
                     value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <a href="forgot-password.php" class="small">Forgot password?</a>
            </div>
            <button class="btn btn-primary w-100 py-2 fw-semibold">Login</button>
          </form>

          <hr class="my-4">
          <p class="text-center small mb-0">
            New student? <a href="register.php">Create account</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
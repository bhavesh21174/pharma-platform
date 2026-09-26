<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$user  = $token ? db_one(
    'SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()', [$token]
) : null;

$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (!$user) {
        $err = 'Invalid or expired reset link.';
    } else {
        $pass  = $_POST['password'] ?? '';
        $cpass = $_POST['confirm_password'] ?? '';
        if (strlen($pass) < 8)   $err = 'Password must be at least 8 characters.';
        elseif ($pass !== $cpass) $err = 'Passwords do not match.';
        else {
            db_update('users', [
                'password_hash' => password_hash($pass, PASSWORD_BCRYPT),
                'reset_token'   => null,
                'reset_expires' => null,
            ], 'id = :id', ['id' => $user['id']]);
            setFlash('success', 'Password reset successfully. Please login.');
            redirect(SITE_URL . '/login.php');
        }
    }
}
?>
<!DOCTYPE html><html><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-light d-flex align-items-center min-vh-100">
<div class="container" style="max-width:480px">
  <div class="card shadow-sm border-0 rounded-4"><div class="card-body p-4">
    <h4 class="fw-bold mb-3">Reset Password</h4>
    <?php if ($err): ?><div class="alert alert-danger small py-2"><?= e($err) ?></div><?php endif; ?>
    <?php if (!$user && !$err): ?>
      <div class="alert alert-warning small py-2">Link invalid or expired.</div>
    <?php else: ?>
    <form method="post"><?= csrfField() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <div class="mb-3">
        <label class="form-label small fw-semibold">New Password</label>
        <input type="password" name="password" required class="form-control" minlength="8">
      </div>
      <div class="mb-3">
        <label class="form-label small fw-semibold">Confirm</label>
        <input type="password" name="confirm_password" required class="form-control" minlength="8">
      </div>
      <button class="btn btn-primary w-100">Reset Password</button>
    </form>
    <?php endif; ?>
  </div></div>
</div>
</body></html>
<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/mail.php';

$msg = null; $err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = trim($_POST['email'] ?? '');
    $user  = db_one('SELECT id, full_name FROM users WHERE email = ?', [$email]);
    if ($user) {
        $token = generateToken(64);
        db_update('users', [
            'reset_token'   => $token,
            'reset_expires' => date('Y-m-d H:i:s', time() + 3600),
        ], 'id = :id', ['id' => $user['id']]);
        $link = SITE_URL . '/reset-password.php?token=' . $token;
        send_mail($email, 'Reset your password',
            "<p>Hi {$user['full_name']},</p><p>Reset link (valid 1 hour):</p><p><a href=\"$link\">$link</a></p>");
    }
    // Always show neutral message
    $msg = 'If that email exists, a reset link has been sent.';
}
?>
<!DOCTYPE html><html><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forgot Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-light d-flex align-items-center min-vh-100">
<div class="container" style="max-width:480px">
  <div class="card shadow-sm border-0 rounded-4"><div class="card-body p-4">
    <h4 class="fw-bold mb-3">Forgot Password</h4>
    <?php if ($msg): ?><div class="alert alert-info small py-2"><?= e($msg) ?></div><?php endif; ?>
    <form method="post"><?= csrfField() ?>
      <div class="mb-3">
        <label class="form-label small fw-semibold">Email</label>
        <input type="email" name="email" required class="form-control">
      </div>
      <button class="btn btn-primary w-100">Send Reset Link</button>
    </form>
  </div></div>
</div>
</body></html>
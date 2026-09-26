<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
$certId = $_GET['id'] ?? '';
$cert = $certId ? db_one('SELECT * FROM certificates WHERE certificate_id=?',[$certId]) : null;
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Verify Certificate</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-light">
<div class="container py-5" style="max-width:720px">
  <h2 class="fw-bold mb-4"><?= SITE_NAME ?> — Certificate Verification</h2>
  <form class="row g-2 mb-4">
    <div class="col-md-8"><input name="id" value="<?= e($certId) ?>" class="form-control" placeholder="Certificate ID e.g. PA-2026-XXXX"></div>
    <div class="col-md-4"><button class="btn btn-primary w-100">Verify</button></div>
  </form>

  <?php if ($certId && !$cert): ?>
    <div class="alert alert-danger">No certificate found for that ID.</div>
  <?php elseif ($cert): ?>
    <div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
      <div class="text-success fw-bold mb-2">✓ Valid Certificate</div>
      <h4 class="fw-bold mb-1"><?= e($cert['student_name']) ?></h4>
      <p class="text-muted mb-1">completed</p>
      <h5 class="fw-bold"><?= e($cert['course_name']) ?></h5>
      <hr>
      <div class="small text-muted">
        Certificate ID: <code><?= e($cert['certificate_id']) ?></code><br>
        Completion Date: <?= formatDate($cert['completion_date']) ?><br>
        Issued By: <?= SITE_NAME ?>
      </div>
    </div></div>
  <?php endif; ?>
</div>
</body></html>
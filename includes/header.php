<?php
require_once __DIR__ . '/functions.php';
$__user = currentUser();
$__flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle ?? SITE_NAME) ?> — <?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
<link href="<?= SITE_URL ?>/assets/css/dashboard.css" rel="stylesheet">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<?php if ($__flash): foreach ($__flash as $t => $m): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index:1080">
  <div class="toast show align-items-center text-bg-<?= e($t) ?> border-0">
    <div class="d-flex">
      <div class="toast-body"><?= e($m) ?></div>
      <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
<?php endforeach; endif; ?>
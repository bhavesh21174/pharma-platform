<?php
$items = [
  ['dashboard.php','Dashboard','bi-speedometer2'],
  ['payments.php','Payments','bi-cash-coin'],
  ['transactions.php','Transactions','bi-arrow-left-right'],
  ['invoices.php','Invoices','bi-receipt'],
  ['refunds.php','Refunds','bi-arrow-counterclockwise'],
  ['reports.php','Reports','bi-bar-chart'],
];
?>
<button class="sidebar-toggle" type="button" data-sidebar-toggle aria-expanded="false" aria-controls="finance-sidebar" aria-label="Open navigation"><i class="bi bi-list" aria-hidden="true"></i></button>
<aside class="app-sidebar" id="finance-sidebar">
  <div class="brand"><?= SITE_NAME ?><span>Finance workspace</span></div>
  <nav class="nav flex-column">
    <?php foreach ($items as [$url,$label,$icon]): ?>
      <a class="nav-link" href="<?= SITE_URL ?>/finance/<?= $url ?>"><i class="bi <?= $icon ?>"></i> <?= $label ?></a>
    <?php endforeach; ?>
    <a class="nav-link nav-link-logout" href="<?= SITE_URL ?>/logout.php"><i class="bi bi-box-arrow-left" aria-hidden="true"></i><span>Sign out</span></a>
  </nav>
</aside>
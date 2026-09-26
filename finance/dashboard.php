<?php
require_once __DIR__ . '/../includes/auth-check.php';
requireRole(USER_FINANCE, USER_ADMIN, USER_SUPER_ADMIN);
requirePermission('finance.view');

$stats = [
  'total'    => (float) db_one("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE status='successful'")['s'],
  'today'    => (float) db_one("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE status='successful' AND DATE(paid_at)=CURDATE()")['s'],
  'month'    => (float) db_one("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE status='successful' AND MONTH(paid_at)=MONTH(CURDATE()) AND YEAR(paid_at)=YEAR(CURDATE())")['s'],
  'pending'  => (int) db_one("SELECT COUNT(*) c FROM payments WHERE status='pending'")['c'],
  'failed'   => (int) db_one("SELECT COUNT(*) c FROM payments WHERE status='failed'")['c'],
  'refunds'  => (float) db_one("SELECT COALESCE(SUM(amount),0) s FROM refunds WHERE status='processed'")['s'],
];

$recent = db_all("SELECT p.*, c.name AS course_name, u.full_name AS student_name
                  FROM payments p
                  JOIN courses c ON c.id=p.course_id
                  JOIN students s ON s.id=p.student_id
                  JOIN users u ON u.id=s.user_id
                  ORDER BY p.id DESC LIMIT 10");

$pageTitle='Finance Dashboard';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/finance-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Finance Dashboard</h4>
  <div class="row g-3">
    <div class="col-6 col-md-3"><div class="stat-card"><div class="label">Total Revenue</div><div class="value"><?= formatCurrency($stats['total']) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="label">Today</div><div class="value"><?= formatCurrency($stats['today']) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="label">This Month</div><div class="value"><?= formatCurrency($stats['month']) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="label">Refunds</div><div class="value"><?= formatCurrency($stats['refunds']) ?></div></div></div>
  </div>
  <div class="row g-3 mt-3">
    <div class="col-6 col-md-3"><div class="stat-card"><div class="label">Pending</div><div class="value"><?= $stats['pending'] ?></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="label">Failed</div><div class="value"><?= $stats['failed'] ?></div></div></div>
  </div>

  <div class="card border-0 shadow-sm rounded-4 mt-4"><div class="card-body">
    <h6 class="fw-bold mb-3">Recent Payments</h6>
    <div class="table-responsive">
      <table class="table small align-middle">
        <thead><tr><th>#</th><th>Student</th><th>Course</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= e($r['student_name']) ?></td>
            <td><?= e($r['course_name']) ?></td>
            <td><?= formatCurrency((float)$r['amount']) ?></td>
            <td><span class="badge bg-<?= $r['status']==='successful'?'success':($r['status']==='failed'?'danger':'secondary') ?>"><?= e($r['status']) ?></span></td>
            <td><?= formatDate($r['paid_at'] ?? $r['created_at'], 'd M Y H:i') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div></div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
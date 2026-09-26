<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('finance.report');

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

if (isset($_GET['export']) && $_GET['export']==='csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=finance_'.date('Ymd').'.csv');
    $out = fopen('php://output','w');
    fputcsv($out, ['Payment ID','Order ID','Student','Course','Amount','Currency','Status','Paid At']);
    $rows = db_all("SELECT p.*, u.full_name AS student_name, c.name AS course_name
                    FROM payments p JOIN students s ON s.id=p.student_id JOIN users u ON u.id=s.user_id
                    JOIN courses c ON c.id=p.course_id
                    WHERE DATE(p.created_at) BETWEEN ? AND ?", [$from,$to]);
    foreach ($rows as $r) fputcsv($out, [$r['id'],$r['order_id'],$r['student_name'],$r['course_name'],$r['amount'],$r['currency'],$r['status'],$r['paid_at']]);
    fclose($out); exit;
}

$rows = db_all("SELECT p.*, u.full_name AS student_name, c.name AS course_name
                FROM payments p JOIN students s ON s.id=p.student_id JOIN users u ON u.id=s.user_id
                JOIN courses c ON c.id=p.course_id
                WHERE DATE(p.created_at) BETWEEN ? AND ? ORDER BY p.id DESC", [$from,$to]);
$total = array_sum(array_column(array_filter($rows, fn($r)=>$r['status']==='successful'), 'amount'));

$pageTitle='Finance Report';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <div class="d-flex justify-content-between mb-3">
    <h4 class="fw-bold mb-0">Finance Report</h4>
    <a class="btn btn-success" href="?<?= http_build_query(array_merge($_GET,['export'=>'csv'])) ?>"><i class="bi bi-download"></i> Export CSV</a>
  </div>
  <form class="row g-2 mb-3">
    <div class="col-md-3"><input type="date" name="from" value="<?= e($from) ?>" class="form-control"></div>
    <div class="col-md-3"><input type="date" name="to" value="<?= e($to) ?>" class="form-control"></div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
  </form>
  <div class="stat-card mb-3"><div class="label">Successful Revenue (Range)</div><div class="value"><?= formatCurrency((float)$total) ?></div></div>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table small align-middle mb-0">
      <thead class="table-light"><tr><th>ID</th><th>Student</th><th>Course</th><th>Amount</th><th>Status</th><th>Paid</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td><td><?= e($r['student_name']) ?></td><td><?= e($r['course_name']) ?></td>
          <td><?= formatCurrency((float)$r['amount']) ?></td><td><?= e($r['status']) ?></td>
          <td><?= formatDate($r['paid_at'] ?? $r['created_at'],'d M Y') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
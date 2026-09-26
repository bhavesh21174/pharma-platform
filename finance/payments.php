<?php
require_once __DIR__ . '/../includes/auth-check.php';
requireRole(USER_FINANCE, USER_ADMIN, USER_SUPER_ADMIN);
requirePermission('payment.view');

$status = $_GET['status'] ?? '';
$params=[]; $where='1=1';
if ($status) { $where.=' AND p.status=?'; $params[]=$status; }

$rows = db_all("SELECT p.*, u.full_name AS student_name, c.name AS course_name
                FROM payments p JOIN students s ON s.id=p.student_id JOIN users u ON u.id=s.user_id
                JOIN courses c ON c.id=p.course_id
                WHERE $where ORDER BY p.id DESC LIMIT 200",$params);

$pageTitle='Payments';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/finance-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Payments</h4>
  <form class="row g-2 mb-3">
    <div class="col-md-3">
      <select name="status" class="form-select">
        <option value="">All statuses</option>
        <?php foreach (['pending','successful','failed','refunded'] as $s): ?>
          <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
  </form>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table small align-middle mb-0">
      <thead class="table-light"><tr><th>ID</th><th>Order</th><th>Student</th><th>Course</th><th>Amount</th><th>Status</th><th>Paid</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><code><?= e($r['order_id']) ?></code></td>
          <td><?= e($r['student_name']) ?></td>
          <td><?= e($r['course_name']) ?></td>
          <td><?= formatCurrency((float)$r['amount']) ?></td>
          <td><span class="badge bg-<?= $r['status']==='successful'?'success':($r['status']==='failed'?'danger':($r['status']==='refunded'?'warning':'secondary')) ?>"><?= e($r['status']) ?></span></td>
          <td><?= formatDate($r['paid_at'] ?? $r['created_at'],'d M Y H:i') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
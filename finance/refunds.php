<?php
require_once __DIR__ . '/../includes/auth-check.php';
requireRole(USER_FINANCE, USER_ADMIN, USER_SUPER_ADMIN);
requirePermission('payment.refund');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    $pid = (int)$_POST['payment_id'];
    $amt = (float)$_POST['amount'];
    $reason = trim($_POST['reason'] ?? '');
    $p = db_one('SELECT * FROM payments WHERE id=?',[$pid]);
    if ($p && $amt > 0 && $amt <= (float)$p['amount'] && $p['status']==='successful') {
        // NOTE: real Razorpay refund API call should be added here
        db_insert('refunds', ['payment_id'=>$pid,'amount'=>$amt,'reason'=>$reason,'status'=>'processed',
                              'processed_by'=>$_SESSION['user_id'],'processed_at'=>date('Y-m-d H:i:s')]);
        $newStatus = ($amt >= (float)$p['amount']) ? 'refunded' : 'partially_refunded';
        db_update('payments', ['status'=>$newStatus,'refund_status'=>'processed'], 'id = :id', ['id'=>$pid]);
        createAuditLog('payment.refund','payment',$pid,null,['amount'=>$amt]);
        setFlash('success','Refund recorded.');
    } else {
        setFlash('danger','Invalid refund request.');
    }
    redirect('refunds.php');
}

$rows = db_all("SELECT r.*, p.order_id, u.full_name AS student_name, p.amount AS payment_amount
                FROM refunds r JOIN payments p ON p.id=r.payment_id
                JOIN students s ON s.id=p.student_id JOIN users u ON u.id=s.user_id
                ORDER BY r.id DESC");
$successful = db_all("SELECT p.*, u.full_name AS student_name FROM payments p
                      JOIN students s ON s.id=p.student_id JOIN users u ON u.id=s.user_id
                      WHERE p.status='successful' ORDER BY p.id DESC LIMIT 50");
$pageTitle='Refunds';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/finance-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Refunds</h4>
  <form method="post" class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body"><?= csrfField() ?>
    <h6 class="fw-bold mb-3">Record Refund</h6>
    <div class="row g-2">
      <div class="col-md-5">
        <select name="payment_id" required class="form-select">
          <option value="">Select payment...</option>
          <?php foreach ($successful as $p): ?>
            <option value="<?= (int)$p['id'] ?>">#<?= (int)$p['id'] ?> · <?= e($p['student_name']) ?> · <?= formatCurrency((float)$p['amount']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><input type="number" step="0.01" name="amount" required class="form-control" placeholder="Amount"></div>
      <div class="col-md-3"><input name="reason" class="form-control" placeholder="Reason"></div>
      <div class="col-md-2"><button class="btn btn-danger w-100">Refund</button></div>
    </div>
  </div></form>

  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table small align-middle mb-0">
      <thead class="table-light"><tr><th>#</th><th>Payment</th><th>Student</th><th>Amount</th><th>Reason</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td>#<?= (int)$r['payment_id'] ?></td>
          <td><?= e($r['student_name']) ?></td>
          <td><?= formatCurrency((float)$r['amount']) ?></td>
          <td><?= e($r['reason']) ?></td>
          <td><span class="badge bg-secondary"><?= e($r['status']) ?></span></td>
          <td><?= formatDate($r['processed_at'] ?? $r['created_at'],'d M Y') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
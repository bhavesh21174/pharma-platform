<?php
require_once __DIR__ . '/../includes/auth-check.php';
requireRole(USER_STUDENT);
$student = db_one('SELECT id FROM students WHERE user_id=?', [$_SESSION['user_id']]);
if (!$student) exit('Student profile missing.');

$payments = db_all("SELECT p.*, c.name AS course_name
                    FROM payments p
                    JOIN courses c ON c.id=p.course_id
                    WHERE p.student_id=?
                    ORDER BY p.created_at DESC", [$student['id']]);
$balances = db_all("SELECT e.id, e.total_fee, e.paid_amount, e.second_installment_due_at, c.name AS course_name
                    FROM enrollments e JOIN courses c ON c.id=e.course_id
                    WHERE e.student_id=? AND e.status IN ('pending_admin','active') AND e.paid_amount < e.total_fee
                    ORDER BY e.second_installment_due_at", [$student['id']]);

$pageTitle = 'Payments';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Payments</h4>
  <?php foreach ($balances as $balance): $isDue = $balance['second_installment_due_at'] && strtotime($balance['second_installment_due_at']) <= time(); ?>
    <section class="card mb-3"><div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
      <div>
        <h5 class="fw-bold mb-1"><?= e($balance['course_name']) ?> · <?= $balance['second_installment_due_at'] ? 'Remaining balance' : 'Mentor allocation pending' ?></h5>
        <p class="text-muted small mb-1">Paid <?= formatCurrency((float)$balance['paid_amount']) ?> of <?= formatCurrency((float)$balance['total_fee']) ?>.</p>
        <p class="small mb-0"><?= !$balance['second_installment_due_at'] ? 'Your 50% deposit is received. Admin allocation is required before the course schedule starts.' : ($isDue ? 'Installment due now.' : 'Balance due ' . formatDate($balance['second_installment_due_at'], 'd M Y') . '.') ?></p>
      </div>
      <?php if ($isDue && $balance['second_installment_due_at']): ?>
        <form method="post" action="<?= SITE_URL ?>/integrations/razorpay/create-course-order.php">
          <?= csrfField() ?>
          <input type="hidden" name="installment_no" value="2">
          <input type="hidden" name="enrollment_id" value="<?= (int)$balance['id'] ?>">
          <button class="btn btn-primary">Pay <?= formatCurrency((float)$balance['total_fee'] - (float)$balance['paid_amount']) ?></button>
        </form>
      <?php endif; ?>
    </div></section>
  <?php endforeach; ?>
  <div class="card"><div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light"><tr><th>Course</th><th>Installment</th><th>Reference</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
      <?php foreach ($payments as $payment): ?>
        <tr>
          <td class="fw-semibold"><?= e($payment['course_name']) ?></td>
          <td><?= (int)$payment['installment_no'] === 1 ? 'First 50%' : 'Balance' ?></td>
          <td><code><?= e($payment['payment_id'] ?: $payment['order_id'] ?: '—') ?></code></td>
          <td><?= formatCurrency((float)$payment['amount']) ?></td>
          <td><?= e($payment['method'] ?? '—') ?></td>
          <td><span class="badge bg-<?= $payment['status']==='successful'?'success':(in_array($payment['status'],['failed','refunded','partially_refunded'],true)?'danger':'secondary') ?>"><?= e($payment['status']) ?></span></td>
          <td><?= formatDate($payment['paid_at'] ?? $payment['created_at'], 'd M Y') ?></td>
        </tr>
      <?php endforeach; if (!$payments): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No payments found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
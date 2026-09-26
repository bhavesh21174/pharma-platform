<?php
require_once __DIR__ . '/../includes/auth-check.php';
requireRole(USER_STUDENT);
$student = db_one('SELECT id FROM students WHERE user_id=?', [$_SESSION['user_id']]);
if (!$student) exit('Student profile missing.');

$invoices = db_all("SELECT i.*, c.name AS course_name
                    FROM invoices i
                    JOIN payments p ON p.id=i.payment_id
                    JOIN courses c ON c.id=p.course_id
                    WHERE i.student_id=?
                    ORDER BY i.issued_at DESC", [$student['id']]);

$pageTitle = 'Invoices';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Invoices</h4>
  <div class="card"><div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light"><tr><th>Invoice</th><th>Course</th><th>Amount</th><th>Tax</th><th>Total</th><th>Issued</th></tr></thead>
      <tbody>
      <?php foreach ($invoices as $invoice): ?>
        <tr>
          <td><code><?= e($invoice['invoice_no']) ?></code></td>
          <td><?= e($invoice['course_name']) ?></td>
          <td><?= formatCurrency((float)$invoice['amount']) ?></td>
          <td><?= formatCurrency((float)$invoice['tax']) ?></td>
          <td class="fw-semibold"><?= formatCurrency((float)$invoice['total']) ?></td>
          <td><?= formatDate($invoice['issued_at']) ?></td>
        </tr>
      <?php endforeach; if (!$invoices): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No invoices found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
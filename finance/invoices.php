<?php
require_once __DIR__ . '/../includes/auth-check.php';
requireRole(USER_FINANCE, USER_ADMIN, USER_SUPER_ADMIN);
requirePermission('invoice.view');
$rows = db_all("SELECT i.*, u.full_name AS student_name FROM invoices i
                JOIN students s ON s.id=i.student_id JOIN users u ON u.id=s.user_id
                ORDER BY i.id DESC");
$pageTitle='Invoices';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/finance-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Invoices</h4>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table small align-middle mb-0">
      <thead class="table-light"><tr><th>Invoice #</th><th>Student</th><th>Amount</th><th>Tax</th><th>Total</th><th>Issued</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><code><?= e($r['invoice_no']) ?></code></td>
          <td><?= e($r['student_name']) ?></td>
          <td><?= formatCurrency((float)$r['amount']) ?></td>
          <td><?= formatCurrency((float)$r['tax']) ?></td>
          <td><?= formatCurrency((float)$r['total']) ?></td>
          <td><?= formatDate($r['issued_at'],'d M Y') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
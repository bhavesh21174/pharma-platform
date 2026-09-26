<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('audit.view');

$module = $_GET['module'] ?? '';
$params = []; $where='1=1';
if ($module) { $where.=' AND al.module=?'; $params[]=$module; }

$rows = db_all("SELECT al.*, u.full_name AS user_name FROM audit_logs al
                LEFT JOIN users u ON u.id=al.user_id
                WHERE $where ORDER BY al.id DESC LIMIT 300",$params);
$pageTitle='Audit Logs';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Audit Logs</h4>
  <form class="row g-2 mb-3">
    <div class="col-md-3"><input name="module" value="<?= e($module) ?>" class="form-control" placeholder="Filter by module"></div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
  </form>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table small align-middle mb-0">
      <thead class="table-light"><tr><th>When</th><th>User</th><th>Action</th><th>Module</th><th>Record</th><th>IP</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= formatDate($r['created_at'],'d M Y H:i') ?></td>
          <td><?= e($r['user_name'] ?? '—') ?></td>
          <td><code><?= e($r['action']) ?></code></td>
          <td><?= e($r['module']) ?></td>
          <td><?= e($r['record_id']) ?></td>
          <td><?= e($r['ip_address']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
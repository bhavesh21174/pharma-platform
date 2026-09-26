<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('employee.create');
$rows = db_all("SELECT e.*, u.full_name, u.email, u.status AS user_status, u.user_type
                FROM employees e JOIN users u ON u.id=e.user_id ORDER BY e.id DESC");
$pageTitle='Employees';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <div class="d-flex justify-content-between mb-3">
    <h4 class="fw-bold mb-0">Employees</h4>
    <a class="btn btn-primary" href="create.php"><i class="bi bi-plus-lg"></i> Add Employee</a>
  </div>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table small align-middle mb-0">
      <thead class="table-light"><tr><th>#</th><th>Name</th><th>Email</th><th>Department</th><th>Role</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= e($r['full_name']) ?></td>
          <td><?= e($r['email']) ?></td>
          <td><?= e($r['department']) ?></td>
          <td><span class="badge bg-secondary"><?= e($r['user_type']) ?></span></td>
          <td><span class="badge bg-<?= $r['user_status']==='active'?'success':'secondary' ?>"><?= e($r['user_status']) ?></span></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int)$r['id'] ?>">View</a>
            <a class="btn btn-sm btn-outline-warning" href="permissions.php?id=<?= (int)$r['id'] ?>">Permissions</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
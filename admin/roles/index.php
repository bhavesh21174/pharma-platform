<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('role.manage');
$rows = db_all("SELECT r.*,
                  (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id=r.id) AS perm_count,
                  (SELECT COUNT(*) FROM user_roles ur WHERE ur.role_id=r.id) AS user_count
                FROM roles r ORDER BY r.id");
$pageTitle='Roles';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Roles & Permissions</h4>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table small align-middle mb-0">
      <thead class="table-light"><tr><th>#</th><th>Name</th><th>Description</th><th>Permissions</th><th>Users</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= e($r['name']) ?></td>
          <td><?= e($r['description']) ?></td>
          <td><?= (int)$r['perm_count'] ?></td>
          <td><?= (int)$r['user_count'] ?></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="permissions.php?id=<?= (int)$r['id'] ?>">Edit Permissions</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
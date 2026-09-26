<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('role.manage');
$rid = (int)($_GET['id'] ?? 0);
$role = db_one('SELECT * FROM roles WHERE id=?',[$rid]);
if (!$role) exit('Not found');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    $perms = $_POST['perms'] ?? [];
    db()->beginTransaction();
    db_query('DELETE FROM role_permissions WHERE role_id=?',[$rid]);
    foreach ($perms as $pid) db_insert('role_permissions',['role_id'=>$rid,'permission_id'=>(int)$pid]);
    db()->commit();
    createAuditLog('role.permissions_update','role',$rid);
    setFlash('success','Role permissions updated.');
    redirect('permissions.php?id='.$rid);
}

$all = db_all('SELECT * FROM permissions ORDER BY module, code');
$assigned = array_column(db_all('SELECT permission_id FROM role_permissions WHERE role_id=?',[$rid]), 'permission_id');
$pageTitle='Role Permissions';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Permissions — <?= e($role['name']) ?></h4>
  <form method="post" class="card border-0 shadow-sm rounded-4"><div class="card-body"><?= csrfField() ?>
    <div class="row g-2">
      <?php foreach ($all as $p): ?>
        <div class="col-md-4">
          <label class="form-check">
            <input class="form-check-input" type="checkbox" name="perms[]" value="<?= (int)$p['id'] ?>" <?= in_array((int)$p['id'], array_map('intval',$assigned), true)?'checked':'' ?>>
            <span class="form-check-label small"><code><?= e($p['code']) ?></code> — <?= e($p['label']) ?></span>
          </label>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-primary mt-3 px-4">Save</button>
  </div></form>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
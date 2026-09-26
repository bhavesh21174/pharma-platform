<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('role.manage');

$uid = (int)($_GET['id'] ?? 0);
$user = db_one('SELECT * FROM users WHERE id=?',[$uid]);
if (!$user) exit('Not found');
$e = db_one('SELECT * FROM employees WHERE user_id=?',[$uid]);

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    // Get grant/deny maps
    $grants = $_POST['grant'] ?? [];
    $denies = $_POST['deny']  ?? [];
    $allPerms = db_all('SELECT id, code FROM permissions');

    db()->beginTransaction();
    db_query('DELETE FROM user_permissions WHERE user_id=?',[$uid]);
    foreach ($allPerms as $p) {
        $pid = (int)$p['id'];
        if (in_array($pid, $denies, true)) {
            db_insert('user_permissions', ['user_id'=>$uid,'permission_id'=>$pid,'allowed'=>0]);
        } elseif (in_array($pid, $grants, true)) {
            db_insert('user_permissions', ['user_id'=>$uid,'permission_id'=>$pid,'allowed'=>1]);
        }
    }
    db()->commit();
    createAuditLog('employee.permissions_update','employee',$uid,null,['grants'=>count($grants),'denies'=>count($denies)]);
    setFlash('success','Permissions updated.');
    redirect('index.php');
}

$perms = db_all('SELECT * FROM permissions ORDER BY module, code');
$userPerms = db_all('SELECT permission_id, allowed FROM user_permissions WHERE user_id=?',[$uid]);
$map = [];
foreach ($userPerms as $up) $map[(int)$up['permission_id']] = (int)$up['allowed'];

$pageTitle='Permissions';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-1">Permissions — <?= e($user['full_name']) ?></h4>
  <p class="text-muted small">Role: <?= e($user['user_type']) ?> · Department: <?= e($e['department'] ?? '—') ?></p>

  <form method="post">
    <?= csrfField() ?>
    <div class="card border-0 shadow-sm rounded-4"><div class="card-body">
      <div class="small text-muted mb-3">Granted: ✅ &nbsp; Denied: ⛔ &nbsp; Inherit from role: ⚪ (unchecked)</div>
      <div class="table-responsive">
        <table class="table small align-middle">
          <thead><tr><th>Code</th><th>Module</th><th>Label</th><th class="text-center">Grant</th><th class="text-center">Deny</th></tr></thead>
          <tbody>
          <?php foreach ($perms as $p):
            $state = $map[(int)$p['id']] ?? null; ?>
            <tr>
              <td><code><?= e($p['code']) ?></code></td>
              <td><?= e($p['module']) ?></td>
              <td><?= e($p['label']) ?></td>
              <td class="text-center"><input type="checkbox" name="grant[]" value="<?= (int)$p['id'] ?>" <?= $state===1?'checked':'' ?>></td>
              <td class="text-center"><input type="checkbox" name="deny[]" value="<?= (int)$p['id'] ?>" <?= $state===0?'checked':'' ?>></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <button class="btn btn-primary px-4">Save Permissions</button>
    </div></div>
  </form>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
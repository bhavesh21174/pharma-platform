<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/performance.php';
requirePermission('attendance.view'); // reuse broad permission

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    if (($_POST['action']??'')==='rebuild_all') {
        $courses = db_all('SELECT id FROM courses');
        foreach ($courses as $c) rebuild_leaderboard((int)$c['id']);
        createAuditLog('leaderboard.rebuild_all','leaderboard');
        setFlash('success','Leaderboard rebuilt.');
    } elseif (($_POST['action']??'')==='rebuild_one') {
        $cid = (int)$_POST['course_id'];
        rebuild_leaderboard($cid);
        createAuditLog('leaderboard.rebuild','leaderboard',$cid);
        setFlash('success','Rebuilt.');
    } elseif (($_POST['action']??'')==='toggle') {
        db_query("REPLACE INTO settings (skey,svalue,sgroup) VALUES ('leaderboard_public',?,'ranking')",
                 [($_POST['leaderboard_public'] ?? '1')]);
        setFlash('success','Visibility updated.');
    }
    redirect('index.php');
}

$courses = db_all('SELECT id,name FROM courses ORDER BY id');
$cid = (int)($_GET['course_id'] ?? ($courses[0]['id'] ?? 0));
$rows = $cid ? db_all("SELECT l.*, u.full_name AS student_name FROM leaderboard l
                       JOIN students s ON s.id=l.student_id JOIN users u ON u.id=s.user_id
                       WHERE l.course_id=? ORDER BY l.rank_position",[$cid]) : [];

$public = setting('leaderboard_public','1');

$pageTitle='Leaderboard';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Leaderboard</h4>
    <div class="d-flex gap-2">
      <form method="post"><?= csrfField() ?>
        <input type="hidden" name="action" value="toggle">
        <select name="leaderboard_public" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="1" <?= $public==='1'?'selected':'' ?>>Public</option>
          <option value="0" <?= $public!=='1'?'selected':'' ?>>Hidden</option>
        </select>
      </form>
      <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="rebuild_all">
        <button class="btn btn-primary btn-sm">Rebuild All</button>
      </form>
    </div>
  </div>

  <form class="row g-2 mb-3">
    <div class="col-md-4">
      <select name="course_id" class="form-select" onchange="this.form.submit()">
        <?php foreach ($courses as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $cid===$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>

  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table small align-middle mb-0">
      <thead class="table-light"><tr><th>Rank</th><th>Student</th><th>Score</th><th>Updated</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['rank_position'] ?></td>
          <td><?= e($r['student_name']) ?></td>
          <td><?= number_format((float)$r['score'],2) ?></td>
          <td><?= formatDate($r['updated_at'],'d M Y H:i') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
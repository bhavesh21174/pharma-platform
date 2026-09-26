<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('mentor.edit');
$mentor_id = (int)($_GET['mentor_id'] ?? 0);
$mentor = db_one('SELECT m.*, u.full_name FROM mentors m JOIN users u ON u.id=m.user_id WHERE m.id=?',[$mentor_id]);
if (!$mentor) exit('Not found');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    if (($_POST['action']??'')==='delete') {
        db_query('DELETE FROM mentor_availability WHERE id=? AND mentor_id=?',[(int)$_POST['id'],$mentor_id]);
        createAuditLog('mentor.availability.override_delete','mentor',$mentor_id);
    } else {
        $date = $_POST['avail_date'] ?? '';
        $st   = $_POST['start_time'] ?? '';
        $et   = $_POST['end_time'] ?? '';
        if ($date && $st && $et && $st < $et) {
            db_insert('mentor_availability', [
                'mentor_id'=>$mentor_id,'avail_date'=>$date,'start_time'=>$st,'end_time'=>$et
            ]);
            createAuditLog('mentor.availability.override_add','mentor',$mentor_id,null,[$date,$st,$et]);
        }
    }
    redirect('availability.php?mentor_id='.$mentor_id);
}

$rows = db_all('SELECT * FROM mentor_availability WHERE mentor_id=? AND avail_date >= CURDATE() ORDER BY avail_date, start_time',[$mentor_id]);

$pageTitle = 'Mentor Availability';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Availability — <?= e($mentor['full_name']) ?></h4>
  <div class="row g-3">
    <div class="col-md-4">
      <form method="post" class="card border-0 shadow-sm rounded-4">
        <div class="card-body"><?= csrfField() ?>
          <h6 class="fw-bold mb-3">Add Slot (admin override)</h6>
          <input type="date" name="avail_date" min="<?= date('Y-m-d') ?>" required class="form-control mb-2">
          <input type="time" name="start_time" required class="form-control mb-2">
          <input type="time" name="end_time" required class="form-control mb-3">
          <button class="btn btn-primary w-100">Add</button>
        </div>
      </form>
    </div>
    <div class="col-md-8">
      <div class="card border-0 shadow-sm rounded-4"><div class="card-body">
        <h6 class="fw-bold mb-3">Upcoming Slots</h6>
        <div class="table-responsive">
          <table class="table small mb-0">
            <thead><tr><th>Date</th><th>From</th><th>To</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= formatDate($r['avail_date']) ?></td>
                <td><?= e(substr($r['start_time'],0,5)) ?></td>
                <td><?= e(substr($r['end_time'],0,5)) ?></td>
                <td class="text-end">
                  <form method="post" class="d-inline" onsubmit="return confirm('Delete?')">
                    <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">Del</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div></div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
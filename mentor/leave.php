<?php
require_once __DIR__ . '/../includes/auth-check.php';
requireRole(USER_MENTOR);
$mentor = db_one('SELECT * FROM mentors WHERE user_id=?',[$_SESSION['user_id']]);
$errors=[]; $success=null;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    $date = $_POST['leave_date'] ?? '';
    $st   = $_POST['start_time'] ?: null;
    $et   = $_POST['end_time']   ?: null;
    $reason = trim($_POST['reason'] ?? '');

    if (!$date) $errors[]='Date required.';
    elseif (strtotime($date) < strtotime(date('Y-m-d'))) $errors[]='Cannot request past leave.';
    elseif (strtotime($date.' '.($st ?: '00:00')) - time() < 24*3600) $errors[]='Leave must be requested at least 24 hours in advance.';
    else {
        db_insert('mentor_leaves', [
            'mentor_id'=>$mentor['id'],'leave_date'=>$date,
            'start_time'=>$st,'end_time'=>$et,'reason'=>$reason,'status'=>'pending'
        ]);
        // Notify admins
        $admins = db_all("SELECT id FROM users WHERE user_type IN ('ADMIN','SUPER_ADMIN')");
        foreach ($admins as $a) createNotification((int)$a['id'],'New leave request','Mentor requested leave on '.$date, SITE_URL.'/admin/mentors/leaves.php');
        createAuditLog('mentor.leave.request','mentor',$mentor['id']);
        $success='Leave request submitted.';
    }
}

$leaves = db_all('SELECT * FROM mentor_leaves WHERE mentor_id=? ORDER BY leave_date DESC LIMIT 20',[$mentor['id']]);
$pageTitle='Leave';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/mentor-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Leave Requests</h4>
  <div class="alert alert-info small">Leave must be requested <b>at least 24 hours</b> in advance whenever possible.</div>
  <?php if ($errors): ?><div class="alert alert-danger small py-2"><?php foreach($errors as $e) echo '• '.e($e).'<br>'; ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success small py-2"><?= e($success) ?></div><?php endif; ?>

  <div class="row g-3">
    <div class="col-md-4">
      <form method="post" class="card border-0 shadow-sm rounded-4"><div class="card-body"><?= csrfField() ?>
        <h6 class="fw-bold mb-3">Request Leave</h6>
        <input type="date" name="leave_date" min="<?= date('Y-m-d') ?>" required class="form-control mb-2">
        <input type="time" name="start_time" class="form-control mb-2" placeholder="From (optional)">
        <input type="time" name="end_time" class="form-control mb-2" placeholder="To (optional)">
        <textarea name="reason" rows="3" class="form-control mb-3" placeholder="Reason"></textarea>
        <button class="btn btn-primary w-100">Submit Request</button>
      </div></form>
    </div>
    <div class="col-md-8">
      <div class="card border-0 shadow-sm rounded-4"><div class="card-body">
        <h6 class="fw-bold mb-3">My Requests</h6>
        <div class="table-responsive">
          <table class="table small">
            <thead><tr><th>Date</th><th>Window</th><th>Reason</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($leaves as $l): ?>
              <tr>
                <td><?= formatDate($l['leave_date']) ?></td>
                <td><?= e(substr($l['start_time'] ?? '',0,5)) ?>–<?= e(substr($l['end_time'] ?? '',0,5)) ?></td>
                <td><?= e($l['reason']) ?></td>
                <td><span class="badge bg-<?= ['pending'=>'warning','approved'=>'success','rejected'=>'danger'][$l['status']] ?>"><?= e($l['status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div></div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
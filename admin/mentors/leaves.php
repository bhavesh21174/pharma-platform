<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('mentor.edit');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    $id = (int)$_POST['id']; $status = $_POST['status']==='approved' ? 'approved' : 'rejected';
    $leave = db_one('SELECT * FROM mentor_leaves WHERE id=?',[$id]);
    if ($leave) {
        db_update('mentor_leaves', ['status'=>$status,'approved_by'=>$_SESSION['user_id']], 'id = :id', ['id'=>$id]);
        createAuditLog('mentor.leave.'.$status,'mentor',$leave['mentor_id']);
        if ($status==='approved') {
            // Notify students with meetings in this window
            $affected = db_all("SELECT DISTINCT m.student_id FROM meetings m WHERE m.mentor_id=? AND m.meeting_date=? AND m.status='scheduled'",
                               [$leave['mentor_id'], $leave['leave_date']]);
            foreach ($affected as $a) {
                $u = db_one('SELECT user_id FROM students WHERE id=?',[$a['student_id']]);
                if ($u) createNotification((int)$u['user_id'],'Mentor on leave',
                    'Your mentor is unavailable on '.formatDate($leave['leave_date']).'. We will reassign or reschedule.');
            }
        }
    }
    redirect('leaves.php');
}

$rows = db_all("SELECT l.*, u.full_name FROM mentor_leaves l JOIN mentors m ON m.id=l.mentor_id JOIN users u ON u.id=m.user_id ORDER BY l.status='pending' DESC, l.created_at DESC");

$pageTitle='Mentor Leaves';
include __DIR__.'/../../includes/header.php';
include __DIR__.'/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Mentor Leaves</h4>
  <div class="card border-0 shadow-sm rounded-4"><div class="card-body">
    <div class="table-responsive">
      <table class="table align-middle small mb-0">
        <thead><tr><th>Mentor</th><th>Date</th><th>Window</th><th>Reason</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= e($r['full_name']) ?></td>
            <td><?= formatDate($r['leave_date']) ?></td>
            <td><?= e(substr($r['start_time'] ?? '',0,5)) ?> – <?= e(substr($r['end_time'] ?? '',0,5)) ?></td>
            <td><?= e($r['reason']) ?></td>
            <td><span class="badge bg-<?= ['pending'=>'warning','approved'=>'success','rejected'=>'danger'][$r['status']] ?>"><?= e($r['status']) ?></span></td>
            <td class="text-end">
              <?php if ($r['status']==='pending'): ?>
                <form method="post" class="d-inline"><?= csrfField() ?>
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <input type="hidden" name="status" value="approved">
                  <button class="btn btn-sm btn-success">Approve</button>
                </form>
                <form method="post" class="d-inline"><?= csrfField() ?>
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <input type="hidden" name="status" value="rejected">
                  <button class="btn btn-sm btn-outline-danger">Reject</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
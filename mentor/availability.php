<?php
require_once __DIR__ . '/../includes/auth-check.php';
requireRole(USER_MENTOR);
$mentor = db_one('SELECT * FROM mentors WHERE user_id=?',[$_SESSION['user_id']]);

$errors=[]; $success=null;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int)$_POST['id'];
        $row = db_one('SELECT * FROM mentor_availability WHERE id=? AND mentor_id=?',[$id,$mentor['id']]);
        if ($row) {
            // 24-hour rule: check booked meetings inside this slot
            $conflict = db_one("SELECT id FROM meetings WHERE mentor_id=? AND meeting_date=? AND start_time>=? AND start_time<? AND status='scheduled'",
                               [$mentor['id'], $row['avail_date'], $row['start_time'], $row['end_time']]);
            $slotTs = strtotime($row['avail_date'].' '.$row['start_time']);
            if ($slotTs - time() < 24*3600) {
                $errors[] = 'Cannot modify availability less than 24 hours before the session. Contact admin.';
            } elseif ($conflict) {
                $errors[] = 'A session is booked in that slot. Reschedule it first.';
            } else {
                db_query('DELETE FROM mentor_availability WHERE id=?',[$id]);
                createAuditLog('mentor.availability.delete','mentor',$mentor['id'],null,['id'=>$id]);
                $success='Slot removed.';
            }
        }
    } else {
        $date = $_POST['avail_date'] ?? '';
        $st   = $_POST['start_time'] ?? '';
        $et   = $_POST['end_time'] ?? '';
        if (!$date || !$st || !$et)             $errors[]='All fields required.';
        elseif ($st >= $et)                     $errors[]='End time must be after start.';
        elseif (strtotime($date) < strtotime(date('Y-m-d'))) $errors[]='Cannot add past dates.';
        else {
            // Overlap check
            $over = db_one("SELECT id FROM mentor_availability
                            WHERE mentor_id=? AND avail_date=? AND NOT (end_time <= ? OR start_time >= ?)",
                            [$mentor['id'], $date, $st, $et]);
            if ($over) $errors[] = 'Overlaps an existing slot.';
            else {
                db_insert('mentor_availability', [
                    'mentor_id'=>$mentor['id'],'avail_date'=>$date,'start_time'=>$st,'end_time'=>$et
                ]);
                createAuditLog('mentor.availability.add','mentor',$mentor['id'],null,['date'=>$date,'start'=>$st,'end'=>$et]);
                $success='Availability added.';
            }
        }
    }
}

$rows = db_all('SELECT * FROM mentor_availability WHERE mentor_id=? AND avail_date>=CURDATE() ORDER BY avail_date, start_time',[$mentor['id']]);
$pageTitle='Availability';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/mentor-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">My Availability</h4>
  <div class="alert alert-info small">
    <strong>Rule:</strong> Availability changes must be made <b>at least 24 hours</b> before the session.
  </div>
  <?php if ($errors): ?><div class="alert alert-danger small py-2"><?php foreach($errors as $e) echo '• '.e($e).'<br>'; ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success small py-2"><?= e($success) ?></div><?php endif; ?>

  <div class="row g-3">
    <div class="col-md-4">
      <form method="post" class="card border-0 shadow-sm rounded-4"><div class="card-body"><?= csrfField() ?>
        <h6 class="fw-bold mb-3">Add Slot</h6>
        <input type="date" name="avail_date" min="<?= date('Y-m-d') ?>" required class="form-control mb-2">
        <input type="time" name="start_time" required class="form-control mb-2">
        <input type="time" name="end_time" required class="form-control mb-3">
        <button class="btn btn-primary w-100">Add</button>
      </div></form>
    </div>
    <div class="col-md-8">
      <div class="card border-0 shadow-sm rounded-4"><div class="card-body">
        <h6 class="fw-bold mb-3">Upcoming Slots</h6>
        <div class="table-responsive">
          <table class="table small align-middle mb-0">
            <thead><tr><th>Date</th><th>From</th><th>To</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r):
              $soon = (strtotime($r['avail_date'].' '.$r['start_time']) - time()) < 24*3600;
            ?>
              <tr>
                <td><?= formatDate($r['avail_date'],'D, d M') ?></td>
                <td><?= e(substr($r['start_time'],0,5)) ?></td>
                <td><?= e(substr($r['end_time'],0,5)) ?></td>
                <td class="text-end">
                  <form method="post" class="d-inline" onsubmit="return confirm('Delete slot?')">
                    <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger" <?= $soon ? 'disabled title="Less than 24h"' : '' ?>>Del</button>
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
<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/performance.php';
requireRole(USER_MENTOR);
$mentor = db_one('SELECT * FROM mentors WHERE user_id=?',[$_SESSION['user_id']]);

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    $meetingId = (int)$_POST['meeting_id'];
    $status = $_POST['status'];
    $notes = trim($_POST['notes'] ?? '');
    $meetingUrl = trim($_POST['meeting_url'] ?? '');
    $recordingUrl = trim($_POST['recording_url'] ?? '');
    foreach (['Meeting' => $meetingUrl, 'Recording' => $recordingUrl] as $label => $url) {
      if ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true))) {
        setFlash('danger', $label . ' link must be a valid HTTP or HTTPS URL.');
        redirect('attendance.php');
      }
    }
    if (!in_array($status, ['present','absent','late','excused'], true)) {
      setFlash('danger', 'Choose a valid attendance status.');
      redirect('attendance.php');
    }
    $m = db_one('SELECT * FROM meetings WHERE id=? AND mentor_id=?',[$meetingId,$mentor['id']]);
    if ($m && in_array($status,['present','absent','late','excused'])) {
        $existing = db_one('SELECT id FROM attendance WHERE meeting_id=?',[$meetingId]);
        if ($existing) {
            db_update('attendance', ['status'=>$status,'notes'=>$notes,'marked_by'=>$_SESSION['user_id']], 'id = :id', ['id'=>$existing['id']]);
        } else {
            db_insert('attendance', [
                'student_id'=>$m['student_id'],'mentor_id'=>$mentor['id'],'course_id'=>$m['course_id'],
                'meeting_id'=>$m['id'],'att_date'=>$m['meeting_date'],'start_time'=>$m['start_time'],'end_time'=>$m['end_time'],
                'status'=>$status,'notes'=>$notes,'marked_by'=>$_SESSION['user_id'],
            ]);
        }
        db_update('meetings', ['status'=>'completed'], 'id = :id', ['id'=>$m['id']]);
        if ($meetingUrl !== '') db_update('meetings', ['meet_url'=>$meetingUrl], 'id = :id', ['id'=>$m['id']]);
        if ($recordingUrl !== '') db_update('meetings', ['recording_url'=>$recordingUrl], 'id = :id', ['id'=>$m['id']]);
        sync_student_performance((int)$m['student_id'], (int)$m['course_id']);
        setFlash('success','Attendance marked.');
    }
    redirect('attendance.php');
}

$rows = db_all("SELECT m.*, u.full_name AS student_name, c.name AS course_name,
                  a.status AS att_status, a.id AS att_id
                FROM meetings m
                JOIN students s ON s.id=m.student_id
                JOIN users u ON u.id=s.user_id
                JOIN courses c ON c.id=m.course_id
                LEFT JOIN attendance a ON a.meeting_id=m.id
                WHERE m.mentor_id=? AND m.meeting_date <= CURDATE()
                ORDER BY m.meeting_date DESC LIMIT 50",[$mentor['id']]);

$pageTitle='Attendance';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/mentor-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Mark Attendance</h4>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table small align-middle mb-0">
      <thead class="table-light"><tr><th>Date</th><th>Student</th><th>Course</th><th>Status</th><th>Attendance / recording</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= formatDate($r['meeting_date']) ?></td>
          <td><?= e($r['student_name']) ?></td>
          <td><?= e($r['course_name']) ?></td>
          <td>
            <?php if ($r['att_status']): ?>
              <span class="badge bg-success"><?= e($r['att_status']) ?></span>
            <?php else: ?>
              <span class="badge bg-warning text-dark">Not marked</span>
            <?php endif; ?>
          </td>
          <td>
            <form method="post" class="row g-1"><?= csrfField() ?>
              <input type="hidden" name="meeting_id" value="<?= (int)$r['id'] ?>">
              <div class="col-auto">
                <select name="status" class="form-select form-select-sm">
                  <?php foreach (['present','absent','late','excused'] as $st): ?>
                    <option <?= $r['att_status']===$st?'selected':'' ?>><?= $st ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3"><input name="notes" class="form-control form-control-sm" placeholder="Notes" value=""></div>
              <div class="col-md-4"><input type="url" name="meeting_url" class="form-control form-control-sm" placeholder="Google Meet / Zoom URL" value="<?= e($r['meet_url'] ?? '') ?>"></div>
              <div class="col-md-4"><input type="url" name="recording_url" class="form-control form-control-sm" placeholder="Session recording URL (optional)" value=""></div>
              <div class="col-auto"><button class="btn btn-sm btn-primary">Save</button></div>
            </form>
          </td>
        </tr>
      <?php endforeach; if (!$rows): ?><tr><td colspan="5" class="text-center text-muted py-4">No past sessions need attendance yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
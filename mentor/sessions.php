<?php
require_once __DIR__ . '/../includes/auth-check.php';
requireRole(USER_MENTOR);
$mentor = db_one('SELECT * FROM mentors WHERE user_id=?',[$_SESSION['user_id']]);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $meetingId = (int)($_POST['meeting_id'] ?? 0);
  $meetUrl = trim($_POST['meet_url'] ?? '');
  if (!filter_var($meetUrl, FILTER_VALIDATE_URL) || !in_array(parse_url($meetUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
    setFlash('danger', 'Meeting link must be a valid HTTP or HTTPS URL.');
  } else {
    $meeting = db_one("SELECT id FROM meetings WHERE id=? AND mentor_id=? AND status='scheduled'", [$meetingId, $mentor['id']]);
    if (!$meeting) {
      setFlash('danger', 'Scheduled session was not found.');
    } else {
      db_update('meetings', ['meet_url' => $meetUrl], 'id = :id', ['id' => $meetingId]);
      createNotification((int)db_one('SELECT user_id FROM students WHERE id=(SELECT student_id FROM meetings WHERE id=?)', [$meetingId])['user_id'],
        'Meeting link ready', 'Your mentor added the meeting link for your upcoming session.', SITE_URL . '/student/meetings.php');
      createAuditLog('meeting.link_updated', 'meeting', $meetingId);
      setFlash('success', 'Meeting link saved.');
    }
  }
  redirect(SITE_URL . '/mentor/sessions.php');
}
$rows = db_all("SELECT m.*, s.id AS sid, u.full_name AS student_name, u.email AS student_email, c.name AS course_name
                FROM meetings m
                JOIN students s ON s.id=m.student_id
                JOIN users u ON u.id=s.user_id
                JOIN courses c ON c.id=m.course_id
                WHERE m.mentor_id=? ORDER BY m.meeting_date DESC, m.start_time DESC LIMIT 100",[$mentor['id']]);

$pageTitle='Sessions';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/mentor-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">All Sessions</h4>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table align-middle small mb-0">
      <thead class="table-light"><tr><th>Date</th><th>Time</th><th>Student</th><th>Course</th><th>Status</th><th>Meet</th><th>Recording</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= formatDate($r['meeting_date']) ?></td>
          <td><?= e(substr($r['start_time'],0,5)) ?>–<?= e(substr($r['end_time'],0,5)) ?></td>
          <td><?= e($r['student_name']) ?></td>
          <td><?= e($r['course_name']) ?></td>
          <td><span class="badge bg-<?= $r['status']==='scheduled'?'success':($r['status']==='cancelled'?'danger':'secondary') ?>"><?= e($r['status']) ?></span></td>
          <td>
            <?php if ($r['meet_url']): ?>
              <a href="<?= e($r['meet_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-success">Join</a>
            <?php elseif ($r['status'] === 'scheduled'): ?>
              <form method="post" class="d-flex gap-1">
                <?= csrfField() ?><input type="hidden" name="meeting_id" value="<?= (int)$r['id'] ?>">
                <input type="url" name="meet_url" class="form-control form-control-sm" placeholder="Google Meet / Zoom URL" required aria-label="Meeting URL for <?= e($r['student_name']) ?>">
                <button class="btn btn-sm btn-outline-primary">Save</button>
              </form>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td><?php if ($r['recording_url']): ?><a href="<?= e($r['recording_url']) ?>" target="_blank" rel="noopener noreferrer">Watch</a><?php else: ?>—<?php endif; ?></td>
        </tr>
      <?php endforeach; if (!$rows): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No sessions scheduled yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
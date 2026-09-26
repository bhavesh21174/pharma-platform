<?php
require_once __DIR__ . '/../includes/auth-check.php';
requireRole(USER_STUDENT);
$student = db_one('SELECT * FROM students WHERE user_id=?',[$_SESSION['user_id']]);
$rows = db_all("SELECT m.*, u.full_name AS mentor_name, c.name AS course_name
                FROM meetings m
                JOIN mentors mt ON mt.id=m.mentor_id
                JOIN users u ON u.id=mt.user_id
                JOIN courses c ON c.id=m.course_id
                WHERE m.student_id=? ORDER BY m.meeting_date DESC, m.start_time DESC",[$student['id']]);
$pageTitle='Meetings';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">My Meetings</h4>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table align-middle small mb-0">
      <thead class="table-light"><tr><th>Date</th><th>Time</th><th>Mentor</th><th>Course</th><th>Status</th><th>Session</th><th>Recording</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= formatDate($r['meeting_date'],'D, d M') ?></td>
          <td><?= e(substr($r['start_time'],0,5)) ?>–<?= e(substr($r['end_time'],0,5)) ?></td>
          <td><?= e($r['mentor_name']) ?></td>
          <td><?= e($r['course_name']) ?></td>
          <td><span class="badge bg-secondary"><?= e($r['status']) ?></span></td>
          <td><?php if ($r['meet_url'] && $r['status']==='scheduled'): ?><a class="btn btn-sm btn-success" href="<?= e($r['meet_url']) ?>" target="_blank" rel="noopener noreferrer">Join</a><?php else: ?>—<?php endif; ?></td>
          <td><?php if ($r['recording_url']): ?><a href="<?= e($r['recording_url']) ?>" target="_blank" rel="noopener noreferrer">Watch</a><?php else: ?>—<?php endif; ?></td>
        </tr>
      <?php endforeach; if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">No sessions booked yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
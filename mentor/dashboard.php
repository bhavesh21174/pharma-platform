<?php
require_once __DIR__ . '/../includes/auth-check.php';
requireRole(USER_MENTOR);
$mentor = db_one('SELECT m.*, u.full_name,u.profile_photo FROM mentors m JOIN users u ON u.id=m.user_id WHERE m.user_id=?',[$_SESSION['user_id']]);
if (!$mentor) exit('Mentor profile missing.');

$stats = [
    'students'  => (int) db_one("SELECT COUNT(*) c FROM mentor_assignments WHERE mentor_id=? AND status='active'",[$mentor['id']])['c'],
    'today'     => (int) db_one("SELECT COUNT(*) c FROM meetings WHERE mentor_id=? AND meeting_date=CURDATE() AND status='scheduled'",[$mentor['id']])['c'],
    'upcoming'  => (int) db_one("SELECT COUNT(*) c FROM meetings WHERE mentor_id=? AND meeting_date>=CURDATE() AND status='scheduled'",[$mentor['id']])['c'],
    'pending_assignments' => (int) db_one("SELECT COUNT(*) c FROM assignment_submissions s
        JOIN assignments a ON a.id=s.assignment_id
        WHERE a.course_id IN (SELECT course_id FROM mentor_assignments WHERE mentor_id=? AND status='active')
          AND s.status IN ('submitted','under_review')",[$mentor['id']])['c'],
];

$today = db_all("SELECT m.*, s.id AS sid, us.full_name AS student_name, us.email AS student_email, c.name AS course_name
                 FROM meetings m
                 JOIN students s ON s.id=m.student_id
                 JOIN users us ON us.id=s.user_id
                 JOIN courses c ON c.id=m.course_id
                 WHERE m.mentor_id=? AND m.meeting_date=CURDATE() AND m.status='scheduled'
                 ORDER BY m.start_time",[$mentor['id']]);

$pageTitle='Mentor Dashboard';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/mentor-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Welcome, <?= e($mentor['full_name']) ?></h4>

  <div class="row g-3">
    <div class="col-6 col-md-3"><div class="stat-card"><div class="label">My Students</div><div class="value"><?= $stats['students'] ?></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="label">Today's Sessions</div><div class="value"><?= $stats['today'] ?></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="label">Upcoming</div><div class="value"><?= $stats['upcoming'] ?></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="label">Pending Reviews</div><div class="value"><?= $stats['pending_assignments'] ?></div></div></div>
  </div>

  <div class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-body">
      <h6 class="fw-bold mb-3">Today's Schedule</h6>
      <?php if (!$today): ?><div class="text-muted small">No sessions today.</div>
      <?php else: foreach ($today as $m): ?>
        <div class="border-bottom py-3 d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-semibold"><?= e($m['student_name']) ?> — <?= e($m['course_name']) ?></div>
            <div class="small text-muted"><?= e(substr($m['start_time'],0,5)) ?> – <?= e(substr($m['end_time'],0,5)) ?></div>
          </div>
          <?php if ($m['meet_url']): ?>
            <a class="btn btn-sm btn-success" href="<?= e($m['meet_url']) ?>" target="_blank"><i class="bi bi-camera-video"></i> Join</a>
          <?php else: ?>
            <span class="badge bg-warning text-dark">Meet pending</span>
          <?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
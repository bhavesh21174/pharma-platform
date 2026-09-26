<?php
require_once __DIR__ . '/../includes/auth-check.php';
requireRole(USER_STUDENT);
$student = db_one('SELECT * FROM students WHERE user_id=?',[$_SESSION['user_id']]);
if (!$student) exit('Student profile missing.');

$courses = db_all("SELECT e.*, c.name AS course_name, c.thumbnail FROM enrollments e
                   JOIN courses c ON c.id=e.course_id
                   WHERE e.student_id=? AND e.status='active'",[$student['id']]);
$nextMeeting = db_one("SELECT m.*, u.full_name AS mentor_name
                       FROM meetings m JOIN mentors mt ON mt.id=m.mentor_id JOIN users u ON u.id=mt.user_id
                       WHERE m.student_id=? AND m.meeting_date>=CURDATE() AND m.status='scheduled'
                       ORDER BY m.meeting_date, m.start_time LIMIT 1",[$student['id']]);
$attendancePct = db_one("SELECT
  ROUND(100 * SUM(status IN ('present','late','excused')) / NULLIF(COUNT(*),0), 2) AS pct
    FROM attendance WHERE student_id=?",[$student['id']])['pct'] ?? 0;
$pendingAllocation = (int)db_one("SELECT COUNT(*) AS c FROM enrollments WHERE student_id=? AND status='pending_admin'", [$student['id']])['c'];
$pendingAssignments = db_one("SELECT COUNT(*) c FROM assignments a
    WHERE a.course_id IN (SELECT course_id FROM enrollments WHERE student_id=? AND status='active')
      AND NOT EXISTS (SELECT 1 FROM assignment_submissions s WHERE s.assignment_id=a.id AND s.student_id=?)",[$student['id'],$student['id']])['c'];
$rank = db_one("SELECT rank_position, score FROM leaderboard WHERE student_id=? ORDER BY updated_at DESC LIMIT 1",[$student['id']]);

$pageTitle='Dashboard';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Hi, <?= e(currentUser()['full_name']) ?> 👋</h4>

  <div class="row g-3">
    <div class="col-6 col-md-3"><div class="stat-card"><div class="label">Active Courses</div><div class="value"><?= count($courses) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="label">Attendance</div><div class="value"><?= number_format((float)$attendancePct,1) ?>%</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="label">Pending Assignments</div><div class="value"><?= (int)$pendingAssignments ?></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="label">My Rank</div><div class="value"><?= $rank ? '#'.(int)$rank['rank_position'] : '—' ?></div></div></div>
    <?php if ($pendingAllocation): ?><div class="col-6 col-md-3"><div class="stat-card"><div class="label">Mentor Requests</div><div class="value"><?= $pendingAllocation ?></div></div></div><?php endif; ?>
  </div>

  <div class="row g-3 mt-3">
    <div class="col-md-7">
      <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body">
        <h6 class="fw-bold mb-3">My Courses</h6>
        <?php if (!$courses): ?>
          <div class="text-muted small">You're not enrolled in any course yet. <a href="<?= SITE_URL ?>/public/courses.php">Browse courses</a></div>
        <?php else: foreach ($courses as $c): ?>
          <div class="border-bottom py-2">
            <div class="fw-semibold"><?= e($c['course_name']) ?></div>
            <div class="progress mt-2" style="height:8px">
              <div class="progress-bar" style="width:<?= (float)$c['progress'] ?>%"></div>
            </div>
            <div class="small text-muted mt-1"><?= number_format((float)$c['progress'],1) ?>% complete</div>
          </div>
        <?php endforeach; endif; ?>
      </div></div>
    </div>

    <div class="col-md-5">
      <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body">
        <h6 class="fw-bold mb-3">Next Session</h6>
        <?php if (!$nextMeeting): ?>
          <div class="text-muted small">No upcoming sessions.</div>
        <?php else: ?>
          <div class="fw-semibold"><?= e($nextMeeting['mentor_name']) ?></div>
          <div class="small text-muted"><?= formatDate($nextMeeting['meeting_date'],'D, d M Y') ?> · <?= e(substr($nextMeeting['start_time'],0,5)) ?></div>
          <?php if ($nextMeeting['meet_url']): ?>
            <a href="<?= e($nextMeeting['meet_url']) ?>" target="_blank" class="btn btn-success mt-3"><i class="bi bi-camera-video"></i> Join Google Meet</a>
          <?php endif; ?>
        <?php endif; ?>
      </div></div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../includes/auth-check.php';
requireRole(USER_SUPER_ADMIN, USER_ADMIN, USER_COURSE_MGR, USER_HR);

$pageTitle = 'Admin Dashboard';
$stats = [
  'students'      => db_one("SELECT COUNT(*) c FROM users WHERE user_type='STUDENT'")['c'],
  'mentors'       => db_one("SELECT COUNT(*) c FROM users WHERE user_type='MENTOR'")['c'],
  'courses'       => db_one("SELECT COUNT(*) c FROM courses WHERE status='published'")['c'],
  'revenue'       => db_one("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE status='successful'")['s'],
];
$upcoming = db_all("SELECT m.*, u.full_name AS mentor_name, us.full_name AS student_name
                    FROM meetings m
                    JOIN mentors mt ON mt.id=m.mentor_id
                    JOIN users u ON u.id=mt.user_id
                    JOIN students s ON s.id=m.student_id
                    JOIN users us ON us.id=s.user_id
                    WHERE m.meeting_date >= CURDATE() AND m.status='scheduled'
                    ORDER BY m.meeting_date, m.start_time LIMIT 5");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin-sidebar.php';
?>
<main class="app-main">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Admin Dashboard</h4>
  </div>

  <div class="row g-3">
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="label">Students</div><div class="value"><?= (int)$stats['students'] ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="label">Mentors</div><div class="value"><?= (int)$stats['mentors'] ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="label">Active Courses</div><div class="value"><?= (int)$stats['courses'] ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="label">Revenue</div><div class="value"><?= formatCurrency((float)$stats['revenue']) ?></div></div></div>
  </div>

  <div class="card border-0 shadow-sm mt-4 rounded-4">
    <div class="card-body">
      <h6 class="fw-bold mb-3">Upcoming Meetings</h6>
      <?php if (!$upcoming): ?>
        <div class="text-muted small">No upcoming meetings.</div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table align-middle small mb-0">
          <thead><tr><th>Date</th><th>Time</th><th>Student</th><th>Mentor</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($upcoming as $m): ?>
            <tr>
              <td><?= formatDate($m['meeting_date']) ?></td>
              <td><?= e(substr($m['start_time'],0,5)) ?> – <?= e(substr($m['end_time'],0,5)) ?></td>
              <td><?= e($m['student_name']) ?></td>
              <td><?= e($m['mentor_name']) ?></td>
              <td><a class="btn btn-sm btn-outline-primary" href="<?= SITE_URL ?>/admin/meetings/view.php?id=<?= (int)$m['id'] ?>">View</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
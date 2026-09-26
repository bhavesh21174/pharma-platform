<?php
require_once __DIR__ . '/../includes/auth-check.php';
requireRole(USER_STUDENT);
$student = db_one('SELECT * FROM students WHERE user_id=?',[$_SESSION['user_id']]);
$rows = db_all("SELECT e.*, c.name AS course_name, c.thumbnail, c.duration_weeks, c.duration_days,
                       u.full_name AS mentor_name, requested.full_name AS requested_mentor_name
                FROM enrollments e
                JOIN courses c ON c.id=e.course_id
                LEFT JOIN mentors mt ON mt.id=e.mentor_id
                LEFT JOIN users u ON u.id=mt.user_id
                LEFT JOIN mentors rm ON rm.id=e.requested_mentor_id
                LEFT JOIN users requested ON requested.id=rm.user_id
                WHERE e.student_id=? ORDER BY e.id DESC",[$student['id']]);
$pageTitle='My Courses';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">My Courses</h4>
  <div class="row g-3">
    <?php foreach ($rows as $r): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body d-flex flex-column">
            <h6 class="fw-semibold"><?= e($r['course_name']) ?></h6>
            <div class="small text-muted mb-2"><?= (int)$r['duration_days'] ?> days · Mentor: <?= e($r['mentor_name'] ?? $r['requested_mentor_name'] ?? 'Awaiting admin allocation') ?></div>
            <div class="small mb-2"><span class="badge bg-<?= $r['status']==='active'?'success':($r['status']==='pending_admin'?'warning text-dark':'secondary') ?>"><?= e($r['status']==='pending_admin'?'Awaiting mentor allocation':$r['status']) ?></span></div>
            <?php if ($r['total_fee'] > 0): ?><div class="small text-muted mb-2">Paid <?= formatCurrency((float)$r['paid_amount']) ?> of <?= formatCurrency((float)$r['total_fee']) ?></div><?php endif; ?>
            <div class="progress mb-2" style="height:8px"><div class="progress-bar" style="width:<?= (float)$r['progress'] ?>%"></div></div>
            <div class="small text-muted mb-3"><?= number_format((float)$r['progress'],1) ?>% complete</div>
            <?php if ($r['status'] === 'active'): ?>
              <a class="btn btn-primary mt-auto" href="course-view.php?id=<?= (int)$r['course_id'] ?>">Open Course</a>
              <a class="btn btn-outline-primary mt-2" href="my-mentor.php?course_id=<?= (int)$r['course_id'] ?>">Mentor and sessions</a>
            <?php elseif ($r['status'] === 'pending_admin'): ?>
              <div class="small text-muted mt-auto">Your first 50% payment is recorded. The admin team is assigning your mentor.</div>
            <?php elseif (in_array($r['status'], ['completed'], true)): ?>
              <a class="btn btn-outline-primary mt-auto" href="course-view.php?id=<?= (int)$r['course_id'] ?>">Review course</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
      <div class="col-12"><div class="card"><div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><h5 class="fw-bold mb-1">Your learning starts here</h5><p class="text-muted mb-0">Enroll in a course to see lessons, mentoring, and progress in this panel.</p></div>
        <a class="btn btn-primary" href="<?= SITE_URL ?>/public/courses.php">Browse courses</a>
      </div></div></div>
    <?php endif; ?>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/performance.php';
requireRole(USER_STUDENT);
$student = db_one('SELECT * FROM students WHERE user_id=?',[$_SESSION['user_id']]);
$courses = db_all("SELECT c.id, c.name FROM enrollments e JOIN courses c ON c.id=e.course_id
                   WHERE e.student_id=? AND e.status='active'",[$student['id']]);

$data = [];
foreach ($courses as $c) {
    sync_student_performance((int)$student['id'], (int)$c['id']);
    $p = db_one('SELECT * FROM student_performance WHERE student_id=? AND course_id=?',[$student['id'],$c['id']]);
    $data[] = ['course'=>$c,'perf'=>$p];
}
$pageTitle='Performance';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">My Performance</h4>
  <?php foreach ($data as $d): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-3"><div class="card-body">
      <h6 class="fw-bold mb-3"><?= e($d['course']['name']) ?></h6>
      <div class="row g-3">
        <div class="col-6 col-md-3"><div class="stat-card"><div class="label">Assignments</div><div class="value"><?= number_format((float)$d['perf']['assignment_score'],1) ?>%</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="label">Assessments</div><div class="value"><?= number_format((float)$d['perf']['assessment_score'],1) ?>%</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="label">Attendance</div><div class="value"><?= number_format((float)$d['perf']['attendance_pct'],1) ?>%</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="label">Overall</div><div class="value text-primary"><?= number_format((float)$d['perf']['overall_score'],1) ?>%</div></div></div>
      </div>
    </div></div>
  <?php endforeach; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
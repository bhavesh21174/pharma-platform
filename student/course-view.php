<?php
require_once __DIR__ . '/../includes/auth-check.php';
requireRole(USER_STUDENT);
$course_id = (int)($_GET['id'] ?? 0);
$student = db_one('SELECT * FROM students WHERE user_id=?',[$_SESSION['user_id']]);
$enroll = db_one("SELECT * FROM enrollments WHERE student_id=? AND course_id=? AND status IN ('active','completed')",[$student['id'],$course_id]);
if (!$enroll) { http_response_code(403); exit('Not enrolled.'); }

$course  = db_one('SELECT * FROM courses WHERE id=?',[$course_id]);
$modules = db_all('SELECT * FROM course_modules WHERE course_id=? ORDER BY position',[$course_id]);

$pageTitle=$course['name'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-1"><?= e($course['name']) ?></h4>
  <p class="text-muted small"><?= e($course['short_desc']) ?></p>

  <?php foreach ($modules as $m):
    $lessons = db_all('SELECT * FROM lessons WHERE module_id=? AND status="published" ORDER BY position',[$m['id']]);
  ?>
    <div class="card border-0 shadow-sm rounded-4 mb-3">
      <div class="card-body">
        <h6 class="fw-bold mb-2"><?= e($m['title']) ?></h6>
        <p class="small text-muted"><?= e($m['description']) ?></p>
        <ul class="list-group list-group-flush">
          <?php foreach ($lessons as $l): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
              <div>
                <div class="fw-semibold small"><?= e($l['title']) ?></div>
                <div class="small text-muted"><?= (int)$l['duration_minutes'] ?> min</div>
              </div>
              <?php if ($l['video_url']): ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= e($l['video_url']) ?>" target="_blank">Watch</a>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endforeach; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
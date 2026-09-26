<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requireRole(USER_STUDENT);
$student = db_one('SELECT * FROM students WHERE user_id=?',[$_SESSION['user_id']]);

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    db_insert('feedback', [
        'student_id'=>$student['id'],
        'mentor_id'=>$_POST['mentor_id'] ? (int)$_POST['mentor_id'] : null,
        'course_id'=>$_POST['course_id'] ? (int)$_POST['course_id'] : null,
        'type'=>$_POST['type'] ?? 'mentor',
        'rating'=>(int)($_POST['rating'] ?? 5),
        'comments'=>trim($_POST['comments'] ?? ''),
        'suggestions'=>trim($_POST['suggestions'] ?? ''),
    ]);
    createAuditLog('feedback.create','feedback');
    setFlash('success','Thank you for your feedback.');
    redirect('feedback.php');
}

$mentors = db_all("SELECT DISTINCT m.id, u.full_name FROM mentor_assignments ma
                   JOIN mentors m ON m.id=ma.mentor_id JOIN users u ON u.id=m.user_id
                   WHERE ma.student_id=? AND ma.status='active'",[$student['id']]);
$courses = db_all("SELECT DISTINCT c.id, c.name FROM enrollments e JOIN courses c ON c.id=e.course_id
                   WHERE e.student_id=? AND e.status='active'",[$student['id']]);
$mine = db_all("SELECT f.*, c.name AS course_name FROM feedback f LEFT JOIN courses c ON c.id=f.course_id
                WHERE f.student_id=? ORDER BY f.id DESC LIMIT 20",[$student['id']]);

$pageTitle='Feedback';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Give Feedback</h4>
  <form method="post" class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body"><?= csrfField() ?>
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Type</label>
        <select name="type" class="form-select">
          <option value="mentor">Mentor</option><option value="session">Session</option>
          <option value="course">Course</option><option value="platform">Platform</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Mentor (optional)</label>
        <select name="mentor_id" class="form-select">
          <option value="">—</option>
          <?php foreach ($mentors as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['full_name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Course (optional)</label>
        <select name="course_id" class="form-select">
          <option value="">—</option>
          <?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold">Rating</label>
        <select name="rating" class="form-select">
          <?php for ($i=5;$i>=1;$i--): ?><option value="<?= $i ?>"><?= str_repeat('★',$i) ?></option><?php endfor; ?>
        </select>
      </div>
      <div class="col-md-9">
        <label class="form-label small fw-semibold">Comments</label>
        <input name="comments" class="form-control">
      </div>
      <div class="col-12">
        <label class="form-label small fw-semibold">Suggestions</label>
        <textarea name="suggestions" rows="3" class="form-control"></textarea>
      </div>
    </div>
    <div class="mt-3"><button class="btn btn-primary px-4">Submit</button></div>
  </div></form>

  <h6 class="fw-bold">Recent</h6>
  <?php foreach ($mine as $f): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-2"><div class="card-body">
      <div class="small text-muted"><?= e($f['type']) ?> · <?= str_repeat('★',(int)$f['rating']) ?> · <?= formatDate($f['created_at']) ?></div>
      <div><?= e($f['comments']) ?></div>
    </div></div>
  <?php endforeach; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
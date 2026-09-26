<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('student.edit');
$student_id = (int)($_GET['student_id'] ?? 0);
$s = db_one('SELECT s.*, u.full_name FROM students s JOIN users u ON u.id=s.user_id WHERE s.id=?',[$student_id]);
if (!$s) exit('Not found');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    $course_id = (int)$_POST['course_id'];
    $mentor_id = $_POST['mentor_id'] ? (int)$_POST['mentor_id'] : null;
    if ($course_id) {
        $existing = db_one('SELECT id FROM enrollments WHERE student_id=? AND course_id=?',[$student_id,$course_id]);
        if ($existing) {
            db_update('enrollments', ['status'=>'active','mentor_id'=>$mentor_id,'enrolled_at'=>date('Y-m-d H:i:s')], 'id = :id', ['id'=>$existing['id']]);
        } else {
            db_insert('enrollments', [
                'student_id'=>$student_id,'course_id'=>$course_id,'mentor_id'=>$mentor_id,
                'status'=>'active','enrolled_at'=>date('Y-m-d H:i:s')
            ]);
        }
        if ($mentor_id) {
            db_insert('mentor_assignments', [
                'mentor_id'=>$mentor_id,'student_id'=>$student_id,'course_id'=>$course_id,
                'assigned_by'=>$_SESSION['user_id'],'status'=>'active'
            ]);
        }
        $u = db_one('SELECT user_id FROM students WHERE id=?',[$student_id]);
        if ($u) createNotification((int)$u['user_id'],'Enrollment activated','You have been enrolled in a course.');
        createAuditLog('student.manual_enroll','student',$student_id,null,['course_id'=>$course_id]);
        setFlash('success','Enrolled.');
        redirect('view.php?id='.$student_id);
    }
}

$courses = db_all("SELECT id,name FROM courses WHERE status='published'");
$mentors = db_all("SELECT m.id, u.full_name FROM mentors m JOIN users u ON u.id=m.user_id WHERE m.status='active'");

$pageTitle='Enroll Student';
include __DIR__.'/../../includes/header.php';
include __DIR__.'/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Enroll <?= e($s['full_name']) ?></h4>
  <form method="post" class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><?= csrfField() ?>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Course *</label>
        <select name="course_id" required class="form-select">
          <option value="">Select course...</option>
          <?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Assign Mentor (optional)</label>
        <select name="mentor_id" class="form-select">
          <option value="">— None —</option>
          <?php foreach ($mentors as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['full_name']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="mt-4"><button class="btn btn-primary px-4">Enroll</button></div>
  </div></form>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
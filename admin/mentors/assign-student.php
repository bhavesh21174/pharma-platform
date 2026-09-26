<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('mentor.edit');

$mentor_id = (int)($_GET['mentor_id'] ?? $_POST['mentor_id'] ?? 0);
$mentor = db_one('SELECT m.*, u.full_name FROM mentors m JOIN users u ON u.id=m.user_id WHERE m.id=?', [$mentor_id]);
if (!$mentor) exit('Mentor not found');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $student_id = (int)($_POST['student_id'] ?? 0);
    $course_id  = (int)($_POST['course_id'] ?? 0);
    if ($student_id && $course_id) {
        $existing = db_one("SELECT id FROM mentor_assignments WHERE student_id=? AND course_id=? AND status='active'",[$student_id,$course_id]);
        if ($existing) { setFlash('warning','Student already assigned for this course.'); }
        else {
            db_insert('mentor_assignments', [
                'mentor_id'=>$mentor_id,'student_id'=>$student_id,'course_id'=>$course_id,
                'assigned_by'=>$_SESSION['user_id'],'status'=>'active',
            ]);
            // Update enrollment mentor
            db_query("UPDATE enrollments SET mentor_id=? WHERE student_id=? AND course_id=? AND mentor_id IS NULL",
                     [$mentor_id,$student_id,$course_id]);
            $stu = db_one('SELECT user_id FROM students WHERE id=?',[$student_id]);
            if ($stu) createNotification((int)$stu['user_id'],'Mentor assigned','A mentor has been assigned to you.');
            createAuditLog('mentor.assign','mentor',$mentor_id,null,['student_id'=>$student_id,'course_id'=>$course_id]);
            setFlash('success','Student assigned.');
        }
    }
    redirect('view.php?id='.$mentor_id);
}

// Available students: enrolled in some course without an active mentor assignment for it
$students = db_all("SELECT DISTINCT s.id, u.full_name, u.email, e.course_id, c.name AS course_name
                    FROM students s
                    JOIN users u ON u.id=s.user_id
                    JOIN enrollments e ON e.student_id=s.id AND e.status='active'
                    JOIN courses c ON c.id=e.course_id
                    LEFT JOIN mentor_assignments ma ON ma.student_id=s.id AND ma.course_id=e.course_id AND ma.status='active'
                    WHERE ma.id IS NULL
                    ORDER BY u.full_name");

$pageTitle = 'Assign Student';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Assign Student → <?= e($mentor['full_name']) ?></h4>
  <form method="post" class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4"><?= csrfField() ?>
      <input type="hidden" name="mentor_id" value="<?= (int)$mentor_id ?>">
      <div class="mb-3">
        <label class="form-label small fw-semibold">Student & Course</label>
        <select name="student_course" required class="form-select" onchange="
          const [sid,cid]=this.value.split('|');
          document.querySelector('[name=student_id]').value=sid;
          document.querySelector('[name=course_id]').value=cid;">
          <option value="">Choose student + course...</option>
          <?php foreach ($students as $s): ?>
            <option value="<?= (int)$s['id'] ?>|<?= (int)$s['course_id'] ?>">
              <?= e($s['full_name']) ?> — <?= e($s['course_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <input type="hidden" name="student_id" value="">
      <input type="hidden" name="course_id" value="">
      <button class="btn btn-primary px-4">Assign</button>
      <a href="view.php?id=<?= (int)$mentor_id ?>" class="btn btn-link">Cancel</a>
    </div>
  </form>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
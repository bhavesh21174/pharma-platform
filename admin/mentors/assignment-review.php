<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/performance.php';
requireRole(USER_MENTOR);
$mentor = db_one('SELECT * FROM mentors WHERE user_id=?',[$_SESSION['user_id']]);
$id = (int)($_GET['id'] ?? 0);
$a  = db_one('SELECT a.*, c.name AS course_name FROM assignments a JOIN courses c ON c.id=a.course_id WHERE a.id=?',[$id]);
if (!$a) exit('Not found');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    $sid = (int)$_POST['submission_id'];
    // ensure it belongs to a mentor-assigned student
    $check = db_one("SELECT s.* FROM assignment_submissions s
                     JOIN mentor_assignments ma ON ma.student_id=s.student_id AND ma.course_id=? AND ma.mentor_id=? AND ma.status='active'
                     WHERE s.id=?", [$a['course_id'], $mentor['id'], $sid]);
    if (!$check) exit('403');

    db_update('assignment_submissions', [
        'marks'=>$_POST['marks'] !== '' ? (float)$_POST['marks'] : null,
        'feedback'=>trim($_POST['feedback'] ?? ''),
        'status'=>$_POST['status'] ?? 'checked',
        'reviewed_by'=>$_SESSION['user_id'],'reviewed_at'=>date('Y-m-d H:i:s'),
    ], 'id = :id', ['id'=>$sid]);

    $stu = db_one('SELECT user_id FROM students WHERE id=?',[(int)$check['student_id']]);
    if ($stu) createNotification((int)$stu['user_id'],'Assignment reviewed','Check your assignments.',SITE_URL.'/admin/students/assignments.php');
    sync_student_performance((int)$check['student_id'], (int)$a['course_id']);
    setFlash('success','Review saved.');
    redirect('assignment-review.php?id='.$id);
}

$subs = db_all("SELECT s.*, u.full_name AS student_name
                FROM assignment_submissions s
                JOIN students st ON st.id=s.student_id
                JOIN users u ON u.id=st.user_id
                JOIN mentor_assignments ma ON ma.student_id=st.id AND ma.course_id=? AND ma.mentor_id=? AND ma.status='active'
                WHERE s.assignment_id=?", [$a['course_id'], $mentor['id'], $id]);

$pageTitle='Review Submission';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/mentor-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-1"><?= e($a['title']) ?></h4>
  <p class="text-muted small">Max <?= (int)$a['max_marks'] ?> marks</p>
  <?php foreach ($subs as $s): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-3"><div class="card-body">
      <div class="fw-semibold mb-1"><?= e($s['student_name']) ?></div>
      <div class="small text-muted mb-2"><?= formatDate($s['submitted_at'],'d M Y H:i') ?></div>
      <?php if ($s['text_response']): ?><div class="border rounded p-2 small bg-light mb-2"><?= nl2br(e($s['text_response'])) ?></div><?php endif; ?>
      <?php if ($s['file_path']): ?><a class="btn btn-sm btn-outline-primary mb-2" href="<?= UPLOAD_URL.'/'.$s['file_path'] ?>" target="_blank">View File</a><?php endif; ?>
      <form method="post" class="row g-2 mt-2"><?= csrfField() ?>
        <input type="hidden" name="submission_id" value="<?= (int)$s['id'] ?>">
        <div class="col-md-2"><input type="number" step="0.01" max="<?= (int)$a['max_marks'] ?>" name="marks" value="<?= e($s['marks']) ?>" class="form-control form-control-sm" placeholder="Marks"></div>
        <div class="col-md-4"><input name="feedback" value="<?= e($s['feedback']) ?>" class="form-control form-control-sm" placeholder="Feedback"></div>
        <div class="col-md-3">
          <select name="status" class="form-select form-select-sm">
            <?php foreach (['submitted','under_review','checked','resubmit'] as $st): ?>
              <option <?= $s['status']===$st?'selected':'' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3"><button class="btn btn-sm btn-primary w-100">Save</button></div>
      </form>
    </div></div>
  <?php endforeach; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/performance.php';
requirePermission('assignment.review');

$id = (int)($_GET['id'] ?? 0);
$a  = db_one('SELECT a.*, c.name AS course_name FROM assignments a JOIN courses c ON c.id=a.course_id WHERE a.id=?',[$id]);
if (!$a) exit('Not found');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $sid = (int)$_POST['submission_id'];
    $marks = $_POST['marks'] !== '' ? (float)$_POST['marks'] : null;
    $feedback = trim($_POST['feedback'] ?? '');
    $status = $_POST['status'] ?? 'checked';
    if ($marks !== null && $marks > (float)$a['max_marks']) $marks = (float)$a['max_marks'];
    db_update('assignment_submissions', [
        'marks'=>$marks,'feedback'=>$feedback,'status'=>$status,
        'reviewed_by'=>$_SESSION['user_id'],'reviewed_at'=>date('Y-m-d H:i:s'),
    ], 'id = :id', ['id'=>$sid]);

    $sub = db_one('SELECT s.*, st.user_id FROM assignment_submissions s JOIN students st ON st.id=s.student_id WHERE s.id=?',[$sid]);
    if ($sub) {
        createNotification((int)$sub['user_id'], 'Assignment reviewed',
          'Your submission was reviewed. Marks: '.($marks ?? '—'), SITE_URL.'/admin/students/assignment-view.php?id='.(int)$a['id']);
        sync_student_performance((int)$sub['student_id'], (int)$a['course_id']);
    }
    setFlash('success','Submission reviewed.');
    redirect('submissions.php?id='.$id);
}

$subs = db_all("SELECT s.*, u.full_name AS student_name, u.email
                FROM assignment_submissions s
                JOIN students st ON st.id=s.student_id
                JOIN users u ON u.id=st.user_id
                WHERE s.assignment_id=?
                ORDER BY s.submitted_at DESC",[$id]);

$pageTitle = 'Submissions';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-1"><?= e($a['title']) ?></h4>
  <p class="text-muted small"><?= e($a['course_name']) ?> · Max <?= (int)$a['max_marks'] ?> marks · Due <?= formatDate($a['due_date'],'d M Y H:i') ?></p>

  <?php if (!$subs): ?>
    <div class="alert alert-info">No submissions yet.</div>
  <?php else: foreach ($subs as $s): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-3">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <div class="fw-semibold"><?= e($s['student_name']) ?></div>
            <div class="small text-muted">Submitted <?= formatDate($s['submitted_at'],'d M Y H:i') ?></div>
          </div>
          <span class="badge bg-<?= ['submitted'=>'info','checked'=>'success','resubmit'=>'warning','late'=>'danger'][$s['status']] ?? 'secondary' ?>"><?= e($s['status']) ?></span>
        </div>

        <?php if ($s['text_response']): ?>
          <div class="border rounded p-2 small bg-light mb-2"><?= nl2br(e($s['text_response'])) ?></div>
        <?php endif; ?>
        <?php if ($s['file_path']): ?>
          <a href="<?= UPLOAD_URL.'/'.$s['file_path'] ?>" target="_blank" class="btn btn-sm btn-outline-primary mb-2">
            <i class="bi bi-paperclip"></i> View File
          </a>
        <?php endif; ?>

        <form method="post" class="row g-2 mt-2">
          <?= csrfField() ?>
          <input type="hidden" name="submission_id" value="<?= (int)$s['id'] ?>">
          <div class="col-md-2"><input type="number" step="0.01" max="<?= (int)$a['max_marks'] ?>" name="marks" value="<?= e($s['marks']) ?>" class="form-control form-control-sm" placeholder="Marks"></div>
          <div class="col-md-4"><input name="feedback" value="<?= e($s['feedback']) ?>" class="form-control form-control-sm" placeholder="Feedback"></div>
          <div class="col-md-3">
            <select name="status" class="form-select form-select-sm">
              <?php foreach (['submitted','under_review','checked','resubmit','late'] as $st): ?>
                <option <?= $s['status']===$st?'selected':'' ?>><?= $st ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3"><button class="btn btn-sm btn-primary w-100">Save Review</button></div>
        </form>
      </div>
    </div>
  <?php endforeach; endif; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
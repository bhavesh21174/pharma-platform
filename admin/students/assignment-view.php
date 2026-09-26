<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requireRole(USER_STUDENT);
$student = db_one('SELECT * FROM students WHERE user_id=?',[$_SESSION['user_id']]);
$id = (int)($_GET['id'] ?? 0);
$a  = db_one('SELECT a.*, c.name AS course_name FROM assignments a JOIN courses c ON c.id=a.course_id WHERE a.id=?',[$id]);
if (!$a) exit('Not found');

// enrollment guard
$enrolled = db_one("SELECT id FROM enrollments WHERE student_id=? AND course_id=? AND status='active'",
                    [$student['id'], $a['course_id']]);
if (!$enrolled) { http_response_code(403); exit('Not enrolled.'); }

$sub = db_one('SELECT * FROM assignment_submissions WHERE assignment_id=? AND student_id=?', [$id, $student['id']]);

$pageTitle = $a['title'];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-1"><?= e($a['title']) ?></h4>
  <p class="text-muted small"><?= e($a['course_name']) ?> · Max <?= (int)$a['max_marks'] ?> marks · Due <?= formatDate($a['due_date'],'d M Y H:i') ?></p>

  <div class="card border-0 shadow-sm rounded-4 mb-3"><div class="card-body">
    <h6 class="fw-bold">Description</h6>
    <p><?= nl2br(e($a['description'])) ?></p>
    <?php if ($a['instructions']): ?>
      <h6 class="fw-bold mt-3">Instructions</h6>
      <p><?= nl2br(e($a['instructions'])) ?></p>
    <?php endif; ?>
    <?php if ($a['attachment']): ?>
      <a class="btn btn-sm btn-outline-primary" href="<?= UPLOAD_URL.'/'.$a['attachment'] ?>" target="_blank">
        <i class="bi bi-paperclip"></i> Attachment
      </a>
    <?php endif; ?>
  </div></div>

  <?php if ($sub): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-3"><div class="card-body">
      <h6 class="fw-bold">My Submission</h6>
      <div class="small text-muted mb-2">Submitted <?= formatDate($sub['submitted_at'],'d M Y H:i') ?> · Status: <span class="badge bg-secondary"><?= e($sub['status']) ?></span></div>
      <?php if ($sub['text_response']): ?><div class="border rounded p-2 small bg-light mb-2"><?= nl2br(e($sub['text_response'])) ?></div><?php endif; ?>
      <?php if ($sub['file_path']): ?><a class="btn btn-sm btn-outline-primary mb-2" href="<?= UPLOAD_URL.'/'.$sub['file_path'] ?>" target="_blank">View File</a><?php endif; ?>
      <?php if ($sub['marks'] !== null): ?>
        <div class="alert alert-success small py-2 mt-2 mb-0">
          <strong>Marks:</strong> <?= e($sub['marks']) ?> / <?= (int)$a['max_marks'] ?>
          <?php if ($sub['feedback']): ?><br><strong>Feedback:</strong> <?= e($sub['feedback']) ?><?php endif; ?>
        </div>
      <?php endif; ?>
    </div></div>
  <?php endif; ?>

  <?php if (!$sub || $sub['status'] === 'resubmit'): ?>
    <div class="card border-0 shadow-sm rounded-4"><div class="card-body">
      <h6 class="fw-bold mb-3"><?= $sub ? 'Resubmit' : 'Submit' ?> Assignment</h6>
      <form method="post" action="assignment-submit.php" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="assignment_id" value="<?= (int)$a['id'] ?>">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Text Response</label>
          <textarea name="text_response" rows="5" class="form-control"></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Upload File (PDF/DOC/IMG/ZIP, max 10MB)</label>
          <input type="file" name="submission_file" class="form-control">
        </div>
        <button class="btn btn-primary px-4">Submit</button>
      </form>
    </div></div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
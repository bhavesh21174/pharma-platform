<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/performance.php';
requireRole(USER_MENTOR);
$mentor = db_one('SELECT * FROM mentors WHERE user_id=?',[$_SESSION['user_id']]);
$id = (int)($_GET['id'] ?? 0);
$a = db_one('SELECT * FROM assessments WHERE id=?',[$id]);
if (!$a) exit('Not found');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    $attemptId = (int)$_POST['attempt_id'];
    $awarded = $_POST['awarded'] ?? [];
    $total = 0;

    foreach ($awarded as $answerId => $marks) {
        db_update('assessment_answers', ['awarded'=>(float)$marks], 'id = :id', ['id'=>(int)$answerId]);
        $total += (float)$marks;
    }
    // Add existing auto-awarded marks from other questions
    $auto = db_one('SELECT COALESCE(SUM(awarded),0) s FROM assessment_answers WHERE attempt_id=?',[$attemptId])['s'] ?? 0;
    $total = $auto;

    $pct = $a['total_marks'] > 0 ? round(($total / (float)$a['total_marks']) * 100, 2) : 0;
    $result = $pct >= 40 ? 'pass' : 'fail';
    db_update('assessment_attempts', ['status'=>'evaluated','score'=>$total,'percentage'=>$pct], 'id = :id', ['id'=>$attemptId]);

    $att = db_one('SELECT * FROM assessment_attempts WHERE id=?',[$attemptId]);
    if ($att) {
        db_query('DELETE FROM assessment_results WHERE attempt_id=?',[$attemptId]);
        db_insert('assessment_results', [
            'attempt_id'=>$attemptId,'assessment_id'=>$id,'student_id'=>$att['student_id'],
            'score'=>$total,'total'=>(int)$a['total_marks'],'percentage'=>$pct,'result'=>$result,
        ]);
        sync_student_performance((int)$att['student_id'], (int)$a['course_id']);
        $stu = db_one('SELECT user_id FROM students WHERE id=?',[(int)$att['student_id']]);
        if ($stu) createNotification((int)$stu['user_id'],'Assessment graded','Your assessment has been evaluated.',
            SITE_URL.'/admin/students/results.php?id='.$id);
    }
    setFlash('success','Graded.');
    redirect('assessment-review.php?id='.$id);
}

$attempts = db_all("SELECT t.*, u.full_name AS student_name
                    FROM assessment_attempts t
                    JOIN students s ON s.id=t.student_id
                    JOIN users u ON u.id=s.user_id
                    JOIN mentor_assignments ma ON ma.student_id=s.id AND ma.course_id=? AND ma.mentor_id=? AND ma.status='active'
                    WHERE t.assessment_id=? AND t.status='submitted'",[$a['course_id'],$mentor['id'],$id]);

$pageTitle='Grade Assessment';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/mentor-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Grading — <?= e($a['title']) ?></h4>
  <?php if (!$attempts): ?><div class="alert alert-info">No submissions awaiting manual review.</div><?php endif; ?>
  <?php foreach ($attempts as $t):
    $answers = db_all("SELECT ans.*, q.question_text, q.question_type, q.marks AS qmarks
                       FROM assessment_answers ans
                       JOIN assessment_questions q ON q.id=ans.question_id
                       WHERE ans.attempt_id=?", [$t['id']]);
  ?>
    <div class="card border-0 shadow-sm rounded-4 mb-3"><div class="card-body">
      <h6 class="fw-bold"><?= e($t['student_name']) ?></h6>
      <form method="post"><?= csrfField() ?>
        <input type="hidden" name="attempt_id" value="<?= (int)$t['id'] ?>">
        <?php foreach ($answers as $ans): ?>
          <div class="border rounded p-2 mb-2">
            <div class="small text-muted"><?= e($ans['question_type']) ?> · max <?= (int)$ans['qmarks'] ?></div>
            <div><?= e($ans['question_text']) ?></div>
            <div class="my-2"><em>Answer:</em> <?= nl2br(e($ans['answer'])) ?></div>
            <input type="number" step="0.25" max="<?= (int)$ans['qmarks'] ?>" name="awarded[<?= (int)$ans['id'] ?>]"
                   value="<?= e($ans['awarded']) ?>" class="form-control form-control-sm" style="max-width:100px" placeholder="Marks">
          </div>
        <?php endforeach; ?>
        <button class="btn btn-primary btn-sm">Save Grades</button>
      </form>
    </div></div>
  <?php endforeach; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
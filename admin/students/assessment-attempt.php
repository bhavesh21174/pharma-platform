<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/performance.php';
requireRole(USER_STUDENT);
$student = db_one('SELECT * FROM students WHERE user_id=?',[$_SESSION['user_id']]);
$id = (int)($_GET['id'] ?? 0);
$a = db_one('SELECT * FROM assessments WHERE id=? AND status="published"',[$id]);
if (!$a) exit('Not found');

$enrolled = db_one("SELECT id FROM enrollments WHERE student_id=? AND course_id=? AND status='active'",[$student['id'],$a['course_id']]);
if (!$enrolled) { http_response_code(403); exit('Not enrolled.'); }

// Attempt limit
$count = (int) db_one('SELECT COUNT(*) c FROM assessment_attempts WHERE assessment_id=? AND student_id=?',[$id,$student['id']])['c'];
if ($count >= (int)$a['attempt_limit']) { setFlash('warning','Attempt limit reached.'); redirect('assessments.php'); }

$questions = db_all('SELECT * FROM assessment_questions WHERE assessment_id=? ORDER BY position',[$id]);

// Handle submission
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    $answers = $_POST['answers'] ?? [];
    $attemptId = db_insert('assessment_attempts', [
        'assessment_id'=>$id,'student_id'=>$student['id'],
        'submitted_at'=>date('Y-m-d H:i:s'),'status'=>'submitted',
    ]);

    $score = 0; $needsManual = false;
    foreach ($questions as $q) {
        $ans = $answers[$q['id']] ?? '';
        $correct = 0; $isCorrect = null;

        if (in_array($q['question_type'], ['mcq','truefalse'])) {
            $isCorrect = (trim((string)$ans) === trim((string)$q['correct_answer'])) ? 1 : 0;
            $correct = $isCorrect ? (float)$q['marks'] : -1 * (float)$a['negative_mark'];
            $score += $correct;
        } elseif ($q['question_type'] === 'multi') {
            // treat as exact match of comma-joined selection
            $ansStr = is_array($ans) ? implode(',', array_map('trim',$ans)) : (string)$ans;
            $isCorrect = (strcasecmp($ansStr, trim((string)$q['correct_answer'])) === 0) ? 1 : 0;
            $correct = $isCorrect ? (float)$q['marks'] : 0;
            $score += $correct;
        } else {
            $needsManual = true; // short/descriptive
        }

        db_insert('assessment_answers', [
            'attempt_id'=>$attemptId,'question_id'=>$q['id'],
            'answer'=> is_array($ans) ? implode(',', $ans) : $ans,
            'is_correct'=>$isCorrect,'awarded'=>$correct ?: null,
        ]);
    }

    $score = max(0, $score);
    $pct   = $a['total_marks'] > 0 ? round(($score / (float)$a['total_marks']) * 100, 2) : 0;
    $result = $pct >= 40 ? 'pass' : 'fail';

    db_update('assessment_attempts', [
        'status'=>$needsManual ? 'submitted' : 'evaluated',
        'score'=>$score,'percentage'=>$pct,
    ], 'id = :id', ['id'=>$attemptId]);

    if (!$needsManual) {
        db_insert('assessment_results', [
            'attempt_id'=>$attemptId,'assessment_id'=>$id,'student_id'=>$student['id'],
            'score'=>$score,'total'=>(int)$a['total_marks'],'percentage'=>$pct,'result'=>$result,
        ]);
        sync_student_performance((int)$student['id'], (int)$a['course_id']);
        createNotification((int)$_SESSION['user_id'],'Assessment result','Your score: '.$score.' / '.$a['total_marks'],
            SITE_URL.'/admin/students/results.php?id='.$id);
    } else {
        // Notify mentor to grade descriptive answers
        $ma = db_one("SELECT mentor_id FROM mentor_assignments WHERE student_id=? AND course_id=? AND status='active'",
                     [$student['id'], $a['course_id']]);
        if ($ma) {
            $mu = db_one('SELECT user_id FROM mentors WHERE id=?',[(int)$ma['mentor_id']]);
            if ($mu) createNotification((int)$mu['user_id'],'Assessment needs manual grading',
                $a['title'], SITE_URL.'/admin/mentors/assessments.php');
        }
    }

    setFlash('success', $needsManual ? 'Submitted. Awaiting manual review.' : 'Submitted. Score: '.$score);
    redirect('assessments.php');
}

$pageTitle = 'Attempt: '.$a['title'];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-1"><?= e($a['title']) ?></h4>
  <p class="text-muted small">Time limit: <?= (int)$a['time_limit_min'] ?> min · Total marks: <?= (int)$a['total_marks'] ?></p>
  <form method="post">
    <?= csrfField() ?>
    <?php foreach ($questions as $i => $q): ?>
      <div class="card border-0 shadow-sm rounded-4 mb-3"><div class="card-body">
        <div class="small text-muted mb-1">Q<?= $i+1 ?> · <?= e($q['question_type']) ?> · <?= (int)$q['marks'] ?> mark(s)</div>
        <div class="fw-semibold mb-3"><?= e($q['question_text']) ?></div>
        <?php if (in_array($q['question_type'], ['mcq','multi','truefalse'])): ?>
          <?php
            $opts = $q['question_type'] === 'truefalse'
                    ? ['True','False']
                    : (json_decode($q['options_json'] ?? '[]', true) ?: []);
            $inputType = $q['question_type']==='multi' ? 'checkbox' : 'radio';
          ?>
          <?php foreach ($opts as $o): ?>
            <div class="form-check">
              <input class="form-check-input" type="<?= $inputType ?>" name="answers[<?= (int)$q['id'] ?>]<?= $q['question_type']==='multi'?'[]':'' ?>" value="<?= e($o) ?>">
              <label class="form-check-label"><?= e($o) ?></label>
            </div>
          <?php endforeach; ?>
        <?php elseif ($q['question_type']==='short'): ?>
          <input name="answers[<?= (int)$q['id'] ?>]" class="form-control">
        <?php else: ?>
          <textarea name="answers[<?= (int)$q['id'] ?>]" rows="4" class="form-control"></textarea>
        <?php endif; ?>
      </div></div>
    <?php endforeach; ?>
    <button class="btn btn-primary px-4">Submit Assessment</button>
  </form>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
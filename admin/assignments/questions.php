<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('assessment.create');
$id = (int)($_GET['id'] ?? 0);
$a = db_one('SELECT * FROM assessments WHERE id=?',[$id]);
if (!$a) exit('Not found');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    if (($_POST['action'] ?? '') === 'delete') {
        db_query('DELETE FROM assessment_questions WHERE id=? AND assessment_id=?', [(int)$_POST['qid'], $id]);
        setFlash('success','Question deleted.');
    } else {
        $type = $_POST['question_type'] ?? 'mcq';
        $options = null;
        if (in_array($type, ['mcq','multi'])) {
            $options = array_values(array_filter(array_map('trim', $_POST['options'] ?? []), fn($o) => $o !== ''));
            $options = json_encode($options);
        }
        if (trim($_POST['question_text'] ?? '') !== '') {
            $pos = (int) db_one('SELECT COALESCE(MAX(position),0)+1 p FROM assessment_questions WHERE assessment_id=?',[$id])['p'];
            db_insert('assessment_questions', [
                'assessment_id'=>$id,'question_type'=>$type,
                'question_text'=>$_POST['question_text'],
                'options_json'=>$options,
                'correct_answer'=>$_POST['correct_answer'] ?? '',
                'marks'=>(int)($_POST['marks'] ?? 1),
                'position'=>$pos,
            ]);
            setFlash('success','Question added.');
        }
    }
    redirect('questions.php?id='.$id);
}

$qs = db_all('SELECT * FROM assessment_questions WHERE assessment_id=? ORDER BY position',[$id]);
$pageTitle='Assessment Questions';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Questions — <?= e($a['title']) ?></h4>
  <div class="row g-3">
    <div class="col-md-5">
      <form method="post" class="card border-0 shadow-sm rounded-4">
        <div class="card-body"><?= csrfField() ?>
          <h6 class="fw-bold mb-3">Add Question</h6>
          <select name="question_type" class="form-select mb-2" onchange="document.getElementById('optBlock').style.display=(this.value==='mcq'||this.value==='multi')?'block':'none'">
            <option value="mcq">MCQ (single answer)</option>
            <option value="multi">Multiple choice</option>
            <option value="truefalse">True/False</option>
            <option value="short">Short answer</option>
            <option value="descriptive">Descriptive</option>
          </select>
          <textarea name="question_text" required rows="3" class="form-control mb-2" placeholder="Question"></textarea>
          <div id="optBlock">
            <input name="options[]" class="form-control mb-1" placeholder="Option A">
            <input name="options[]" class="form-control mb-1" placeholder="Option B">
            <input name="options[]" class="form-control mb-1" placeholder="Option C">
            <input name="options[]" class="form-control mb-1" placeholder="Option D">
          </div>
          <input name="correct_answer" class="form-control mb-2 mt-2" placeholder="Correct answer (text or exact option)">
          <input type="number" name="marks" value="1" class="form-control mb-3" placeholder="Marks">
          <button class="btn btn-primary w-100">Add Question</button>
        </div>
      </form>
    </div>
    <div class="col-md-7">
      <div class="card border-0 shadow-sm rounded-4"><div class="card-body">
        <h6 class="fw-bold mb-3">Questions (<?= count($qs) ?>)</h6>
        <?php foreach ($qs as $q): ?>
          <div class="border-bottom py-2">
            <div class="d-flex justify-content-between">
              <div>
                <div class="small text-muted"><?= e($q['question_type']) ?> · <?= (int)$q['marks'] ?> mark(s)</div>
                <div><?= e($q['question_text']) ?></div>
                <?php if ($q['options_json']): $opts = json_decode($q['options_json'], true) ?: []; ?>
                  <ul class="small mb-0"><?php foreach ($opts as $o): ?><li><?= e($o) ?></li><?php endforeach; ?></ul>
                <?php endif; ?>
                <?php if ($q['correct_answer']): ?><div class="small text-success">Answer: <?= e($q['correct_answer']) ?></div><?php endif; ?>
              </div>
              <form method="post" onsubmit="return confirm('Delete?')">
                <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="qid" value="<?= (int)$q['id'] ?>">
                <button class="btn btn-sm btn-outline-danger">Del</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div></div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
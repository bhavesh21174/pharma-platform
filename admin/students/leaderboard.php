<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requireRole(USER_STUDENT);
$student = db_one('SELECT * FROM students WHERE user_id=?',[$_SESSION['user_id']]);
$visible = setting('leaderboard_public', '1') === '1';
$courseId = (int)($_GET['course_id'] ?? 0);

if (!$courseId) {
    $first = db_one("SELECT course_id FROM enrollments WHERE student_id=? AND status='active' LIMIT 1",[$student['id']]);
    $courseId = $first ? (int)$first['course_id'] : 0;
}

$rows = $courseId ? db_all("SELECT l.*, u.full_name AS student_name
                            FROM leaderboard l JOIN students s ON s.id=l.student_id JOIN users u ON u.id=s.user_id
                            WHERE l.course_id=? ORDER BY l.rank_position LIMIT 50",[$courseId]) : [];

$myRank = $courseId ? db_one('SELECT * FROM leaderboard WHERE course_id=? AND student_id=?',[$courseId,$student['id']]) : null;

$pageTitle='Leaderboard';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">Leaderboard</h4>
  <?php if (!$visible): ?>
    <div class="alert alert-info">Leaderboard is currently disabled by admin.</div>
  <?php else: ?>
    <?php if ($myRank): ?>
      <div class="alert alert-primary small py-2">Your rank: <strong>#<?= (int)$myRank['rank_position'] ?></strong> · Score: <strong><?= number_format((float)$myRank['score'],2) ?></strong></div>
    <?php endif; ?>
    <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
      <table class="table small align-middle mb-0">
        <thead class="table-light"><tr><th>#</th><th>Student</th><th>Score</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr class="<?= (int)$r['student_id']===(int)$student['id']?'table-primary':'' ?>">
            <td><?= (int)$r['rank_position'] ?></td>
            <td><?= e($r['student_name']) ?></td>
            <td><?= number_format((float)$r['score'],2) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
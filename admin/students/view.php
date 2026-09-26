<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('student.view');
$id = (int)($_GET['id'] ?? 0);
$s  = db_one('SELECT s.*, u.full_name,u.email,u.mobile,u.status AS user_status,u.profile_photo FROM students s JOIN users u ON u.id=s.user_id WHERE s.id=?',[$id]);
if (!$s) exit('Not found');

$enrollments = db_all("SELECT e.*, c.name AS course_name, c.course_code, u.full_name AS mentor_name
                       FROM enrollments e
                       JOIN courses c ON c.id=e.course_id
                       LEFT JOIN mentors m ON m.id=e.mentor_id
                       LEFT JOIN users u ON u.id=m.user_id
                       WHERE e.student_id=? ORDER BY e.id DESC",[$id]);

$payments = db_all("SELECT p.*, c.name AS course_name FROM payments p JOIN courses c ON c.id=p.course_id WHERE p.student_id=? ORDER BY p.id DESC LIMIT 10",[$id]);

$pageTitle = $s['full_name'];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <div class="d-flex justify-content-between mb-3">
    <h4 class="fw-bold mb-0"><?= e($s['full_name']) ?></h4>
    <div>
      <?php if (hasPermission('student.edit')): ?><a class="btn btn-outline-secondary" href="edit.php?id=<?= (int)$id ?>">Edit</a><?php endif; ?>
      <a class="btn btn-primary" href="enrollment.php?student_id=<?= (int)$id ?>">Enroll Course</a>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-4"><div class="stat-card"><div class="label">Email</div><div class="small"><?= e($s['email']) ?></div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="label">Mobile</div><div class="small"><?= e($s['mobile']) ?></div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="label">College</div><div class="small"><?= e($s['college']) ?></div></div></div>
  </div>

  <div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body">
    <h6 class="fw-bold mb-3">Enrollments</h6>
    <div class="table-responsive">
      <table class="table small">
        <thead><tr><th>Course</th><th>Mentor</th><th>Status</th><th>Progress</th><th>Enrolled</th></tr></thead>
        <tbody>
        <?php foreach ($enrollments as $e): ?>
          <tr>
            <td><?= e($e['course_name']) ?></td>
            <td><?= e($e['mentor_name'] ?? '—') ?></td>
            <td><span class="badge bg-secondary"><?= e($e['status']) ?></span></td>
            <td><?= number_format((float)$e['progress'],1) ?>%</td>
            <td><?= formatDate($e['enrolled_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div></div>

  <div class="card border-0 shadow-sm rounded-4"><div class="card-body">
    <h6 class="fw-bold mb-3">Recent Payments</h6>
    <div class="table-responsive">
      <table class="table small">
        <thead><tr><th>Course</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
          <tr>
            <td><?= e($p['course_name']) ?></td>
            <td><?= formatCurrency((float)$p['amount']) ?></td>
            <td><span class="badge bg-<?= $p['status']==='successful'?'success':'secondary' ?>"><?= e($p['status']) ?></span></td>
            <td><?= formatDate($p['paid_at'] ?? $p['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
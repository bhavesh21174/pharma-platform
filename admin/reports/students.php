<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('attendance.view');

if (isset($_GET['export']) && $_GET['export']==='csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=students_'.date('Ymd').'.csv');
    $out = fopen('php://output','w');
    fputcsv($out, ['ID','Name','Email','Mobile','College','Courses','Status']);
    $rows = db_all("SELECT s.id,u.full_name,u.email,u.mobile,s.college,u.status,
                      (SELECT COUNT(*) FROM enrollments e WHERE e.student_id=s.id AND e.status='active') courses
                    FROM students s JOIN users u ON u.id=s.user_id");
    foreach ($rows as $r) fputcsv($out, [$r['id'],$r['full_name'],$r['email'],$r['mobile'],$r['college'],$r['courses'],$r['status']]);
    fclose($out); exit;
}

$rows = db_all("SELECT s.id,u.full_name,u.email,u.mobile,s.college,u.status,
                  (SELECT COUNT(*) FROM enrollments e WHERE e.student_id=s.id AND e.status='active') courses
                FROM students s JOIN users u ON u.id=s.user_id ORDER BY s.id DESC");
$pageTitle='Student Report';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <div class="d-flex justify-content-between mb-3">
    <h4 class="fw-bold mb-0">Student Report</h4>
    <a class="btn btn-success" href="?export=csv"><i class="bi bi-download"></i> Export CSV</a>
  </div>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table small align-middle mb-0">
      <thead class="table-light"><tr><th>ID</th><th>Name</th><th>Email</th><th>College</th><th>Courses</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td><td><?= e($r['full_name']) ?></td><td><?= e($r['email']) ?></td>
          <td><?= e($r['college']) ?></td><td><?= (int)$r['courses'] ?></td><td><?= e($r['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
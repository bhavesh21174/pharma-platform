<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/certificate.php';
requirePermission('certificate.view');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    if (($_POST['action']??'')==='scan_all') {
        $enroll = db_all("SELECT student_id, course_id FROM enrollments WHERE status='active'");
        $n = 0;
        foreach ($enroll as $e) if (issue_certificate_if_eligible((int)$e['student_id'], (int)$e['course_id'])) $n++;
        setFlash('success',"$n certificate(s) issued.");
    } elseif (($_POST['action']??'')==='issue_one') {
        $c = issue_certificate_if_eligible((int)$_POST['student_id'], (int)$_POST['course_id']);
        setFlash($c ? 'success' : 'warning', $c ? 'Issued.' : 'Student does not meet completion rules.');
    }
    redirect('index.php');
}

$rows = db_all("SELECT c.*, u.full_name FROM certificates c JOIN students s ON s.id=c.student_id JOIN users u ON u.id=s.user_id ORDER BY c.id DESC");
$pageTitle='Certificates';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <div class="d-flex justify-content-between mb-3">
    <h4 class="fw-bold mb-0">Certificates</h4>
    <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="scan_all">
      <button class="btn btn-primary">Scan All Enrollments</button>
    </form>
  </div>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table small align-middle mb-0">
      <thead class="table-light"><tr><th>Cert ID</th><th>Student</th><th>Course</th><th>Issued</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><code><?= e($r['certificate_id']) ?></code></td>
          <td><?= e($r['student_name']) ?></td>
          <td><?= e($r['course_name']) ?></td>
          <td><?= formatDate($r['completion_date']) ?></td>
          <td class="text-end"><a target="_blank" class="btn btn-sm btn-outline-primary" href="<?= e($r['verification_url']) ?>">Verify</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>